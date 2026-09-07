<script setup lang="js">
const props = defineProps({
  channelId: { type: Number, required: true },
})

const ranges = [
  { value: 'last_50', label: 'Last 50 messages' },
  { value: 'today', label: 'Today' },
  { value: 'yesterday', label: 'Yesterday' },
]

const store = useServerStore()
const { channelSummaries, channelSummariesLoading, channelSummaryErrors } = storeToRefs(store)

const open = ref(false)
const range = ref(null)

const key = computed(() => `${props.channelId}:${range.value}`)
const loading = computed(() => channelSummariesLoading.value.has(key.value))
const error = computed(() => channelSummaryErrors.value.get(key.value) ?? null)
const result = computed(() => channelSummaries.value.get(key.value) ?? null)

function select(value) {
  range.value = value
  store.fetchChannelSummary(props.channelId, value)
}

watch(open, (isOpen) => {
  if (!isOpen) {
    range.value = null
  }
})
</script>

<template>
  <UPopover
    v-model:open="open"
    :content="{ side: 'top', align: 'end', sideOffset: 8 }"
    :ui="{ content: 'w-80 rounded-2xl border border-muted bg-elevated p-3 shadow-2xl' }"
  >
    <UButton
      type="button"
      icon="i-lucide-sparkles"
      color="neutral"
      variant="ghost"
      size="sm"
      title="Summarize channel"
      class="size-8 text-muted transition hover:text-highlighted rounded-full flex justify-center"
    />

    <template #content>
      <div class="flex gap-2">
        <UIcon name="i-lucide-sparkles" class="bg-indigo-400" />
        <p class="px-1 pb-2 text-xs font-semibold uppercase tracking-wider text-dimmed">
          Summarize
        </p>
      </div>


      <div class="flex gap-1">
        <UButton
          v-for="option in ranges"
          :key="option.value"
          class="bg-slate-900/40 hover:bg-slate-900/80"
          :class="{'ring-indigo-600 ring-2' : range === option.value }"
          type="button"
          :label="option.label"
          color="neutral"
          :variant="range === option.value ? 'soft' : 'ghost'"
          size="sm"
          block
          :disabled="loading"
          @click="select(option.value)"
        />
      </div>

      <div v-if="range" class="mt-3 border-t border-muted pt-3">
        <p v-if="loading" class="text-sm text-muted animate-pulse">
          Generating…
        </p>

        <p v-else-if="error" class="text-sm text-error">
          {{ error }}
        </p>

        <template v-else-if="result">
          <p v-if="!result.summary" class="text-sm text-muted">
            No messages in this range.
          </p>

          <template v-else>
            <p class="whitespace-pre-line text-sm leading-6 text-highlighted">
              {{ result.summary }}
            </p>
            <p class="mt-2 text-xs text-dimmed">
              <template v-if="result.truncated">
                Last {{ result.message_count }} of {{ result.total }} messages
              </template>
              <template v-else>
                {{ result.message_count }} messages
              </template>
            </p>
          </template>
        </template>
      </div>
    </template>
  </UPopover>
</template>
