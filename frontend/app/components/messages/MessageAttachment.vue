<script setup lang="js">
import MessageDownloadAttachment from '~/components/messages/MessageDownloadAttachment.vue'
import MessageMediaAttachment from '~/components/messages/MessageMediaAttachment.vue'

const PREVIEWABLE_IMAGE_TYPES = new Set([
  'image/avif',
  'image/gif',
  'image/jpeg',
  'image/png',
  'image/webp',
])

const props = defineProps({
  attachment: {
    type: [Object, String],
    default: null,
  },
})

const config = useRuntimeConfig()

const file = computed(() => {
  if (!props.attachment) return null

  if (typeof props.attachment === 'string') {
    return {
      name: props.attachment,
      size: null,
      mime_type: null,
      is_image: false,
      url: null,
    }
  }

  return props.attachment
})

const name = computed(() => file.value?.name || 'Attachment')
const size = computed(() => formatFileSize(file.value?.size))
const metadata = computed(() =>
  [size.value, file.value?.mime_type].filter(Boolean).join(' · ') || 'Shared file',
)
const href = computed(() => {
  const url = file.value?.url

  if (!url) return null
  if (/^(?:blob:|data:|https?:\/\/)/i.test(url)) return url

  const apiBase = String(config.public.apiBase || window.location.origin).replace(/\/$/, '')

  return new URL(url, `${apiBase}/`).toString()
})
const isPreviewableImage = computed(() =>
  Boolean(
    file.value?.is_image
    || PREVIEWABLE_IMAGE_TYPES.has(file.value?.mime_type),
  ),
)

function formatFileSize(bytes) {
  const value = Number(bytes)

  if (!Number.isFinite(value) || value < 0) return null
  if (value === 0) return '0 B'

  const units = ['B', 'KB', 'MB', 'GB']
  const unitIndex = Math.min(
    Math.floor(Math.log(value) / Math.log(1000)),
    units.length - 1,
  )
  const normalizedValue = value / 1000 ** unitIndex

  return `${normalizedValue >= 10 || unitIndex === 0
    ? normalizedValue.toFixed(0)
    : normalizedValue.toFixed(1)} ${units[unitIndex]}`
}
</script>

<template>
  <MessageMediaAttachment
    v-if="file && isPreviewableImage && href"
    :href="href"
    :name="name"
  />

  <MessageDownloadAttachment
    v-else-if="file"
    :href="href"
    :name="name"
    :metadata="metadata"
  />
</template>
