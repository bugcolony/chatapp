export const useChatUIStore = defineStore('chatUI', {
    state: () => ({
        leftSidebarOpen: true,
        rightSidebarOpen: true,
        searchQuery: '',
        serverSearchQuery: '',
        draft: '',
    }),
    actions: {
        toggleLeftSidebar() {
            this.leftSidebarOpen = !this.leftSidebarOpen
        },
        toggleRightSidebar() {
            this.rightSidebarOpen = !this.rightSidebarOpen
        },
        clearSearch() {
            this.searchQuery = ''
        },
        clearServerSearch() {
            this.serverSearchQuery = ''
        },
    },
})