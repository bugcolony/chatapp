<script setup lang="js">
const props = defineProps({
  href: {
    type: String,
    default: null,
  },
  name: {
    type: String,
    default: 'Attachment',
  },
  metadata: {
    type: String,
    default: 'Shared file',
  },
})

const { $apiFetch } = useNuxtApp()
const toast = useToast()
const downloading = ref(false)

async function download() {
  if (!props.href || downloading.value) return

  downloading.value = true

  try {
    const blob = await $apiFetch(props.href, {
      responseType: 'blob',
    })
    const objectUrl = URL.createObjectURL(blob)
    const link = document.createElement('a')

    link.href = objectUrl
    link.download = props.name
    link.hidden = true
    document.body.append(link)
    link.click()
    link.remove()
    window.setTimeout(() => URL.revokeObjectURL(objectUrl), 1000)
  } catch {
    toast.add({
      title: 'Download failed',
      description: 'The file could not be downloaded. Try again.',
      icon: 'i-lucide-cloud-off',
      color: 'error',
    })
  } finally {
    downloading.value = false
  }
}
</script>

<template>
  <div
    class="mt-2 flex max-w-sm items-center gap-2.5 rounded-2xl border border-white/8 bg-white/6 px-3 py-2 text-left transition hover:bg-white/10"
  >
    <span class="grid size-8 place-items-center rounded-xl bg-cyan-300/15 text-cyan-200">
      <UIcon name="i-lucide-paperclip" class="size-4" />
    </span>
    <span class="min-w-0 flex-1">
      <span class="block max-w-64 truncate text-sm font-bold text-white">{{ name }}</span>
      <span class="block text-xs text-slate-500">{{ metadata }}</span>
    </span>
    <UButton
      :disabled="!href"
      :loading="downloading"
      :aria-label="href ? `Download ${name}` : `${name} is unavailable`"
      :title="href ? `Download ${name}` : 'File unavailable'"
      icon="i-lucide-download"
      color="neutral"
      variant="ghost"
      size="sm"
      square
      class="shrink-0"
      @click="download"
    />
  </div>
</template>
