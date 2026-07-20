<script setup lang="js">
import { ref, computed } from 'vue'
import { storeToRefs } from 'pinia'
import ServerListItem from '~/components/servers/ServerListItem.vue'
import { serverAvatarSrc } from '~/composables/useServerAvatar.js'
import { useCreateInviteModal } from '~/composables/useCreateInviteModal.js'
import { useCreateCategoryModal } from '~/composables/useCreateCategoryModal.js'
import { useCreateChannelModal } from '~/composables/useCreateChannelModal.js'
import { useLeaveServerModal } from '~/composables/useLeaveServerModal.js'

const store = useServerStore()
const { reconnect } = useSocketStore()
const { activeServerId, activeServer, pinnedServerIds, servers } = storeToRefs(store)
const { openCreateInviteModal } = useCreateInviteModal()
const { openCreateCategoryModal } = useCreateCategoryModal()
const { openCreateChannelModal } = useCreateChannelModal()
const { openLeaveServerModal } = useLeaveServerModal()

const open = ref(false)

const actionItems = computed(() => {
  if (!activeServer.value) {
    return []
  }

  const isPinned = pinnedServerIds.value.includes(activeServer.value.id)

  return [
    {
      label: isPinned ? 'Unpin server' : 'Pin server',
      icon: isPinned ? 'i-lucide-pin-off' : 'i-lucide-pin',
      onSelect: () => store.togglePinnedServer(activeServer.value.id),
    },
    {
      label: 'Create invite',
      icon: 'i-lucide-user-plus',
      onSelect: () => openCreateInviteModal(activeServer.value),
    },
    {
      label: 'Create channel',
      icon: 'i-lucide-message-circle-plus',
      onSelect: () => openCreateChannelModal(activeServer.value),
    },
    {
      label: 'Create category',
      icon: 'i-lucide-list-plus',
      onSelect: () => openCreateCategoryModal(activeServer.value),
    },
    {
      label: 'Leave server',
      icon: 'i-lucide-log-out',
      color: 'error',
      onSelect: () => promptLeaveServer(activeServer.value.id),
    },
  ]
})

async function handleSelectServer(id) {
  open.value = false
  await navigateTo(`/app/servers/${id}`)
}

async function promptLeaveServer(id) {
  const server = servers.value.find((server) => server.id === id)

  if (!server) {
    return
  }

  const confirmedServer = await openLeaveServerModal(server)

  if (confirmedServer) {
    await handleLeaveServer(confirmedServer.id)
    await reconnect()
  }
}

async function handleLeaveServer(id) {
  if (id === activeServerId.value) {
    await navigateTo('/app')
  }

  await store.leaveServer(id)
}

</script>

<template>
  <UCollapsible
    v-if="activeServer"
    v-model:open="open"
    :ui="{ root: 'w-full', content: 'w-full overflow-hidden' }"
  >
    <div class="group flex w-full cursor-pointer items-center gap-3 px-1 py-2 transition">
      <UAvatar
        :src="serverAvatarSrc(activeServer)"
        :alt="activeServer.name"
        size="md"
        class="shrink-0 rounded-lg"
      />

      <span class="min-w-0 flex-1">
        <span class="block truncate text-sm font-bold text-white">
          {{ activeServer.name }}
        </span>
        <span class="block truncate text-xs text-slate-500">
          Channels
        </span>
      </span>

      <UDropdownMenu
        :items="actionItems"
        :content="{ align: 'end', side: 'bottom', sideOffset: 6 }"
        :ui="{
          content: 'w-44 rounded-xl border border-white/10 bg-slate-950/95 shadow-2xl shadow-black/40 backdrop-blur-xl',
          item: 'rounded-lg',
        }"
      >
        <UButton
          square
          color="neutral"
          variant="ghost"
          icon="i-lucide-settings"
          aria-label="Open server actions"
          class="size-8 shrink-0 rounded-md text-slate-400 hover:bg-white/8 hover:text-white"
          @click.stop
        />
      </UDropdownMenu>

      <span class="grid size-6 shrink-0 place-items-center text-slate-500 transition group-hover:text-slate-300">
        <UIcon
          :name="open ? 'i-lucide-chevron-up' : 'i-lucide-chevron-down'"
          class="size-4"
        />
      </span>
    </div>

    <template #content>
      <div class="flex max-h-[44vh] flex-col gap-0.5 overflow-y-auto border-t border-white/6 py-1">
        <ServerListItem
          v-for="server in servers"
          :key="server.id"
          :server="server"
          :is-pinned="pinnedServerIds.includes(server.id)"
          :active="server.id === activeServerId"
          pinnable
          @leave-server="promptLeaveServer"
          @toggle-pin="store.togglePinnedServer"
          @select-server="handleSelectServer"
        />
      </div>
    </template>
  </UCollapsible>
</template>
