export const KLIPY_PAGE_SIZE = 24
export const KLIPY_WEBSITE_URL = 'https://klipy.com'
export const KLIPY_API_BASE = 'https://api.klipy.com/api/v1'

const KLIPY_CONTENT_FILTER = 'low'
const KLIPY_MEDIA_HOSTS = new Set([
  'static.klipy.co',
  'static.klipy.com',
  'static1.klipy.com',
  'static2.klipy.com',
])

const PREVIEW_FORMATS = [
  ['sm', 'webp'],
  ['xs', 'webp'],
  ['sm', 'gif'],
  ['xs', 'gif'],
  ['md', 'webp'],
  ['md', 'gif'],
  ['hd', 'webp'],
  ['hd', 'gif'],
]

const MESSAGE_FORMATS = [
  ['md', 'webp'],
  ['md', 'gif'],
  ['sm', 'webp'],
  ['sm', 'gif'],
  ['xs', 'webp'],
  ['xs', 'gif'],
  ['hd', 'webp'],
  ['hd', 'gif'],
]

export function normalizeKlipyLocale(language) {
  const parts = String(language ?? '')
    .replace('_', '-')
    .split('-')
    .filter(Boolean)

  if (!parts.length) return undefined

  // KLIPY's official native-v1 clients send the two-letter language code.
  return parts[0].toLowerCase()
}

export function createKlipyRequest({
  apiKey,
  query = '',
  page = 1,
  language,
} = {}) {
  const key = String(apiKey ?? '').trim()

  if (!key) {
    throw new Error('KLIPY_API_KEY_MISSING')
  }

  const normalizedQuery = String(query).trim()
  const locale = normalizeKlipyLocale(language)
  const endpoint = normalizedQuery ? 'search' : 'trending'

  return {
    url: `${KLIPY_API_BASE}/${encodeURIComponent(key)}/gifs/${endpoint}`,
    query: {
      page: positiveInteger(page) ?? 1,
      per_page: KLIPY_PAGE_SIZE,
      ...(normalizedQuery ? { q: normalizedQuery } : {}),
      ...(locale ? { locale } : {}),
      content_filter: KLIPY_CONTENT_FILTER,
      format_filter: 'gif,webp',
    },
  }
}

export function normalizeKlipyResponse(response, requestedPage = 1) {
  const payload = response?.data

  if (response?.result !== true || !Array.isArray(payload?.data)) {
    throw new Error('KLIPY_RESPONSE_INVALID')
  }

  const rawItems = payload.data
  const currentPage = positiveInteger(payload?.current_page) ?? requestedPage

  return {
    gifs: rawItems
      .map(normalizeKlipyGif)
      .filter(Boolean),
    currentPage,
    hasNext: Boolean(payload?.has_next),
  }
}

export function normalizeKlipyGif(item) {
  if (!item || (item.type && item.type !== 'gif')) return null

  const files = item.file ?? item.files
  const preview = findFormat(files, PREVIEW_FORMATS)
  const message = findFormat(files, MESSAGE_FORMATS)

  if (!preview || !message) return null

  const slug = String(item.slug ?? '').trim()
  const title = String(item.title ?? '').trim() || 'GIF'
  const id = String(item.id ?? (slug || message.url))

  return {
    id,
    slug,
    title,
    previewUrl: preview.url,
    url: message.url,
    width: preview.width ?? message.width ?? 1,
    height: preview.height ?? message.height ?? 1,
    providerUrl: slug
      ? `${KLIPY_WEBSITE_URL}/gifs/${encodeURIComponent(slug)}`
      : KLIPY_WEBSITE_URL,
  }
}

export function createKlipyGifMarkdown(gif) {
  const url = normalizeKlipyMediaUrl(gif?.url)

  if (!url) return ''

  const title = String(gif?.title ?? '').trim() || 'GIF'
  const alt = `${title.replace(/\s+/g, ' ')} — GIF from KLIPY`
    .slice(0, 240)
    .replace(/[\\[\]]/g, '\\$&')

  return `![${alt}](<${url}>)`
}

export function isAllowedKlipyMediaUrl(value) {
  return normalizeKlipyMediaUrl(value) !== null
}

function findFormat(files, candidates) {
  if (!files || typeof files !== 'object') return null

  for (const [size, format] of candidates) {
    const candidate = files[size]?.[format]
    const url = normalizeKlipyMediaUrl(candidate?.url)

    if (!url) continue

    return {
      url,
      width: positiveInteger(candidate.width),
      height: positiveInteger(candidate.height),
    }
  }

  return null
}

function normalizeKlipyMediaUrl(value) {
  if (typeof value !== 'string' || !value.trim()) return null

  try {
    const url = new URL(value.startsWith('//') ? `https:${value}` : value)

    return url.protocol === 'https:'
      && (!url.port || url.port === '443')
      && !url.username
      && !url.password
      && KLIPY_MEDIA_HOSTS.has(url.hostname)
      ? url.toString()
      : null
  } catch {
    return null
  }
}

function positiveInteger(value) {
  const number = Number(value)

  return Number.isInteger(number) && number > 0 ? number : null
}
