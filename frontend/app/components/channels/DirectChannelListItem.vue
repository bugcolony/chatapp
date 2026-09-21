<script setup lang="js">
import {userAvatarSrc} from "~/composables/useServerAvatar.js";

const props = defineProps({
  channel: {
    type: Object,
    required: true
  }
})

const route = useRoute()
const store = useServerStore()
const authStore = useAuthStore()
const {channelLastReadId, channelLastMessageId} = storeToRefs(store)

const participants = computed(() => props.channel.participants.filter((p) => p.id !== authStore.user?.id))
const avatars = computed(() => participants.value.map((p) => userAvatarSrc(p)))
const title = computed(() => participants.value.map((p) => p.name).join(', '))
const lastReadId = computed(() => channelLastReadId.value.get(props.channel.id) ?? 0)
const lastMessageId = computed(() => channelLastMessageId.value.get(props.channel.id) ?? 0)
const hasUnreadMessages = computed(() => lastReadId.value < lastMessageId.value)
const active = computed(() => route.path === `/app/direct/${props.channel.id}`)
const isGroup = computed(() => props.channel.type === 'group_dm')
const toast = useToast()

const actionItems = computed(() => [
  [
    {
      label: 'Leave group',
      icon: 'i-lucide-log-out',
      color: 'error',
      onSelect: () => leaveGroup(),
    },
  ],
])

async function leaveGroup() {
  try {
    await store.leaveGroupChannel(props.channel.id)

    if (active.value) {
      await navigateTo('/app')
    }
  } catch (error) {
    toast.add({
      title: 'Could not leave the group',
      description: error?.data?.message ?? 'Please try again.',
      color: 'error',
    })
  }
}
</script>

<template>
  <UContextMenu
      :items="actionItems"
      :disabled="!isGroup"
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
        :to="`/app/direct/${channel.id}`"
        class="group flex w-full items-center gap-2.5 rounded-lg px-4 py-1 text-left relative"
        :class="{
        'bg-white text-slate-900 shadow-lg shadow-black/20' : active,
        'hover:bg-white/8 hover:text-white': !active,
        'text-slate-200': hasUnreadMessages && !active,
        'text-slate-400': !hasUnreadMessages && !active
      }"
        :ui="{ base: 'justify-start' }"
    >
      <UIcon v-show="hasUnreadMessages" name="i-lucide-dot" class="size-7 absolute -left-2" title="Unread messages"/>
      <div class="relative">
        <UAvatar v-if="isGroup" size="xs" class="absolute bg-slate-600/50"/>
        <div :class="{'ml-2' : isGroup}">
          <UCarousel
              v-if="avatars.length > 1"
              v-slot="{ item }"
              loop
              fade
              :autoplay="{ delay: 3000 }"
              :items="avatars"
              :ui="{ item: 'basis-full ps-0', container: 'ms-0' }"
              class="w-6 shrink-0"
          >
            <UAvatar :src="item" size="xs"/>
          </UCarousel>
          <UAvatar v-else :src="avatars[0]" size="xs" class="shrink-0 relative"/>
        </div>
      </div>

      <span
          class="min-w-0 flex-1 truncate text-sm font-bold"
          :class="{'': hasUnreadMessages}"
      >{{ title }}</span>
    </UButton>
  </UContextMenu>
</template>
