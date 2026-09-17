export const useChatUIStore = defineStore('chatUI', {
    state: () => ({
        leftSidebarOpen: true,
        rightSidebarOpen: true,
        voiceTextVisible: true,
        searchQuery: '',
        serverSearchQuery: '',
        draft: '',
        serverDirectTab: 'servers'
    }),
    actions: {
        toggleLeftSidebar() {
            this.leftSidebarOpen = !this.leftSidebarOpen
        },
        toggleRightSidebar() {
            this.rightSidebarOpen = !this.rightSidebarOpen
        },
        toggleVoiceTextVisible() {
            this.voiceTextVisible = !this.voiceTextVisible
        },
        clearSearch() {
            this.searchQuery = ''
        },
        clearServerSearch() {
            this.serverSearchQuery = ''
        },
        setServerDirectTab(tab) {
            this.serverDirectTab = tab
        }
    },
})