<script setup lang="js">
import { computed } from 'vue'

defineProps({
  placeholder: {
    type: String,
    default: 'Message',
  },
})

const emit = defineEmits(['send'])
const draft = defineModel({ type: String, default: '' })

const trimmed = computed(() => draft.value.trim())
const canSend = computed(() => trimmed.value.length > 0)
const characterCount = computed(() => draft.value.length)
const showCount = computed(() => characterCount.value > 180)

function handleSend() {
  if (!canSend.value) return
  emit('send', trimmed.value)
}

function onKeydown(event) {
  if (event.key !== 'Enter') return
  if (event.shiftKey || event.isComposing || event.keyCode === 229) return
  event.preventDefault()
  handleSend()
}
</script>

<template>
  <div class="px-3 pb-3 pt-2 sm:px-4">
    <div class="mx-auto flex max-w-6xl items-end gap-2 overflow-hidden rounded-2xl bg-slate-900/88 p-2 shadow-[0_18px_48px_rgba(0,0,0,0.24)]">
      <div class="mb-1 flex shrink-0 items-center gap-1">
        <UButton
          icon="i-lucide-paperclip"
          color="neutral"
          variant="ghost"
          aria-label="Add attachment"
          title="Add attachment"
          :ui="{
            base: 'grid size-9 place-items-center rounded-xl p-0 text-slate-400 transition hover:bg-white/7 hover:text-slate-100',
            leadingIcon: 'size-4',
          }"
        />
        <UButton
          icon="i-lucide-smile"
          color="neutral"
          variant="ghost"
          aria-label="Add emoji"
          title="Add emoji"
          :ui="{
            base: 'hidden size-9 place-items-center rounded-xl p-0 text-slate-400 transition hover:bg-white/7 hover:text-slate-100 sm:grid',
            leadingIcon: 'size-4',
          }"
          @click="draft = `${draft}🙂`"
        />
      </div>

      <UTextarea
        v-model="draft"
        :placeholder="placeholder"
        :rows="1"
        autoresize
        color="neutral"
        variant="none"
        :ui="{
          root: 'min-w-0 flex-1 self-stretch',
          base: 'block w-full max-h-40 min-h-12 resize-none rounded-xl bg-transparent px-1 py-3 text-[15px] leading-6 text-slate-100 placeholder:text-slate-500',
        }"
        @keydown="onKeydown"
      />

      <div class="mb-1 flex shrink-0 items-center gap-2">
        <span
          v-if="showCount"
          class="hidden min-w-10 text-right text-[11px] font-semibold tabular-nums sm:inline"
          :class="characterCount > 420 ? 'text-orange-200' : 'text-slate-500'"
        >
          {{ characterCount }}
        </span>

        <UButton
          icon="i-lucide-arrow-up"
          color="neutral"
          variant="ghost"
          :disabled="!canSend"
          aria-label="Send message"
          title="Send message"
          :ui="{
            base: [
              'grid size-10 shrink-0 place-items-center rounded-xl p-0 transition',
              canSend
                ? 'bg-slate-100 text-slate-950 hover:bg-white active:scale-95'
                : 'bg-white/5 text-slate-700/60',
              'disabled:cursor-not-allowed disabled:opacity-100',
            ],
            leadingIcon: 'size-5',
          }"
          @click="handleSend"
        />
      </div>
    </div>
  </div>
</template>
