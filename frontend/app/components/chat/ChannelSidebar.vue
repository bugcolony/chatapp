<script setup lang="js">
import { computed } from 'vue'
import { storeToRefs } from 'pinia'
import ChatSidebar from '~/components/chat/ChatSidebar.vue'
import ChannelSection from '~/components/chat/ChannelSection.vue'
import ServerSwitcher from '~/components/chat/ServerSwitcher.vue'
import { useChatUIStore } from '~/stores/chatUIStore.js'

const store = useServerStore()
const uiStore = useChatUIStore()

const { activeServerId, activeChannelId, serverChannels } = storeToRefs(store)
const { leftSidebarOpen } = storeToRefs(uiStore)

const channelSections = computed(() => [
  { title: 'general', items: serverChannels.value[activeServerId.value] ?? [] },
])

const filteredChannelSections = computed(() => channelSections.value)

async function handleSelectChannel(channelId) {
  if (!activeServerId.value) {
    return
  }
  await navigateTo(`/app/servers/${activeServerId.value}/c/${channelId}`)
}

watchEffect(() => {
  if (activeServerId.value && !serverChannels.value[activeServerId.value]) {
    store.fetchServerChannels(activeServerId.value)
  }
})

</script>

<template>
  <ChatSidebar
    v-model:open="leftSidebarOpen"
    side="left"
    width="308px"
  >
    <template #header>
      <div class="flex flex-col gap-3 w-full">
        <div class="flex justify-end lg:hidden">
          <UButton
            icon="i-lucide-x"
            color="neutral"
            variant="ghost"
            aria-label="Close channels panel"
            class="rounded-xl bg-white/8 text-white hover:bg-white/12"
            @click="leftSidebarOpen = false"
          />
        </div>

        <ServerSwitcher />

<!--        <UInput-->
<!--          v-model="searchQuery"-->
<!--          icon="i-lucide-search"-->
<!--          type="search"-->
<!--          placeholder="Search channels"-->
<!--          color="neutral"-->
<!--          variant="subtle"-->
<!--          :ui="{-->
<!--            root: 'rounded-xl border border-white/8 bg-slate-950/55 px-0',-->
<!--            base: 'min-w-0 bg-transparent text-sm text-white placeholder:text-slate-500'-->
<!--          }"-->
<!--        />-->
      </div>
    </template>

    <div class="space-y-4 pr-1">
      <p
        v-if="filteredChannelSections.length === 0"
        class="rounded-xl border border-dashed border-white/10 px-3 py-4 text-sm text-slate-500"
      >
        No matching channels.
      </p>

      <ChannelSection
        v-for="section in filteredChannelSections"
        :key="section.title"
        :section="section"
        :active-id="activeChannelId"
        @select="handleSelectChannel"
      />
    </div>
  </ChatSidebar>
</template>
