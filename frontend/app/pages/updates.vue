<script setup lang="js">
import ReleaseNotes from '~/components/releases/ReleaseNotes.vue'
import { useReleaseNotes } from '~/composables/useReleaseNotes.js'

definePageMeta({
  title: "What's New",
})

useSeoMeta({
  title: "What's New · Chat App",
  description: 'Product updates, improvements, and fixes from Chat App.',
})

const releases = useReleaseNotes()
const latestRelease = computed(() => releases.value[0] ?? null)
</script>

<template>
  <div class="min-h-screen bg-default text-default">
    <header class="border-b border-muted bg-muted/30">
      <UContainer class="py-14 sm:py-18">
        <div class="max-w-3xl">
          <div class="flex items-center gap-2 text-sm font-semibold text-primary">
            <UIcon name="i-lucide-newspaper" class="size-4" />
            <span>Product updates</span>
          </div>

          <h1 class="mt-4 text-4xl font-black tracking-tight text-highlighted sm:text-5xl">
            What’s new
          </h1>
          <p class="mt-4 max-w-2xl text-base leading-7 text-muted sm:text-lg">
            New features, useful improvements, and the occasional maintenance note—organized by release.
          </p>

          <div class="mt-6 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-muted">
            <span class="inline-flex items-center gap-2">
              <UIcon name="i-lucide-tag" class="size-4" />
              Latest {{ latestRelease?.version }}
            </span>
            <span class="inline-flex items-center gap-2">
              <UIcon name="i-lucide-layers-3" class="size-4" />
              {{ releases.length }} releases
            </span>
          </div>
        </div>

        <nav
          class="mt-10 flex gap-3 overflow-x-auto pb-2"
          aria-label="Jump to a release"
        >
          <a
            v-for="release in releases"
            :key="release.id"
            :href="`#release-${release.id}`"
            class="group min-w-44 rounded-xl border border-muted bg-default/70 px-4 py-3 transition hover:border-accented hover:bg-elevated"
          >
            <span class="block text-xs font-bold text-primary">
              {{ release.version }}
            </span>
            <span class="mt-1 block truncate text-sm font-semibold text-highlighted">
              {{ release.title }}
            </span>
          </a>
        </nav>
      </UContainer>
    </header>

    <main>
      <ReleaseNotes
        v-for="(release, index) in releases"
        :key="release.id"
        :release="release"
        :is-latest="index === 0"
      />
    </main>
  </div>
</template>
