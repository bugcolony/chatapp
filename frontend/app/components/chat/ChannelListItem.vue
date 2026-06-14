<script setup lang="js">
defineProps({
  item: { type: Object, required: true },
  active: { type: Boolean, default: false },
})

defineEmits(['select'])
</script>

<template>
  <UButton
    block
    color="neutral"
    variant="ghost"
    class="group flex w-full items-center gap-2.5 rounded-lg px-2.5 py-1 text-left"
    :class="active ? 'bg-white text-slate-950 shadow-lg shadow-black/20' : 'text-slate-300 hover:bg-white/8 hover:text-white'"
    :ui="{ base: 'justify-start' }"
    @click="$emit('select', item.id)"
  >
    <UIcon
      :name="item.icon"
      class="size-4 shrink-0"
      :class="item.live ? 'text-emerald-300' : ''"
    />
    <UIcon name="i-lucide-hash" class="size-3.5 shrink-0 text-slate-500" />
    <span class="min-w-0 flex-1 truncate text-sm font-bold">{{ item.name }}</span>
    <span
      v-if="item.live"
      class="rounded-full bg-emerald-400/20 px-1.5 py-0.5 text-xs font-black uppercase tracking-wider text-emerald-200"
    >
      live
    </span>
    <span
      v-if="item.unread > 0"
      class="rounded-full bg-orange-400 px-2 py-0.5 text-xs font-black text-slate-950"
    >
      {{ item.unread }}
    </span>
  </UButton>
</template>
