<script setup lang="js">
import {userAvatarSrc} from "~/composables/useServerAvatar.js";

const props = defineProps({
  item: { type: Object, required: true },
  active: { type: Boolean, default: false },
})

const emit = defineEmits(['select', 'add-channel', 'edit', 'delete'])

const isCategory = computed(() => props.item.type === 'category')
const store = useServerStore()
const {activeServerId, serverMembers, voiceChannelParticipants} = storeToRefs(store)
const voiceParticipants = computed(() => {
  if (props.item.type === 'voice') {
    if (voiceChannelParticipants.value.has(props.item.id)) {
      const participants = voiceChannelParticipants.value.get(props.item.id)

      return serverMembers.value[activeServerId.value]?.filter((m) => participants.has(m.user.id)) ?? []
    }
  }

  return []
})
const contextMenuItems = computed(() => [
  [
    {
      label: `Edit ${isCategory.value ? 'category' : 'channel'}`,
      icon: 'i-lucide-pencil',
      onSelect: () => emit('edit', props.item),
    },
  ],
  [
    {
      label: `Delete ${isCategory.value ? 'category' : 'channel'}`,
      icon: 'i-lucide-trash-2',
      color: 'error',
      onSelect: () => emit('delete', props.item),
    },
  ],
])
</script>

<template>

    <div>
      <UContextMenu
          :items="contextMenuItems"
          :ui="{
            content: 'w-44 rounded-xl bg-default/95 shadow-2xl shadow-black/40 ring ring-default backdrop-blur-xl',
            item: 'rounded-lg',
            }"
      >
      <div v-if="item.type === 'category'" class="mb-1.5 flex items-center justify-between px-1">
        <p class="truncate text-xs font-bold uppercase tracking-[0.2em] text-slate-500">
          {{ item.name }}
        </p>
        <UButton
          square
          color="neutral"
          variant="ghost"
          icon="i-lucide-plus"
          size="xs"
          aria-label="Add channel"
          class="rounded-md text-slate-400 hover:bg-white/8 hover:text-white"
          @click.stop="$emit('add-channel', item.id)"
        />
      </div>
      <UButton
        v-else
        block
        color="neutral"
        variant="ghost"
        class="group flex w-full items-center gap-2.5 rounded-lg px-4 py-1 text-left"
        :class="active ? 'bg-white text-slate-950 shadow-lg shadow-black/20' : 'text-slate-300 hover:bg-white/8 hover:text-white'"
        :ui="{ base: 'justify-start' }"
        @click="$emit('select', item.id)"
      >
        <UIcon
          :name="item.icon"
          class="size-4 shrink-0"
        />
        <UIcon :name="item.type === 'voice' ? 'i-lucide-headset' : 'i-lucide-hash'" class="size-3.5 shrink-0 text-slate-500" />
        <span class="min-w-0 flex-1 truncate text-sm font-bold">{{ item.name }}</span>
        <span
          v-if="item.unread > 0"
          class="rounded-full bg-orange-400 px-2 py-0.5 text-xs font-black text-slate-950"
        >
          {{ item.unread }}
        </span>
      </UButton>

      </UContextMenu>
      <ul v-if="item.type === 'voice' && voiceParticipants.length > 0" class="pl-10 py-2">
        <li v-for="member in voiceParticipants" :key="member.id" class="flex items-center py-1">
          <UAvatar
              :src="userAvatarSrc(member)"
              size="xs"
          />
          <span class="ml-2 text-sm">{{member.display_name}}</span>
        </li>
      </ul>
    </div>
</template>
