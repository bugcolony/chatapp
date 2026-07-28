const PREVIEWABLE_IMAGE_TYPES = new Set([
  'image/avif',
  'image/gif',
  'image/jpeg',
  'image/png',
  'image/webp',
])

export function isPreviewableImageType(mimeType) {
  return PREVIEWABLE_IMAGE_TYPES.has(mimeType)
}

export function createLocalMessageAttachment(file) {
  if (!file) return null

  const isImage = isPreviewableImageType(file.type)

  return {
    name: file.name,
    size: file.size,
    mime_type: file.type || 'application/octet-stream',
    is_image: isImage,
    url: isImage ? URL.createObjectURL(file) : null,
  }
}

export function revokeLocalMessageAttachment(attachment) {
  if (attachment?.url?.startsWith('blob:')) {
    URL.revokeObjectURL(attachment.url)
  }
}
