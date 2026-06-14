<script setup lang="js">
import { ref } from 'vue'
import { storeToRefs } from 'pinia'
import ChatSidebar from '~/components/chat/ChatSidebar.vue'
import ServerListItem from '~/components/chat/ServerListItem.vue'
import { useChatUIStore } from '~/stores/chatUIStore.js'

const store = useServerStore()
const uiStore = useChatUIStore()
const { servers, pinnedServerIds } = storeToRefs(store)
const { leftSidebarOpen } = storeToRefs(uiStore)

const tab = ref('servers')

const tabs = [
  { id: 'servers', label: 'Servers', icon: 'i-lucide-server' },
  { id: 'dms', label: 'DMs', icon: 'i-lucide-message-circle' },
]

function handleSelectServer(id) {
  uiStore.searchQuery = ''
  navigateTo(`/app/servers/${id}`)
}
</script>

<template>
  <ChatSidebar
    v-model:open="leftSidebarOpen"
    side="left"
    width="308px"
  >
    <template #header>
      <div class="flex w-full items-center gap-1 rounded-2xl border border-white/8 bg-slate-950/55 p-1">
        <UButton
          v-for="t in tabs"
          :key="t.id"
          :icon="t.icon"
          block
          color="neutral"
          variant="ghost"
          class="flex-1 gap-2 rounded-xl px-2 py-1.5 text-sm font-bold transition"
          :class="tab === t.id ? 'bg-white text-slate-950 shadow-lg shadow-black/20 hover:bg-white' : 'text-slate-400 hover:bg-white/5 hover:text-white'"
          :ui="{ base: 'justify-center' }"
          @click="tab = t.id"
        >
          {{ t.label }}
        </UButton>
      </div>
    </template>

    <div
      v-if="tab === 'servers'"
      class="space-y-1"
    >
      <ServerListItem
        v-for="server in servers"
        :key="server.id"
        :server="server"
        :is-pinned="pinnedServerIds.includes(server.id)"
        pinnable
        @leave-server="store.leaveServer"
        @toggle-pin="store.togglePinnedServer"
        @select-server="handleSelectServer"
      />
    </div>

    <div
      v-else
      class="flex h-full flex-col items-center justify-center px-3 py-10"
    >
      <UIcon
        name="i-lucide-message-circle"
        class="size-10 text-slate-600"
      />
      <p class="mt-3 text-sm font-black text-slate-300">
        No direct messages yet
      </p>
      <p class="mt-1 max-w-55 text-center text-xs text-slate-500">
        Start a conversation from the friends panel on the right.
      </p>
    </div>
  </ChatSidebar>
</template>
