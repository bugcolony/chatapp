<script setup lang="js">
const props = defineProps({
  item: {
    type: Object,
    required: true,
  },
  childCount: {
    type: Number,
    default: 0,
  },
})

const emit = defineEmits(['close'])

const isCategory = computed(() => props.item.type === 'category')
const itemLabel = computed(() => isCategory.value ? props.item.name : `#${props.item.name}`)
const childDescription = computed(() => {
  if (!isCategory.value || props.childCount === 0) {
    return ''
  }

  const noun = props.childCount === 1 ? 'channel' : 'channels'

  return `${props.childCount} ${noun} will be moved out of this category.`
})
</script>

<template>
  <UModal
    :dismissible="false"
    :ui="{
      content: 'max-w-md rounded-2xl bg-default shadow-2xl shadow-black/40 ring ring-default',
    }"
  >
    <template #content>
      <div class="p-6">
        <div class="flex items-start gap-4">
          <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-error/10 text-error">
            <UIcon name="i-lucide-trash-2" class="size-5" />
          </span>

          <div class="min-w-0">
            <h2 class="text-lg font-black tracking-tight text-highlighted">
              Delete {{ isCategory ? 'category' : 'channel' }}?
            </h2>
            <p class="mt-1 text-sm leading-6 text-muted">
              <span class="font-bold text-default">{{ itemLabel }}</span>
              will be permanently deleted. This action cannot be undone.
            </p>
            <p v-if="childDescription" class="mt-2 text-sm text-warning">
              {{ childDescription }}
            </p>
          </div>
        </div>

        <div class="mt-6 flex justify-end gap-2">
          <UButton
            label="Cancel"
            color="neutral"
            variant="ghost"
            @click="emit('close', null)"
          />
          <UButton
            :label="isCategory ? 'Delete category' : 'Delete channel'"
            icon="i-lucide-trash-2"
            color="error"
            @click="emit('close', item)"
          />
        </div>
      </div>
    </template>
  </UModal>
</template>
