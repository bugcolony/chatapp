<script setup lang="js">
import { onBeforeUnmount, onMounted, ref } from 'vue'

const emit = defineEmits(['select'])

const pickerHost = useTemplateRef('pickerHost')
const loading = ref(true)
const loadFailed = ref(false)

let disposed = false
let pickerElement = null

onMounted(async () => {
  try {
    const [{ default: data }, { Picker }] = await Promise.all([
      import('@emoji-mart/data/sets/15/native.json'),
      import('emoji-mart'),
    ])

    if (disposed || !pickerHost.value) return

    pickerElement = new Picker({
      data,
      theme: 'dark',
      set: 'native',
      autoFocus: true,
      dynamicWidth: true,
      emojiButtonSize: 34,
      emojiSize: 22,
      maxFrequentRows: 2,
      previewPosition: 'none',
      skinTonePosition: 'search',
      onEmojiSelect(emoji) {
        if (emoji.native) {
          emit('select', emoji.native)
        }
      },
    })

    pickerElement.classList.add('message-emoji-picker')
    pickerHost.value.appendChild(pickerElement)
  } catch {
    loadFailed.value = true
  } finally {
    loading.value = false
  }
})

onBeforeUnmount(() => {
  disposed = true
  pickerElement?.remove()
})
</script>

<template>
  <div
    class="emoji-picker-shell relative h-[min(26rem,calc(100vh-6rem))] min-h-72 w-[min(22rem,calc(100vw-2rem))] overflow-hidden rounded-2xl bg-elevated"
    :aria-busy="loading"
  >
    <div ref="pickerHost" class="size-full" />

    <div
      v-if="loading"
      class="absolute inset-0 grid place-items-center bg-elevated text-sm text-muted"
    >
      <div class="flex items-center gap-2">
        <UIcon name="i-lucide-loader-circle" class="size-4 animate-spin" />
        Loading emojis…
      </div>
    </div>

    <div
      v-else-if="loadFailed"
      class="absolute inset-0 grid place-items-center bg-elevated px-6 text-center text-sm text-muted"
    >
      Emoji picker could not be loaded.
    </div>
  </div>
</template>

<style scoped>
.emoji-picker-shell :deep(.message-emoji-picker) {
  width: 100%;
  height: 100%;
  --border-radius: 1rem;
  --font-family: "Avenir Next", "Trebuchet MS", "Segoe UI", sans-serif;
  --shadow: none;
}
</style>
