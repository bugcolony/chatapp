<script setup lang="js">
const open = defineModel('open', { type: Boolean, default: false })
</script>

<template>
  <UModal
    v-model:open="open"
    title="Create new server"
    description="Give your server a name to get started."
    :ui="{
      content: 'max-w-md rounded-2xl border border-white/10 bg-slate-950/95 shadow-2xl shadow-black/40 backdrop-blur-xl',
      header: 'border-b border-white/8 p-5',
      title: 'text-lg font-black tracking-tight text-white',
      description: 'text-sm text-slate-400',
      body: 'p-5',
    }"
  >
    <slot />

    <template #body>
      <CreateServerForm
        @success="open = false"
        @cancel="open = false"
      />
    </template>
  </UModal>
</template>
