<script setup lang="js">
import ReleaseArticle from '~/components/releases/ReleaseArticle.vue'
import ReleaseChangelist from '~/components/releases/ReleaseChangelist.vue'
import ReleaseGrid from '~/components/releases/ReleaseGrid.vue'

const sections = {
  ReleaseArticle,
  ReleaseChangelist,
  ReleaseGrid,
}

const props = defineProps({
  release: {
    type: Object,
    required: true,
  },
  isLatest: {
    type: Boolean,
    default: false,
  },
})

const formattedDate = computed(() => {
  const value = new Date(`${props.release.publishedAt}T00:00:00Z`)

  return new Intl.DateTimeFormat('en', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    timeZone: 'UTC',
  }).format(value)
})

const cover = computed(() => {
  if (props.release.cover) return props.release.cover
  if (!props.release.img) return null

  return {
    src: props.release.img,
    alt: `${props.release.title} screenshot`,
  }
})

const visibleSections = computed(() =>
  (props.release.sections ?? []).filter(section => sections[section.component]),
)
</script>

<template>
  <article
    :id="`release-${release.id}`"
    class="scroll-mt-20 py-12 even:bg-muted/30 sm:py-16"
  >
    <UContainer class="grid gap-7 lg:grid-cols-[9rem_minmax(0,1fr)] lg:gap-12">
      <aside class="lg:pt-1">
        <div class="flex flex-wrap items-center gap-2 lg:sticky lg:top-8 lg:block">
          <UBadge
            :label="release.version"
            :color="isLatest ? 'primary' : 'neutral'"
            :variant="isLatest ? 'soft' : 'subtle'"
            size="md"
            class="font-bold"
          />
          <UBadge
            v-if="isLatest"
            label="Latest"
            color="success"
            variant="soft"
            size="sm"
            class="lg:mt-2 lg:flex lg:w-fit"
          />
          <time
            :datetime="release.publishedAt"
            class="text-sm text-muted lg:mt-4 lg:block"
          >
            {{ formattedDate }}
          </time>
        </div>
      </aside>

      <div class="min-w-0 max-w-4xl">
        <header>
          <h2 class="text-3xl font-black tracking-tight uppercase sm:text-4xl text-indigo-300">
            {{ release.title }}
          </h2>

          <img
            v-if="cover"
            :src="cover.src"
            :alt="cover.alt"
            :loading="isLatest ? 'eager' : 'lazy'"
            class="mt-8 max-h-160 w-full object-contain"
          >

          <p
            class="max-w-3xl text-base leading-7 text-muted sm:text-lg"
            :class="cover ? 'mt-6' : 'mt-4'"
          >
            {{ release.description }}
          </p>
        </header>

        <div class="mt-10 space-y-10">
          <component
            :is="sections[section.component]"
            v-for="(section, index) in visibleSections"
            :key="`${section.component}-${index}`"
            :data="section.data"
          />
        </div>
      </div>
    </UContainer>
  </article>
</template>
