import {
    createLocalMessageAttachment,
    revokeLocalMessageAttachment,
} from '~/utils/messageAttachment.js'
import {updatePendingMemberPresence} from '~/utils/memberPresence.js'

export const useServerStore = defineStore('server', {
    state: () => ({
        activeServerId: null,
        activeChannelId: null,
        servers: [],
        serverChannels: {},
        serverMembers: {},
        serverMemberFetchGenerations: {},
        channelMessages: new Map(),
        pendingMemberUpdates: {},
        channelMeta: new Map(),
        channelsLoading: new Set(),
        friends: [],
        channelTypingPresence: new Map()
    }),
    getters: {
        activeServer: (state) => state.servers.find((s) => s.id === state.activeServerId) ?? null,
        serverIds: (state) => state.servers.map((s) => s.id),
        pinnedServerIds: (state) => state.servers.filter((s) => s.pin_position).map((el) => el.id) ?? []
    },
    actions: {
        async fetchServers() {
            try {
                const {$apiFetch} = useNuxtApp();

                const res = await $apiFetch('/servers');

                this.servers = res?.data ?? [];
            } catch (error) {
                console.error("Error fetching servers:", error);
                throw error;
            }
        },

        async fetchServerChannels(serverId) {
            try {
                const {$apiFetch} = useNuxtApp();

                const res = await $apiFetch(`servers/${serverId}/channels`);

                this.serverChannels[serverId] = res?.data ?? [];
            } catch (error) {
                console.error("Error fetching serve channels:", error);
                throw error;
            }
        },

        async fetchServerMembers(serverId) {
            const generation = (this.serverMemberFetchGenerations[serverId] ?? 0) + 1
            this.serverMemberFetchGenerations[serverId] = generation

            try {
                const {$apiFetch} = useNuxtApp();

                const res = await $apiFetch(`servers/${serverId}/members`);

                if (this.serverMemberFetchGenerations[serverId] !== generation) {
                    return
                }

                this.serverMembers[serverId] = res?.data ?? [];

                if (this.pendingMemberUpdates[serverId]) {
                    this.setServerMemberStatusSnapshot(serverId, this.pendingMemberUpdates[serverId])
                }
            } catch (error) {
                console.error("Error fetching server members:", error);
                throw error;
            }
        },

        async fetchChannelMessages(channelId = this.activeChannelId) {
            if (!channelId) {
                return;
            }

            try {
                const {$apiFetch} = useNuxtApp();
                this.channelsLoading.add(channelId);

                const res = await $apiFetch(`channels/${channelId}/messages`);

                const data = res?.data ?? [];
                const meta = res?.meta ?? {}

                this.channelMessages.set(channelId, new Map(data.reverse().map(item => [item.id, item])))
                this.channelMeta.set(channelId, meta);
            } catch (error) {
                console.error("Error fetching channel messages:", error);
                throw error;
            } finally {
                this.channelsLoading.delete(channelId);
            }
        },

        async fetchActiveChannelHistory(channelId = this.activeChannelId) {
            if (!channelId) {
                return
            }

            if (this.channelsLoading.has(channelId) || !this.channelMessages.has(channelId)) {
                return
            }

            try {
                const {$apiFetch} = useNuxtApp();

                this.channelsLoading.add(channelId);

                const res = await $apiFetch(`/channels/${channelId}/messages?cursor=${this.channelMeta.get(channelId)?.cursor ?? 0}`);
                const history = res?.data ?? [];
                const meta = res?.meta ?? {}

                this.channelMessages.set(channelId, new Map([...history.reverse().map(item => [item.id, item]), ...this.channelMessages.get(channelId)]));
                this.channelMeta.set(channelId, meta)
            } catch (e) {
                console.error('Failed to load channel history', e);
            } finally {
                this.channelsLoading.delete(channelId);
            }
        },

        appendMessageToChannel(message, id, channelId) {
            const newMessage = isReactive(message) ? message : reactive(message);

            if (this.channelMessages.has(channelId)) {
                if (!this.channelMessages.get(channelId).has(id)) {
                    this.channelMessages.get(channelId).set(id, newMessage)
                }
            } else {
                this.channelMessages.set(channelId, new Map([[id, newMessage]]))
            }
        },

        upsertChannelMessage(channelId, message) {
            const newMessage = isReactive(message) ? message : reactive(message);

            if (this.channelMessages.has(channelId)) {
                if (!this.channelMessages.get(channelId).has(message.id)) {
                    this.channelMessages.get(channelId).set(message.id, newMessage)
                }
            }
        },

        upsertServerChannel(serverId, channel) {
            if (!channel?.id) {
                return;
            }

            const channels = this.serverChannels[serverId] ?? [];

            if (!this.serverChannels[serverId]) {
                this.serverChannels[serverId] = channels;
            }

            const index = channels.findIndex(item => item.id === channel.id);

            if (index === -1) {
                channels.push(channel);
            } else {
                channels[index] = {...channels[index], ...channel};
            }
        },

        removeServerChannel(serverId, channelId) {
            const channels = this.serverChannels[serverId] ?? [];

            this.serverChannels[serverId] = channels
                .filter(channel => channel.id !== channelId)
                .map(channel => channel.parent_id === channelId
                    ? {...channel, parent_id: null}
                    : channel);

            this.invalidateChannelState(channelId);
        },

        setServerMemberStatusSnapshot(serverId, userState) {
            const statuses = new Map(userState.map((e) => [e.id, e]))

            if (!this.serverMembers[serverId]) {
                this.pendingMemberUpdates[serverId] = userState

                return
            }

            this.serverMembers[serverId]?.forEach((m) => {
                if (statuses.has(m.user.id)) {
                    m.status = 'online'
                } else {
                    m.status = 'offline'
                }
            })

            delete this.pendingMemberUpdates[serverId]
        },

        setServerMemberStatus(serverId, userState) {
            if (this.serverMembers[serverId]) {
                const member = this.serverMembers[serverId].find((el) => el.user.id === userState.id)

                if (member) {
                    member.status = userState.status
                }
            } else {
                this.pendingMemberUpdates[serverId] = updatePendingMemberPresence(
                    this.pendingMemberUpdates[serverId],
                    userState,
                )
            }
        },

        setChannelTypingPresence(channelId, userId) {
            if (!this.channelTypingPresence.has(channelId)) {
                this.channelTypingPresence.set(channelId, new Set())
            }

            this.channelTypingPresence.get(channelId).add(userId)
        },

        removeChannelTypingPresence(channelId, userId) {
            if (this.channelTypingPresence.has(channelId)) {
                this.channelTypingPresence.get(channelId).delete(userId)
            }
        },

        async deleteServerChannel(serverId, channelId) {
            const {$apiFetch} = useNuxtApp();

            await $apiFetch(`/channels/${channelId}`, {
                method: 'DELETE',
            });

            this.removeServerChannel(serverId, channelId);
        },

        invalidateChannelState(channelId) {
            for (const message of this.channelMessages.get(channelId)?.values() ?? []) {
                revokeLocalMessageAttachment(message.attachment)
            }

            this.channelMessages.delete(channelId);
            this.channelMeta.delete(channelId);
            this.channelsLoading.delete(channelId);
        },

        sendMessage(payload) {
            const message = typeof payload === 'string'
                ? payload.trim()
                : String(payload?.content ?? '').trim()
            const attachment = typeof File !== 'undefined' && payload?.attachment instanceof File
                ? payload.attachment
                : null

            if (message === '' && !attachment) {
                return;
            }

            const {$apiFetch} = useNuxtApp();
            const auth = useAuthStore();
            const channelId = this.activeChannelId
            const clientId = Date.now()
            const optimisticAttachment = createLocalMessageAttachment(attachment)

            const newMessage = reactive({
                id: null,
                client_id: clientId,
                author: auth.user?.name ?? 'You',
                created_at: new Date().toLocaleDateString([], {hour: 'numeric', minute: '2-digit', hour12: false}),
                message,
                attachment: optimisticAttachment,
                reactions: [],
                mine: true,
                status: 'pending'
            })

            this.appendMessageToChannel(newMessage, clientId, channelId);

            let body = {
                content: message,
                client_id: clientId,
            }

            if (attachment) {
                body = new FormData()
                body.append('content', message)
                body.append('client_id', String(clientId))
                body.append('attachment', attachment, attachment.name)
            }

            return $apiFetch(`/channels/${channelId}/messages`, {
                method: 'POST',
                body,
            }).then(res => {
                const serverMessage = res?.data ?? {}

                revokeLocalMessageAttachment(newMessage.attachment)
                newMessage.id = serverMessage.id ?? null
                newMessage.message = serverMessage.message ?? message
                newMessage.attachment = serverMessage.attachment ?? null
                newMessage.created_at = serverMessage.created_at ?? newMessage.created_at
                newMessage.status = 'sent'
            }).catch(err => {
                console.error(err)
                newMessage.status = 'failed'
                throw err
            })
        },

        async togglePinnedServer(id) {
            const payload = this.pinnedServerIds.slice()
            const index = payload.indexOf(id);
            const added = index === -1

            if (added) {
                payload.push(id);
            } else {
                payload.splice(index, 1);
            }

            try {
                const {$apiFetch} = useNuxtApp();

                await $apiFetch('me/preferences/pinned-servers', {
                    method: 'POST',
                    body: {
                        server_ids: payload
                    }
                })

                const s = this.servers.find((el) => el.id === id)

                if (s) {
                    s.pin_position = added ? 1 : null
                }
            } catch (e) {
                console.error(e)
            }
        },

        async leaveServer(id) {
            try {
                const {$apiFetch} = useNuxtApp();

                await $apiFetch(`/servers/${id}/leave`, {
                    method: 'POST'
                })

                this.unsetServerData(id)
            } catch (e) {
                console.error('Failed to leave server', e);
            }
        },

        unsetServerData(id) {
            const server = this.servers.find(s => s.id === id);

            if (server) {
                for (let channel of this.serverChannels[id] ?? []) {
                    this.invalidateChannelState(channel.id);
                }

                delete this.serverChannels[id]
                delete this.serverMembers[id]

                this.servers = this.servers.filter(s => s.id !== id);
            }
        },

        addServer(server) {
            this.servers.push(server);
        }
    },
});
