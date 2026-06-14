<script setup lang="js">
defineProps({
  open: { type: Boolean, default: false },
  side: { type: String, default: 'left' },
  width: { type: String, default: '308px' },
})

defineEmits(['update:open'])

const ui = {
  root: 'h-full min-h-0 w-auto shrink-0',
  gap: 'min-h-0',
  container: 'h-full border-0 p-2',
  inner: 'chat-panel h-full border border-white/8 bg-slate-900/72 shadow-2xl shadow-black/20 backdrop-blur-xl lg:rounded-4xl ring-0',
  header: 'shrink-0 space-y-3 p-3',
  body: 'min-h-0 flex-1 overflow-y-auto p-3 pt-2',
}
</script>

<template>
  <USidebar
    :open="open"
    :side="side"
    collapsible="offcanvas"
    rail
    mode="slideover"
    :style="{ '--sidebar-width': width }"
    :ui="ui"
    @update:open="$emit('update:open', $event)"
  >
    <template
      v-if="$slots.header"
      #header
    >
      <slot name="header" />
    </template>
    <slot />
  </USidebar>
</template>
