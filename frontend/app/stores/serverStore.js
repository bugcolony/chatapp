export const useServerStore = defineStore('server', {
    state: () => ({
        activeServerId: null,
        activeChannelId: null,
        servers: [],
        serverChannels: {},
        serverMembers: {},
        channelMessages: new Map(),
        channelMeta: new Map(),
        channelsLoading: new Set(),
        friends: [],
        pinnedServerIds: []
    }),
    getters: {
        activeServer: (state) => state.servers.find((s) => s.id === state.activeServerId) ?? null,
        serverIds: (state) => state.servers.map((s) => s.id),
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
            try {
                const {$apiFetch} = useNuxtApp();

                const res = await $apiFetch(`servers/${serverId}/members`);

                this.serverMembers[serverId] = res?.data ?? [];
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

        invalidateChannelState(channelId) {
            this.channelMessages.delete(channelId);
        },

        sendMessage(message) {
            if (message.trim() === '') {
                return;
            }

            const {$apiFetch} = useNuxtApp();
            const auth = useAuthStore();

            const clientId = Date.now()

            const newMessage = reactive({
                id: null,
                client_id: clientId,
                author: auth.user?.name ?? 'You',
                created_at: new Date().toLocaleDateString([], {hour: 'numeric', minute: '2-digit', hour12: false}),
                message,
                reactions: [],
                mine: true,
                status: 'pending'
            })

            this.appendMessageToChannel(newMessage, clientId, this.activeChannelId);

            $apiFetch(`/channels/${this.activeChannelId}/messages`, {
                method: 'POST',
                body: {
                    content: message,
                    client_id: clientId,
                }
            }).then(res => {
                newMessage.id = res?.data?.id ?? null
                newMessage.status = 'sent'
            }).catch(err => {
                console.error(err)
                newMessage.status = 'failed'
            })
        },

        togglePinnedServer(id) {
            const index = this.pinnedServerIds.indexOf(id);

            if (index === -1) {
                this.pinnedServerIds.push(id);
            } else {
                this.pinnedServerIds.splice(index, 1);
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
