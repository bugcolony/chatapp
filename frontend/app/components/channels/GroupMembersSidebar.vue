<script setup lang="js">
import { computed } from 'vue'
import { storeToRefs } from 'pinia'
import CurrentUserCard from '~/components/account/CurrentUserCard.vue'
import AppSidebar from '~/components/layout/AppSidebar.vue'
import { useChatUIStore } from '~/stores/chatUIStore.js'
import { userAvatarSrc } from '~/composables/useServerAvatar.js'

const props = defineProps({
  channel: {
    type: Object,
    required: true,
  },
})

const store = useServerStore()
const uiStore = useChatUIStore()
const authStore = useAuthStore()
const toast = useToast()
const { friendStatus } = storeToRefs(store)
const { rightSidebarOpen } = storeToRefs(uiStore)

const isOwner = computed(() => props.channel.owner_id === authStore.user?.id)

const members = computed(() => [...(props.channel.participants ?? [])].sort((a, b) => {
  const aStatus = statusOf(a.id)
  const bStatus = statusOf(b.id)

  if (aStatus !== bStatus) {
    return aStatus === 'online' ? -1 : 1
  }

  return a.name.localeCompare(b.name)
}))

const onlineMembersCount = computed(() => members.value.filter((m) => statusOf(m.id) === 'online').length)

function statusOf(userId) {
  if (userId === authStore.user?.id) {
    return 'online'
  }

  return friendStatus.value.get(userId) ?? 'offline'
}

function actionItemsFor(member) {
  const removable = isOwner.value && member.id !== props.channel.owner_id

  return [
    [
      {
        label: 'Remove from group',
        icon: 'i-lucide-user-minus',
        color: 'error',
        disabled: !removable,
        onSelect: () => removeMember(member),
      },
    ],
  ]
}

async function removeMember(member) {
  try {
    await store.removeGroupParticipant(props.channel.id, member.id)
  } catch (error) {
    toast.add({
      title: 'Could not remove the member',
      description: error?.data?.message ?? 'Please try again.',
      color: 'error',
    })
  }
}

function chipStatusColor(status) {
  return status === 'online' ? 'bg-green-400' : 'bg-slate-700'
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
              Group
            </p>
            <h2 class="mt-1 truncate text-xl font-black text-white">
              {{ members.length }} members
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

        <div>
          <UContextMenu
            v-for="member in members"
            :key="member.id"
            :items="actionItemsFor(member)"
            :modal="false"
            :ui="{
              content: 'w-48 rounded-xl border border-white/10 bg-slate-950/95 shadow-2xl shadow-black/40 backdrop-blur-xl',
              item: 'rounded-lg',
            }"
          >
            <UButton
              block
              color="neutral"
              variant="ghost"
              class="flex w-full items-center gap-3 rounded-2xl px-2 py-3 text-left transition hover:bg-white/6"
              :class="statusOf(member.id) === 'online' ? 'text-white' : 'text-slate-500'"
              :ui="{ base: 'justify-start' }"
            >
              <UAvatar
                :src="userAvatarSrc(member)"
                size="lg"
                :chip="{
                  inset: true,
                  ui: { base: chipStatusColor(statusOf(member.id)) + ' ring-0' }
                }"
              />
              <span class="min-w-0 flex-1">
                <span class="flex items-center gap-2">
                  <span
                    class="truncate text-sm font-bold"
                    :class="{ 'text-white': statusOf(member.id) === 'online' }"
                  >{{ member.name }}</span>
                  <UIcon
                    v-if="channel.owner_id === member.id"
                    class="bg-indigo-900"
                    name="i-lucide-crown"
                    title="Group owner"
                  />
                </span>
                <span class="block truncate text-xs text-slate-500">{{ statusOf(member.id) }}</span>
              </span>
            </UButton>
          </UContextMenu>
        </div>
      </div>
    </aside>
  </AppSidebar>
</template>
