<script setup lang="js">
import { computed } from 'vue'
import { storeToRefs } from 'pinia'
import CreateServerModal from "~/components/servers/CreateServerModal.vue";
import ServerListItem from '~/components/servers/ServerListItem.vue'
import { useChatUIStore } from '~/stores/chatUIStore.js'

const store = useServerStore()
const uiStore = useChatUIStore()
const { activeServerId, servers, pinnedServerIds } = storeToRefs(store)
const { leftSidebarOpen, rightSidebarOpen } = storeToRefs(uiStore)

const pinnedServers = computed(() => servers.value.filter((s) => pinnedServerIds.value.includes(s.id)))
const unpinnedServers = computed(() => servers.value.filter((s) => !pinnedServerIds.value.includes(s.id)))

function handleSelectServer(id) {
  uiStore.searchQuery = ''
  navigateTo(`/app/servers/${id}`)
}

const addMenuItems = computed(() => {
  const serverItems = unpinnedServers.value.map((server) => ({
    id: server.id,
    name: server.name,
    icon: server.icon,
    description: server.description,
    unread: server.unread,
    color: server.color,
    label: server.name,
    type: 'server',
  }))

  return serverItems.length ? [serverItems] : []
})
</script>

<template>
  <nav class="border-b border-white/8 px-4 py-3">
    <div class="flex items-center gap-2">
      <UButton
          :icon="leftSidebarOpen ? 'i-lucide-chevron-left' : 'i-lucide-chevron-right'"
          color="neutral"
          variant="ghost"
          aria-label="Toggle channels panel"
          class="grid size-11 shrink-0 place-items-center rounded-2xl bg-white/8 text-white hover:bg-white/12"
          @click="leftSidebarOpen = !leftSidebarOpen"
      />

      <UButton
          icon="i-lucide-house"
          color="neutral"
          variant="ghost"
          aria-label="Home"
          class="grid size-11 shrink-0 place-items-center rounded-2xl bg-white/8 text-white hover:bg-white/12"
          @click="navigateTo('/app')"
      />

      <div class="min-w-0 flex-1 overflow-x-auto overflow-y-clip py-2">
        <div class="flex min-w-max items-center gap-2 px-0.5">
          <p
              v-if="pinnedServers.length === 0"
              class="rounded-2xl border border-dashed border-white/10 px-3 py-2 text-xs font-bold text-slate-500"
          >
            Pin servers from the left panel
          </p>

          <div
              v-for="server in pinnedServers"
              :key="server.id"
              class="relative shrink-0 overflow-visible p-1"
          >
            <div class="relative size-11">
              <UButton
                  square
                  color="neutral"
                  variant="ghost"
                  class="relative grid size-11 place-items-center overflow-hidden border border-white/10 shadow-lg transition !p-0 hover:border-white/25"
                  :class="server.id === activeServerId ? 'rounded-xl shadow-orange-500/40' : 'rounded-2xl shadow-black/20 hover:shadow-orange-500/15'"
                  :aria-label="server.name"
                  :aria-pressed="server.id === activeServerId"
                  :title="server.name"
                  @click="handleSelectServer(server.id)"
              >
                <UAvatar
                    :src="serverAvatarSrc(server)"
                    :alt="server.name"
                    size="xl"
                    class="w-full h-full rounded-none"
                />
              </UButton>

              <span
                  v-if="server.id === activeServerId"
                  class="pointer-events-none absolute -bottom-1 left-1/2 h-1 w-6 -translate-x-1/2 rounded-full bg-orange-300 shadow-[0_0_8px_rgba(251,146,60,0.9)]"
              />

              <span
                  v-if="server.unread > 0"
                  class="absolute -right-1 -top-1 min-w-5 rounded-full bg-orange-500 px-1.5 py-0.5 text-xs font-black leading-none text-white ring-2 ring-slate-950"
              >
                {{ server.unread }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <UDropdownMenu
          :items="addMenuItems"
          :content="{ align: 'end', side: 'bottom', sideOffset: 8 }"
          :ui="{
          content: 'w-80 rounded-xl border border-white/10 bg-slate-950/95 shadow-2xl shadow-black/40 backdrop-blur-xl',
          item: 'p-0'
        }"
      >
        <template #item="{ item }">
          <ServerListItem
              class="w-full text-start"
              :server="item"
              :active="false"
              :is-pinned="false"
              @select-server="store.togglePinnedServer"
          />
        </template>

        <template #content-bottom>
          <div class="border-t border-white/8 p-1">
            <CreateServerModal>
              <UButton
                  color="neutral"
                  variant="ghost"
                  class="group flex w-full items-center gap-3 rounded-xl p-2 text-left transition hover:bg-white/6"
                  :ui="{ base: 'justify-start' }"
              >
                <div
                    class="grid size-10 shrink-0 place-items-center rounded-2xl border border-dashed border-white/15 bg-white/8">
                  <UIcon name="i-lucide-plus" class="size-5 text-slate-300"/>
                </div>
                <span class="min-w-0 flex-1">
                <span class="block truncate text-sm font-black text-white">Create new server</span>
                <span class="block truncate text-xs text-slate-500">Open setup modal</span>
              </span>
              </UButton>
            </CreateServerModal>
          </div>
        </template>

        <UButton
            square
            color="neutral"
            variant="ghost"
            icon="i-lucide-plus"
            class="grid size-11 place-items-center rounded-2xl border border-dashed border-white/15 bg-white/6 text-slate-300 shadow-lg shadow-black/20 transition hover:border-orange-200/70 hover:bg-orange-400/15 hover:text-white"
            aria-label="Add or pin server"
        />
      </UDropdownMenu>

      <UButton
          :icon="rightSidebarOpen ? 'i-lucide-chevron-right' : 'i-lucide-chevron-left'"
          color="neutral"
          variant="ghost"
          aria-label="Toggle members panel"
          class="grid size-11 shrink-0 place-items-center rounded-2xl bg-white/8 text-white hover:bg-white/12"
          @click="rightSidebarOpen = !rightSidebarOpen"
      />
    </div>
  </nav>


</template>
