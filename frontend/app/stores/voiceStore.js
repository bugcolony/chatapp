import {ParticipantEvent, Room, RoomEvent, VideoPresets, ConnectionState} from 'livekit-client';
export const useVoiceStore = defineStore('voice', () => {
    const config = useRuntimeConfig()
    const url = config.public.rtcUrl
    const {$apiFetch} = useNuxtApp()
    const voiceState = ref(ConnectionState.Disconnected)

    const connections = reactive(new Map())
    const activeChannelId = ref(null)

    const microphoneEnabled = ref(false)

    const activeConnection = computed(() => {
        if (activeChannelId.value === null) {
            return null
        }

        return connections.get(activeChannelId.value) ?? null
    })

    const participants = computed(() => activeConnection.value?.participants ?? new Map())
    const cameraEnabled = computed(() => activeConnection.value?.cameraEnabled ?? false)
    const screenEnabled = computed(() => activeConnection.value?.screenEnabled ?? false)
    const microphoneStateLoading = computed(() => activeConnection.value?.microphoneStateLoading ?? false)
    const cameraStateLoading = computed(() => activeConnection.value?.cameraStateLoading ?? false)
    const screenStateLoading = computed(() => activeConnection.value?.screenStateLoading ?? false)
    const connectionStateConnected = computed(() => voiceState.value === ConnectionState.Connected)
    const connectionStateDisconnected = computed(() => voiceState.value === ConnectionState.Disconnected)
    const connectionStateConnecting = computed(() => [ConnectionState.Connecting, ConnectionState.Reconnecting, ConnectionState.SignalReconnecting].includes(voiceState.value))

    function createRecord(channelId) {
        return reactive({
            channelId,
            room: null,
            participants: new Map(),
            participantHandlers: markRaw(new Map()),
            roomHandlers: null,
            state: ConnectionState.Disconnected,
            cameraEnabled: false,
            screenEnabled: false,
            microphoneStateLoading: false,
            cameraStateLoading: false,
            screenStateLoading: false,
        })
    }

    function isStale(record) {
        return connections.get(record.channelId) !== record
    }

    async function connect(channelId) {
        if (connections.has(channelId)) {
            return
        }

        const record = createRecord(channelId)

        connections.set(channelId, record)
        activeChannelId.value = channelId

        try {
            await disconnectOthers(channelId)

            if (isStale(record)) {
                return
            }

            const res = await $apiFetch(`/channels/${channelId}/credentials`, {
                method: 'POST',
            })

            if (isStale(record)) {
                return
            }

            const roomToken = res?.token

            if (!roomToken) {
                throw new Error("Failed to resolve connection token")
            }

            const room = buildRoom(record)
            record.room = markRaw(room)

            await room.prepareConnection(url, roomToken)
            await room.connect(url, roomToken)

            if (isStale(record)) {
                await closeRoom(record)

                return
            }

            handleParticipantConnected(record, room.localParticipant)

            room.remoteParticipants.forEach((participant) => {
                handleParticipantConnected(record, participant)
            })

            try {
                await room.localParticipant.setMicrophoneEnabled(microphoneEnabled.value)
            } catch (err) {
                console.error(err)
                microphoneEnabled.value = false
            }
        } catch (err) {
            console.error(err)
            await teardown(record)
        }
    }

    async function disconnect(channelId) {
        const record = connections.get(channelId)

        if (!record) {
            return
        }

        await teardown(record)
    }

    async function disconnectAll() {
        await Promise.all(Array.from(connections.values()).map((record) => teardown(record)))
    }

    async function disconnectOthers(channelId) {
        const others = Array.from(connections.values()).filter((record) => record.channelId !== channelId)

        await Promise.all(others.map((record) => teardown(record)))
    }

    async function teardown(record) {
        if (connections.get(record.channelId) === record) {
            connections.delete(record.channelId)
        }

        if (activeChannelId.value === record.channelId) {
            activeChannelId.value = null
        }

        await closeRoom(record)
    }

    async function closeRoom(record) {
        const room = record.room
        record.room = null

        detachRoomListeners(record, room)

        record.participants.forEach((entry) => {
            detachParticipantListeners(record, entry.participant)
        })

        record.participantHandlers.clear()
        record.participants = new Map()
        record.state = ConnectionState.Disconnected
        record.cameraEnabled = false
        record.screenEnabled = false
        record.microphoneStateLoading = false
        record.cameraStateLoading = false
        record.screenStateLoading = false

        voiceState.value = ConnectionState.Disconnected

        if (!room) {
            return
        }

        try {
            await room.disconnect()
        } catch (err) {
            console.error(err)
        }
    }

    function buildRoom(record) {
        const room = new Room({
            adaptiveStream: true,
            dynacast: true,
            videoCaptureDefaults: {
                resolution: VideoPresets.h720.resolution,
            }
        })

        record.roomHandlers = markRaw({
            participantConnected: (participant) => handleParticipantConnected(record, participant),
            participantDisconnected: (participant) => handleParticipantDisconnected(record, participant),
            disconnected: () => handleRoomDisconnected(record),
            trackSubscribed: (track, publication, participant) => handleTrackSubscribed(record, track, participant),
            trackUnsubscribed: (track, publication, participant) => handleTrackUnsubscribed(record, track, participant),
            connectionStateChanged: (state) => handleConnectionStateChanged(record, state),
            trackPublished: (publication, participant) => handleTrackPublished(record, publication, participant),
            trackUnpublished: (publication, participant) => handleTrackUnpublished(record, publication, participant),
        })

        room
            .on(RoomEvent.ParticipantConnected, record.roomHandlers.participantConnected)
            .on(RoomEvent.ParticipantDisconnected, record.roomHandlers.participantDisconnected)
            .on(RoomEvent.Disconnected, record.roomHandlers.disconnected)
            .on(RoomEvent.TrackSubscribed, record.roomHandlers.trackSubscribed)
            .on(RoomEvent.TrackUnsubscribed, record.roomHandlers.trackUnsubscribed)
            .on(RoomEvent.ConnectionStateChanged, record.roomHandlers.connectionStateChanged)
            // .on(RoomEvent.LocalTrackPublished, record.roomHandlers.trackPublished)
            // .on(RoomEvent.LocalTrackUnpublished, record.roomHandlers.trackUnpublished)

        return room
    }

    function detachRoomListeners(record, room) {
        const handlers = record.roomHandlers
        record.roomHandlers = null

        if (!room || !handlers) {
            return
        }

        room
            .off(RoomEvent.ParticipantConnected, handlers.participantConnected)
            .off(RoomEvent.ParticipantDisconnected, handlers.participantDisconnected)
            .off(RoomEvent.Disconnected, handlers.disconnected)
            .off(RoomEvent.TrackSubscribed, handlers.trackSubscribed)
            .off(RoomEvent.TrackUnsubscribed, handlers.trackUnsubscribed)
            .off(RoomEvent.ConnectionStateChanged, handlers.connectionStateChanged)
            // .off(RoomEvent.LocalTrackPublished, handlers.trackPublished)
            // .off(RoomEvent.LocalTrackUnpublished, handlers.trackUnpublished)
    }

    function handleRoomDisconnected(record) {
        void teardown(record)
    }

    function findOrCreate(record, participant) {
        if (!record.participants.has(participant.identity)) {
            insertParticipant(record, participant)
        }

        return record.participants.get(participant.identity)
    }

    function insertParticipant(record, participant) {
        if (!record.participants.has(participant.identity)) {
            record.participants.set(participant.identity, {
                sid: participant.sid,
                local: participant.isLocal,
                identity: participant.identity,
                participant: markRaw(participant),
                isSpeaking: participant.isSpeaking,
                audioLevel: participant.audioLevel,
                tracks: new Map(),
            })
        }
    }

    function deleteParticipant(record, participant) {
        if (!record.participants.has(participant.identity)) {
            return
        }

        record.participants.delete(participant.identity)
    }

    function handleTrackPublished(record, publication, participant) {
        if (!publication.track) {
            return
        }

        const entry = findOrCreate(record, participant)
        entry.tracks.set(publication.trackSid, {
            sid: publication.trackSid,
            source: publication.source,
            kind: publication.kind,
            t: markRaw(publication.track),
            muted: publication.isMuted
        })
    }

    function handleTrackUnpublished(record, publication, participant) {
        const entry = record.participants.get(participant.identity)
        entry?.tracks.delete(publication.trackSid)
    }

    function handleTrackSubscribed(record, track, participant) {
        const entry = findOrCreate(record, participant)
        entry.tracks.set(track.sid, {
            sid: track.sid,
            source: track.source,
            kind: track.kind,
            t: markRaw(track),
            muted: track.isMuted
        })
    }

    function handleTrackUnsubscribed(record, track, participant) {
        const entry = record.participants.get(participant.identity)
        entry?.tracks.delete(track.sid)
    }

    function handleConnectionStateChanged(record, state) {
        record.state = state
        voiceState.value = state
    }

    function handleParticipantDisconnected(record, participant) {
        detachParticipantListeners(record, participant)
        deleteParticipant(record, participant)
    }

    function handleParticipantConnected(record, participant) {
        insertParticipant(record, participant)
        attachParticipantListeners(record, participant)
    }

    function attachParticipantListeners(record, participant) {
        if (!record.participants.has(participant.identity) || record.participantHandlers.has(participant.identity)) {
            return
        }

        const handlers = {
            trackMuted: (pub) => handleTrackMuted(record, pub, participant),
            trackUnmuted: (pub) => handleTrackMuted(record, pub, participant),
            isSpeakingChanged: (isSpeaking) => handleSpeakingChanged(record, participant, isSpeaking)
        }

        participant
            .on(ParticipantEvent.TrackMuted, handlers.trackMuted)
            .on(ParticipantEvent.TrackUnmuted, handlers.trackUnmuted)
            .on(ParticipantEvent.IsSpeakingChanged, handlers.isSpeakingChanged)

        record.participantHandlers.set(participant.identity, handlers)
    }

    function detachParticipantListeners(record, participant) {
        if (!record.participantHandlers.has(participant.identity)) {
            return
        }

        const handlers = record.participantHandlers.get(participant.identity)

        participant
            .off(ParticipantEvent.TrackMuted, handlers.trackMuted)
            .off(ParticipantEvent.TrackUnmuted, handlers.trackUnmuted)
            .off(ParticipantEvent.IsSpeakingChanged, handlers.isSpeakingChanged)

        record.participantHandlers.delete(participant.identity)
    }

    function handleSpeakingChanged(record, participant, isSpeaking) {
        const entry = record.participants.get(participant.identity)

        if (entry) {
            entry.isSpeaking = isSpeaking
        }
    }

    function handleTrackMuted(record, publication, participant) {
        const entry = record.participants.get(participant.identity)
        const track = entry?.tracks.get(publication.trackSid)

        if (track) {
            track.muted = publication.isMuted
        }
    }

    async function toggleMicrophone() {
        const record = activeConnection.value

        if (!record?.room) {
            return
        }

        const newState = !microphoneEnabled.value
        try {
            record.microphoneStateLoading = true
            await record.room.localParticipant.setMicrophoneEnabled(newState)
            microphoneEnabled.value = newState
        } catch (e) {
            console.error(e)
        } finally {
            record.microphoneStateLoading = false
        }
    }

    async function toggleCamera() {
        const record = activeConnection.value

        if (!record?.room) {
            return
        }

        const newState = !record.cameraEnabled
        try {
            record.cameraStateLoading = true
            await record.room.localParticipant.setCameraEnabled(newState)
            record.cameraEnabled = newState
        } catch (e) {
            console.error(e)
        } finally {
            record.cameraStateLoading = false
        }
    }

    async function toggleScreen() {
        const record = activeConnection.value

        if (!record?.room) {
            return
        }

        const newState = !record.screenEnabled
        try {
            record.screenStateLoading = true
            await record.room.localParticipant.setScreenShareEnabled(newState)
            record.screenEnabled = newState
        } catch (e) {
            console.error(e)
        } finally {
            record.screenStateLoading = false
        }
    }

    return {
        participants,
        connect,
        disconnect,
        disconnectAll,
        toggleMicrophone,
        toggleCamera,
        toggleScreen,
        cameraEnabled,
        microphoneEnabled,
        screenEnabled,
        microphoneStateLoading,
        cameraStateLoading,
        screenStateLoading,
        connectionStateConnected,
        connectionStateDisconnected,
        connectionStateConnecting,
        voiceState
    }
})
