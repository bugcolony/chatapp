<script setup lang="js">
import ServerInvitePanel from "~/components/ServerInvitePanel.vue";

const open = defineModel('open', { type: Boolean, default: false })
const emit = defineEmits(['after:leave'])
const { reconnect } = useSocketStore()

defineProps({
  code: {
    type: String,
    required: true,
  },
})

async function onJoined(serverId) {
  open.value = false
  await reconnect()
  await navigateTo(`/app/servers/${serverId}`)
}
</script>

<template>
  <UModal
    v-model:open="open"
    :ui="{
      content: 'w-full max-w-md overflow-hidden rounded-4xl border border-white/10 bg-slate-900 shadow-2xl shadow-black/35 ring-0',
    }"
    @after:leave="emit('after:leave')"
  >
    <template #content="{ close }">
      <ServerInvitePanel :code="code" @joined="onJoined" />

      <UButton
        square
        color="neutral"
        variant="ghost"
        icon="i-lucide-x"
        aria-label="Close server invite"
        class="absolute right-3 top-3 rounded-full text-slate-400 hover:bg-white/8 hover:text-white"
        @click="close"
      />
    </template>
  </UModal>
</template>
