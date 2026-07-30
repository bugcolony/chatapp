import { useNavigatorLanguage } from '@vueuse/core'
import {
  createKlipyRequest,
  normalizeKlipyResponse,
} from '~/utils/klipyGif.js'

const TRENDING_CACHE_TTL = 5 * 60 * 1000
const REQUEST_TIMEOUT = 30_000
let trendingCache = null

export function useKlipyGifs() {
  const config = useRuntimeConfig()
  const apiKey = String(config.public.klipyApiKey ?? '').trim()
  const { language } = useNavigatorLanguage()
  const isConfigured = Boolean(apiKey)

  async function fetchGifs({
    query = '',
    page = 1,
    signal,
  } = {}) {
    const normalizedQuery = String(query).trim()
    const cachedTrending = !normalizedQuery && page === 1
      ? readTrendingCache(apiKey)
      : null

    if (cachedTrending) {
      return cachedTrending
    }

    const request = createKlipyRequest({
      apiKey,
      query: normalizedQuery,
      page,
      language: language.value,
    })
    const timedRequest = createTimedSignal(signal)

    try {
      const response = await $fetch(request.url, {
        signal: timedRequest.signal,
        query: request.query,
        retry: 0,
      })
      const result = normalizeKlipyResponse(response, page)

      if (!normalizedQuery && page === 1) {
        trendingCache = {
          apiKey,
          cachedAt: Date.now(),
          result,
        }
      }

      return result
    } finally {
      timedRequest.cleanup()
    }
  }

  return {
    fetchGifs,
    isConfigured,
  }
}

function readTrendingCache(apiKey) {
  if (
    !trendingCache
    || trendingCache.apiKey !== apiKey
    || Date.now() - trendingCache.cachedAt > TRENDING_CACHE_TTL
  ) {
    return null
  }

  return trendingCache.result
}

function createTimedSignal(parentSignal) {
  const controller = new AbortController()
  const abortFromParent = () => controller.abort(parentSignal?.reason)
  const timeoutId = setTimeout(() => {
    const error = new Error('KLIPY request timed out')
    error.name = 'TimeoutError'
    controller.abort(error)
  }, REQUEST_TIMEOUT)

  if (parentSignal?.aborted) {
    abortFromParent()
  } else {
    parentSignal?.addEventListener('abort', abortFromParent, { once: true })
  }

  return {
    signal: controller.signal,
    cleanup() {
      clearTimeout(timeoutId)
      parentSignal?.removeEventListener('abort', abortFromParent)
    },
  }
}
