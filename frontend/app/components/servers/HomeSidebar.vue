<script setup lang="js">
import { computed } from 'vue'
import { storeToRefs } from 'pinia'
import AppSidebar from '~/components/layout/AppSidebar.vue'
import ServerListItem from '~/components/servers/ServerListItem.vue'
import DirectChannelListItem from '~/components/channels/DirectChannelListItem.vue'
import { useChatUIStore } from '~/stores/chatUIStore.js'

const store = useServerStore()
const uiStore = useChatUIStore()
const { servers, pinnedServerIds, directChannels } = storeToRefs(store)
const { leftSidebarOpen, serverDirectTab } = storeToRefs(uiStore)

const directChannelList = computed(() => [...directChannels.value.values()])

const tabs = [
  { label: 'Servers', icon: 'i-lucide-server', value: 'servers' },
  { label: 'Messages', icon: 'i-lucide-message-circle', value: 'direct' },
]

function handleSelectServer(id) {
  uiStore.searchQuery = ''
  navigateTo(`/app/servers/${id}`)
}
</script>

<template>
  <AppSidebar
    v-model:open="leftSidebarOpen"
    side="left"
    width="308px"
  >
    <template #header>
      <UTabs
        :model-value="serverDirectTab"
        :items="tabs"
        :content="false"
        color="neutral"
        size="sm"
        class="w-full"
        @update:model-value="uiStore.setServerDirectTab"
      />
    </template>

    <div
      v-if="serverDirectTab === 'servers'"
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

    <template v-if="serverDirectTab === 'direct'">
      <div
        v-if="directChannelList.length"
        class="space-y-1"
      >
        <DirectChannelListItem
          v-for="channel in directChannelList"
          :key="channel.id"
          :channel="channel"
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
      </div>
    </template>
  </AppSidebar>
</template>
