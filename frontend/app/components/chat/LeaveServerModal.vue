<script setup lang="js">
const emit = defineEmits(['close'])

const props = defineProps({
  server: {
    type: Object,
    default: null,
  },
})

function confirm() {
  emit('close', props.server)
}
</script>

<template>
  <UModal
    :dismissible="false"
  >
    <template #content>
      <div class="p-8">
        <div class="flex items-center gap-5 mb-2">
          <UIcon name="i-lucide-log-out" class="size-10 text-slate-400" />
          <div>
            <p class="font-semibold">{{server ? `Leave ${server.name}?` : ''}}</p>
            <p class="text-sm text-muted">
              You will lose access until you join again.
            </p>
          </div>
        </div>

        <div class="flex justify-end gap-3">
          <UButton
              label="Cancel"
              color="neutral"
              variant="ghost"
              @click="emit('close', null)"
          />
          <UButton
              label="Leave"
              color="error"
              @click="confirm"
          />
        </div>
      </div>
    </template>
  </UModal>
</template>
