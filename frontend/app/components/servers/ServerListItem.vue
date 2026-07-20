<script setup lang="js">
import { computed } from 'vue'

const props = defineProps({
  server: {
    type: Object,
    required: true,
  },
  pinnable: {
    type: Boolean,
    default: false,
  },
  active: {
    type: Boolean,
    default: false,
  },
  isPinned: {
    type: Boolean,
    default: false,
  },
})

const avatar = computed(() => serverAvatarSrc(props.server))

const emit = defineEmits(['leave-server', 'toggle-pin', 'select-server'])

const actionItems = computed(() => [
  [
    {
      label: props.isPinned ? 'Unpin server' : 'Pin server',
      icon: props.isPinned ? 'i-lucide-pin-off' : 'i-lucide-pin',
      onSelect: () => emit('toggle-pin', props.server.id),
    },
  ],
  [
    {
      label: 'Leave server',
      icon: 'i-lucide-log-out',
      color: 'error',
      onSelect: () => emit('leave-server', props.server.id),
    },
  ],
])
</script>

<template>
  <UContextMenu
    :items="actionItems"
    :disabled="!pinnable"
    :modal="false"
    :ui="{
      content: 'w-44 rounded-xl border border-white/10 bg-slate-950/95 shadow-2xl shadow-black/40 backdrop-blur-xl',
      item: 'rounded-lg',
    }"
  >
    <div
      role="button"
      tabindex="0"
      class="group relative flex cursor-pointer items-center gap-2.5 rounded-lg px-2 py-2 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400/40"
      :class="active
        ? 'bg-white/8'
        : 'hover:bg-white/5'"
      @click="$emit('select-server', server.id)"
      @keydown.enter.prevent="$emit('select-server', server.id)"
      @keydown.space.prevent="$emit('select-server', server.id)"
    >
      <UAvatar
        :src="avatar"
        :alt="server.name"
        size="sm"
        class="shrink-0 rounded-md"
      />

      <span
        class="min-w-0 flex-1 truncate text-sm font-semibold transition"
        :class="active ? 'text-white' : 'text-slate-300'"
      >{{ server.name }}</span>

      <UIcon
        v-if="isPinned"
        name="i-lucide-pin"
        class="size-3.5 shrink-0 text-slate-500"
      />

      <span
        v-if="server.unread > 0"
        class="grid h-5 min-w-5 shrink-0 place-items-center rounded-full px-1.5 text-[10px] font-bold tabular-nums transition"
        :class="active
          ? 'bg-indigo-400/30 text-indigo-100'
          : 'bg-white/8 text-slate-300'"
      >{{ server.unread > 99 ? '99+' : server.unread }}</span>
    </div>
  </UContextMenu>
</template>
