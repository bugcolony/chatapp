<script setup lang="js">
import {
  computed,
  nextTick,
  onBeforeUnmount,
  onMounted,
  ref,
  shallowRef,
} from 'vue'
import { useInfiniteScroll, watchDebounced } from '@vueuse/core'
import { KLIPY_WEBSITE_URL } from '~/utils/klipyGif.js'

const emit = defineEmits(['select'])

const { fetchGifs, isConfigured } = useKlipyGifs()
const query = ref('')
const settledQuery = ref('')
const gifs = shallowRef([])
const loading = ref(false)
const loadingMore = ref(false)
const hasNext = ref(false)
const nextPage = ref(2)
const errorMessage = ref('')
const resultsViewport = useTemplateRef('resultsViewport')

const assistiveStatus = computed(() => {
  if (!isConfigured) return 'GIFs are unavailable right now.'
  if (loading.value) return 'Loading GIFs.'
  if (errorMessage.value) return errorMessage.value
  if (!gifs.value.length) return 'No GIFs found.'

  return `${gifs.value.length} GIFs loaded.`
})

let requestVersion = 0
let requestController = null

const { reset: resetInfiniteScroll } = useInfiniteScroll(
  resultsViewport,
  loadMore,
  {
    distance: 160,
    canLoadMore: () =>
      isConfigured
      && hasNext.value
      && !loading.value
      && !loadingMore.value
      && !errorMessage.value,
  },
)

watchDebounced(
  query,
  loadFirstPage,
  {
    debounce: 300,
    maxWait: 800,
  },
)

onMounted(() => {
  if (isConfigured) {
    loadFirstPage()
  }
})

onBeforeUnmount(() => {
  requestVersion += 1
  requestController?.abort()
})

async function loadFirstPage() {
  if (!isConfigured) return

  const version = ++requestVersion
  const currentQuery = query.value.trim()

  requestController?.abort()
  requestController = new AbortController()
  loading.value = true
  loadingMore.value = false
  errorMessage.value = ''
  gifs.value = []
  hasNext.value = false

  try {
    const result = await fetchGifs({
      query: currentQuery,
      page: 1,
      signal: requestController.signal,
    })

    if (version !== requestVersion) return

    settledQuery.value = currentQuery
    gifs.value = result.gifs
    nextPage.value = result.currentPage + 1
    hasNext.value = result.hasNext

    await nextTick()
    resetInfiniteScroll()
  } catch (error) {
    if (version === requestVersion && error?.name !== 'AbortError') {
      errorMessage.value = describeRequestError(error)
    }
  } finally {
    if (version === requestVersion) {
      loading.value = false
    }
  }
}

async function loadMore() {
  if (
    !isConfigured
    || loading.value
    || loadingMore.value
    || !hasNext.value
  ) {
    return
  }

  const version = requestVersion
  const currentQuery = settledQuery.value
  const page = nextPage.value

  requestController = new AbortController()
  loadingMore.value = true

  try {
    const result = await fetchGifs({
      query: currentQuery,
      page,
      signal: requestController.signal,
    })

    if (version !== requestVersion) return

    const existingIds = new Set(gifs.value.map(gif => gif.id))

    gifs.value = [
      ...gifs.value,
      ...result.gifs.filter(gif => !existingIds.has(gif.id)),
    ]
    nextPage.value = result.currentPage + 1
    hasNext.value = result.hasNext
  } catch (error) {
    if (version === requestVersion && error?.name !== 'AbortError') {
      errorMessage.value = describeRequestError(error)
    }
  } finally {
    if (version === requestVersion) {
      loadingMore.value = false
    }
  }
}

function selectGif(gif) {
  emit('select', gif)
}

function retryMore() {
  errorMessage.value = ''
  loadMore()
}

function describeRequestError(error) {
  const status = error?.response?.status ?? error?.statusCode

  if (
    status === 401
    || status === 403
    || status === 404
  ) {
    return 'GIFs are unavailable right now. Please try again later.'
  }

  if (status === 429) {
    return 'Too many GIF searches right now. Please try again in a moment.'
  }

  return 'GIFs could not be loaded. Check your connection and try again.'
}
</script>

<template>
  <section
    class="flex h-[min(32rem,calc(100vh-5rem))] min-h-80 w-[min(28rem,calc(100vw-1rem))] flex-col overflow-hidden rounded-2xl bg-elevated"
    aria-label="KLIPY GIF picker"
  >
    <header class="border-b border-muted bg-default/35 p-3">
      <div class="flex items-center gap-2">
        <UInput
          v-model="query"
          icon="i-lucide-search"
          placeholder="Search KLIPY"
          aria-label="Search GIFs"
          autocomplete="off"
          autofocus
          size="lg"
          class="min-w-0 flex-1"
          :disabled="!isConfigured"
          :ui="{
            base: 'rounded-xl bg-elevated',
            leadingIcon: 'text-muted',
          }"
        />

        <UButton
          v-if="query"
          type="button"
          icon="i-lucide-x"
          color="neutral"
          variant="ghost"
          square
          aria-label="Clear GIF search"
          class="rounded-xl text-muted"
          @click="query = ''"
        />
      </div>

      <div class="mt-2 flex items-center justify-end gap-3 px-0.5">
        <a
          :href="KLIPY_WEBSITE_URL"
          target="_blank"
          rel="noopener noreferrer"
          class="shrink-0 opacity-65 transition hover:opacity-100"
          aria-label="Powered by KLIPY"
        >
          <img
            src="/images/powered-by-klipy.svg"
            alt="Powered by KLIPY"
            class="h-3.5 w-auto invert dark:invert-0"
          >
        </a>
      </div>
    </header>

    <p class="sr-only" role="status" aria-live="polite">
      {{ assistiveStatus }}
    </p>

    <div
      ref="resultsViewport"
      class="min-h-0 flex-1 overflow-y-auto p-2"
      :aria-busy="loading || loadingMore"
    >
      <div
        v-if="!isConfigured"
        class="grid min-h-full place-items-center px-8 py-10 text-center"
      >
        <div>
          <div class="mx-auto grid size-11 place-items-center rounded-2xl bg-accented text-muted">
            <UIcon name="i-lucide-image-off" class="size-5" />
          </div>
          <p class="mt-3 text-sm font-semibold text-highlighted">
            GIFs are unavailable
          </p>
          <p class="mt-1 text-xs leading-5 text-muted">
            This feature can’t be used right now. Try again later.
          </p>
        </div>
      </div>

      <div v-else-if="loading" class="columns-2 gap-2" aria-label="Loading GIFs">
        <USkeleton
          v-for="index in 10"
          :key="index"
          class="mb-2 w-full break-inside-avoid rounded-xl"
          :class="index % 3 === 0 ? 'h-36' : index % 2 === 0 ? 'h-28' : 'h-24'"
        />
      </div>

      <div
        v-else-if="errorMessage && !gifs.length"
        class="grid min-h-full place-items-center px-8 py-10 text-center"
      >
        <div>
          <div class="mx-auto grid size-11 place-items-center rounded-2xl bg-error/10 text-error">
            <UIcon name="i-lucide-wifi-off" class="size-5" />
          </div>
          <p class="mt-3 text-sm font-semibold text-highlighted">
            Couldn’t load GIFs
          </p>
          <p class="mt-1 text-xs leading-5 text-muted">
            {{ errorMessage }}
          </p>
          <UButton
            type="button"
            label="Try again"
            icon="i-lucide-refresh-cw"
            color="neutral"
            variant="soft"
            size="sm"
            class="mt-4 rounded-lg"
            @click="loadFirstPage"
          />
        </div>
      </div>

      <div
        v-else-if="!gifs.length"
        class="grid min-h-full place-items-center px-8 py-10 text-center"
      >
        <div>
          <div class="mx-auto grid size-11 place-items-center rounded-2xl bg-accented text-muted">
            <UIcon name="i-lucide-search-x" class="size-5" />
          </div>
          <p class="mt-3 text-sm font-semibold text-highlighted">
            No GIFs found
          </p>
          <p class="mt-1 text-xs leading-5 text-muted">
            Try a broader search or a different phrase.
          </p>
        </div>
      </div>

      <template v-else>
        <div class="columns-2 gap-2">
          <button
            v-for="gif in gifs"
            :key="gif.id"
            type="button"
            class="group relative mb-2 block w-full break-inside-avoid overflow-hidden rounded-xl border border-transparent bg-muted text-left shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-accented hover:shadow-lg focus-visible:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/35 motion-reduce:transform-none"
            :aria-label="`Select ${gif.title}`"
            @click="selectGif(gif)"
          >
            <img
              :src="gif.previewUrl"
              :alt="gif.title"
              loading="lazy"
              decoding="async"
              referrerpolicy="no-referrer"
              class="block h-auto w-full bg-muted object-cover"
              :style="{ aspectRatio: `${gif.width} / ${gif.height}` }"
            >

            <span class="absolute inset-x-0 bottom-0 translate-y-full bg-linear-to-t from-black/85 via-black/55 to-transparent px-2.5 pb-2 pt-7 text-[11px] font-semibold leading-4 text-white transition-transform group-hover:translate-y-0 group-focus-visible:translate-y-0">
              {{ gif.title }}
            </span>
          </button>
        </div>

        <div
          v-if="loadingMore"
          class="flex items-center justify-center gap-2 py-4 text-xs text-muted"
        >
          <UIcon name="i-lucide-loader-circle" class="size-4 animate-spin" />
          Loading more…
        </div>

        <div
          v-else-if="errorMessage"
          class="m-2 rounded-xl border border-error/20 bg-error/5 p-3 text-center"
        >
          <p class="text-xs text-muted">
            {{ errorMessage }}
          </p>
          <UButton
            type="button"
            label="Retry"
            color="neutral"
            variant="link"
            size="xs"
            class="mt-1"
            @click="retryMore"
          />
        </div>
      </template>
    </div>
  </section>
</template>
