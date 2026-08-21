import { useNotificationHub } from '~/composables/useNotificationHub.js'
import {RealtimeOperations} from "~/constants/RealtimeOperations.js";

export const useSocketStore = defineStore('socket', () => {
    const reconnectBaseDelay = 1000
    const reconnectMaxDelay = 30000
    const config = useRuntimeConfig()
    const auth = useAuthStore()
    const serverStore = useServerStore()
    const { $apiFetch } = useNuxtApp()
    const { play: playNotification } = useNotificationHub()

    let socketUrl
    let connectPromise = null
    let connectPromiseGeneration = null
    let reconnectTimer = null
    let reconnectAttempt = 0
    let shouldReconnect = false
    let connectionGeneration = 0
    let typeEventSent = false

    const {status, open, send, close} = useWebSocket(() => socketUrl, {
        immediate: false,
        autoConnect: false,
        autoReconnect: false,
        onConnected: () => {
            reconnectAttempt = 0
            clearReconnectTimer()
            setActiveServer(serverStore.activeServerId)
        },
        onError: (_, event) => {
            console.warn('[WS] Connection error:', event)
        },
        onDisconnected: () => {
            scheduleReconnect()
        },
        onMessage: (_, event) => {
            messageEventHandler(event)
        }
    })

    function clearReconnectTimer() {
        if (reconnectTimer) {
            clearTimeout(reconnectTimer)
            reconnectTimer = null
        }
    }

    function scheduleReconnect() {
        if (
            !shouldReconnect
            || !auth.isAuthenticated
            || reconnectTimer
            || status.value === 'OPEN'
            || status.value === 'CONNECTING'
        ) {
            return
        }

        const delay = Math.min(
            reconnectBaseDelay * 2 ** reconnectAttempt,
            reconnectMaxDelay,
        )

        const scheduledGeneration = connectionGeneration

        reconnectAttempt += 1
        reconnectTimer = setTimeout(() => {
            reconnectTimer = null

            if (scheduledGeneration === connectionGeneration) {
                void attemptConnection(scheduledGeneration)
            }
        }, delay)
    }

    function dispatchEvent(event) {
        send(JSON.stringify(event))
    }

    function setActiveServer(serverId) {
        if (status.value !== 'OPEN') {
            return
        }

        dispatchEvent({
            op: RealtimeOperations.CLIENT_SERVER_ACTIVE,
            serverId: serverId
        })
    }

    function startTypingEvent(serverId, channelId) {
        typeEventSent = true

        dispatchEvent({
            op: RealtimeOperations.CLIENT_START_TYPING,
            serverId: serverId,
            channelId: channelId
        })
    }

    function stopTypingEvent(serverId, channelId) {
        if (!typeEventSent) {
            return
        }

        dispatchEvent({
            op: RealtimeOperations.CLIENT_STOP_TYPING,
            serverId: serverId,
            channelId: channelId
        })

        typeEventSent = false
    }

    function resolveWebSocketUrl() {
        const configuredUrl = String(config.public.wsURL || '').replace(/\/$/, '')
        const resolvedUrl = new URL(configuredUrl || '/ws', window.location.origin)

        if (resolvedUrl.protocol === 'http:') {
            resolvedUrl.protocol = 'ws:'
        } else if (resolvedUrl.protocol === 'https:') {
            resolvedUrl.protocol = 'wss:'
        }

        return resolvedUrl
    }

    function messageEventHandler(event) {
        const operation = JSON.parse(event.data);

        switch (operation.op) {
            case RealtimeOperations.MESSAGE_CREATED:
                // TODO: do better dedupe
                // TODO: rework to client_id check
                if (operation.senderId === auth.user?.id) {
                    return
                }

                serverStore.upsertChannelMessage(operation.targetChannelId, operation.data)
                playNotification()

                break;
            case RealtimeOperations.CHANNEL_CREATED:
                serverStore.upsertServerChannel(operation.targetServerId, operation.data)
                break;
            case RealtimeOperations.CHANNEL_UPDATED:
                serverStore.upsertServerChannel(operation.targetServerId, operation.data)
                break;
            case RealtimeOperations.CHANNEL_DELETED:
                serverStore.removeServerChannel(operation.targetServerId, operation.data.id)

                if (serverStore.activeChannelId === operation.data.id) {
                    void navigateTo(`/app/servers/${operation.targetServerId}`)
                }
                break;
            case RealtimeOperations.VOICE_PARTICIPANT_JOINED:
                serverStore.addVoiceChannelParticipant(operation.targetChannelId, operation.data.id)
                break;
            case RealtimeOperations.VOICE_PARTICIPANT_LEFT:
                serverStore.removeVoiceChannelParticipant(operation.targetChannelId, operation.data.id)
                break;
            case RealtimeOperations.VOICE_CHANNEL_CLOSED:
                serverStore.clearVoiceChannel(operation.targetChannelId)
                break;
            case RealtimeOperations.GW_MEMBER_STATUS_SNAPSHOT:
                serverStore.setServerMemberStatusSnapshot(operation.targetServerId, operation.data.members)
                break;
            case RealtimeOperations.GW_MEMBER_STATUS:
                serverStore.setServerMemberStatus(operation.targetServerId, operation.data)
                break;
            case RealtimeOperations.CLIENT_START_TYPING:
                serverStore.setChannelTypingPresence(operation.targetChannelId, operation.data.id)
                break;
            case RealtimeOperations.CLIENT_STOP_TYPING:
                serverStore.removeChannelTypingPresence(operation.targetChannelId, operation.data.id)
                break;
            default:
                console.log('[WS] Unknown OP:', operation)
        }
    }

    async function attemptConnection(generation = connectionGeneration) {
        if (generation !== connectionGeneration || !shouldReconnect) {
            return
        }

        if (!auth.isAuthenticated || status.value === 'OPEN' || status.value === 'CONNECTING') {
            return
        }

        if (connectPromise && connectPromiseGeneration === generation) {
            return connectPromise
        }

        clearReconnectTimer()

        const currentConnectPromise = (async () => {
            try {
                const res = await $apiFetch('/ws/ticket', {
                    method: 'POST'
                })

                const nextTicket = res?.ticket

                if (!nextTicket) {
                    throw new Error('WebSocket ticket was not returned')
                }

                if (
                    generation !== connectionGeneration
                    || !shouldReconnect
                    || !auth.isAuthenticated
                ) {
                    return
                }

                const nextUrl = resolveWebSocketUrl()
                nextUrl.searchParams.set('ticket', nextTicket)
                socketUrl = nextUrl.toString()

                open()
            } catch (error) {
                if (generation !== connectionGeneration || !shouldReconnect) {
                    return
                }

                const statusCode = error?.response?.status ?? error?.statusCode

                if (statusCode === 401 || statusCode === 403) {
                    shouldReconnect = false
                    clearReconnectTimer()
                    console.warn(`[WS] Ticket request rejected with ${statusCode}; reconnect disabled`)

                    return
                }

                if (!auth.isAuthenticated) {
                    return
                }

                console.error('Failed WebSocket handshake:', error)
                scheduleReconnect()
            }
        })()

        connectPromise = currentConnectPromise
        connectPromiseGeneration = generation

        try {
            return await currentConnectPromise
        } finally {
            if (connectPromise === currentConnectPromise) {
                connectPromise = null
                connectPromiseGeneration = null
            }
        }
    }

    function connect() {
        shouldReconnect = true

        return attemptConnection()
    }

    function disconnect() {
        shouldReconnect = false
        connectionGeneration += 1
        reconnectAttempt = 0
        clearReconnectTimer()
        close()
        status.value = 'CLOSED'
        socketUrl = undefined
    }

    function reconnect() {
        disconnect()
        return connect()
    }

    if (import.meta.client) {
        useEventListener(window, 'online', () => {
            if (!shouldReconnect || !auth.isAuthenticated || status.value === 'OPEN') {
                return
            }

            reconnectAttempt = 0
            clearReconnectTimer()
            void attemptConnection()
        })
    }

    return {
        status,
        connect,
        disconnect,
        reconnect,
        setActiveServer,
        startTypingEvent,
        stopTypingEvent
    }
})
