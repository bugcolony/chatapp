<script setup lang="js">
import { computed } from 'vue'
import { storeToRefs } from 'pinia'
import CurrentUserCard from '~/components/account/CurrentUserCard.vue'
import AppSidebar from '~/components/layout/AppSidebar.vue'
import { useChatUIStore } from '~/stores/chatUIStore.js'
import { fallbackAvatarSrc } from '~/composables/useServerAvatar.js'

const store = useServerStore()
const uiStore = useChatUIStore()
const { activeServerId, serverMembers, activeServer } = storeToRefs(store)
const { rightSidebarOpen } = storeToRefs(uiStore)

const members = computed(() => serverMembers.value[activeServerId.value]?.toSorted((a,b) => {
  if (a.status !== b.status) {
    return a.status === 'online' ? -1 : 1;
  }

  return a.display_name.localeCompare(b.display_name)
}) ?? [])

const onlineMembersCount = computed(() => members.value.filter((m) => m.status === 'online').length)

const memberActionItems = [
  [
    {
      label: 'Assign role',
      icon: 'i-lucide-shield',
      disabled: true,
    },
  ],
  [
    {
      label: 'Kick member',
      icon: 'i-lucide-user-minus',
      color: 'warning',
      disabled: true,
    },
    {
      label: 'Ban member',
      icon: 'i-lucide-ban',
      color: 'error',
      disabled: true,
    },
  ],
]

watchEffect(() => {
  if (activeServerId.value && !serverMembers.value[activeServerId.value]) {
    store.fetchServerMembers(activeServerId.value)
  }
})

function chipStatusColor(status) {
  switch (status) {
    case 'online':
      return 'bg-green-400'
    case 'away':
      return 'bg-amber-300'
    default:
      return 'bg-slate-700'
  }
}

</script>

<template>
  <AppSidebar
    v-model:open="rightSidebarOpen"
    side="right"
    width="360px"
  >
    <aside class="flex h-full min-h-0 flex-col">
      <CurrentUserCard />

      <div class="min-h-0 flex-1 overflow-y-auto p-2 pt-2">
        <div class="mb-4 flex items-end justify-between gap-3">
          <div class="min-w-0">
            <p class="text-xs font-bold uppercase tracking-[0.24em] text-orange-200/65">
              Members
            </p>
            <h2 class="mt-1 truncate text-xl font-black text-white">
              {{ activeServer?.name ?? '' }}
            </h2>
          </div>
          <UBadge
            variant="soft"
            color="neutral"
            class="rounded-full border border-white/10 bg-white/8 px-2.5 py-1 text-xs font-semibold text-slate-200"
          >
            {{ onlineMembersCount }} online
          </UBadge>
        </div>

        <div class="">
          <UContextMenu
            v-for="member in members"
            :key="member.id"
            :items="memberActionItems"
            :modal="false"
            :ui="{
              content: 'w-44 rounded-xl border border-white/10 bg-slate-950/95 shadow-2xl shadow-black/40 backdrop-blur-xl',
              item: 'rounded-lg',
            }"
          >
            <UButton
              block
              color="neutral"
              variant="ghost"
              class="flex w-full items-center gap-3 rounded-2xl px-2 py-3 text-left transition hover:bg-white/6"
              :class="member.online ? 'text-white' : 'text-slate-500'"
              :ui="{ base: 'justify-start' }"
            >
              <UAvatar
                :src="fallbackAvatarSrc(member.display_name)"
                size="lg"
                :chip="{
                  inset: true,
                  ui: {base: chipStatusColor(member.status) + ' ring-0'}
                }"
              />
              <span class="min-w-0 flex-1">
                <span class="flex items-center gap-2">
                  <span class="truncate text-sm font-bold" :class="{'text-white': member.status === 'online'}">{{ member.display_name }}</span>
<!--                <span class="rounded-full bg-white/8 px-2 py-0.5 text-xs font-bold uppercase tracking-[0.14em] text-slate-400">-->
<!--                  {{ member.role ?? 'dog' }}-->
<!--                </span>-->
                  <UIcon v-if="activeServer.owner_id === member.user.id" class="bg-indigo-900" name="i-lucide-crown" title="Server owner"/>
                </span>
                <span class="block truncate text-xs text-slate-500">{{ member.status }}</span>
              </span>
            </UButton>
          </UContextMenu>
        </div>
      </div>
    </aside>
  </AppSidebar>
</template>
