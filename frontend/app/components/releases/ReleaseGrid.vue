<script setup lang="js">
const props = defineProps({
  data: {
    type: Object,
    required: true,
  },
})

const gridColumnClasses = {
  1: 'grid-cols-1',
  2: 'grid-cols-1 md:grid-cols-2',
  3: 'grid-cols-1 sm:grid-cols-2 xl:grid-cols-3',
  4: 'grid-cols-1 sm:grid-cols-2 xl:grid-cols-4',
}

const columnClass = computed(() =>
  gridColumnClasses[props.data.cols ?? 2] ?? gridColumnClasses[2],
)

function itemImage(item) {
  if (item.image) return item.image
  if (!item.img) return null

  return {
    src: item.img,
    alt: `${item.title} screenshot`,
  }
}
</script>

<template>
  <section>
    <header class="mb-5 max-w-2xl">
      <h3 class="text-xl font-bold text-highlighted">
        {{ data.title ?? 'Highlights' }}
      </h3>
      <p v-if="data.description" class="mt-2 text-sm leading-6 text-muted">
        {{ data.description }}
      </p>
    </header>

    <div class="grid gap-x-5 gap-y-8" :class="columnClass">
      <article
        v-for="item in data.items"
        :key="item.title"
        class="min-w-0"
        :class="itemImage(item) ? '' : 'rounded-xl border border-muted bg-muted/25 p-5'"
      >
        <template v-if="itemImage(item)">
          <img
            :src="itemImage(item).src"
            :alt="itemImage(item).alt"
            loading="lazy"
            class="block w-full object-cover rounded-xl border-3 border-indigo-300"
          >
          <div class="pt-4">
            <h4 class="font-bold text-highlighted">
              {{ item.title }}
            </h4>
            <p class="mt-1.5 text-sm leading-6 text-muted">
              {{ item.description }}
            </p>
          </div>
        </template>

        <template v-else>
          <span
            v-if="item.icon"
            class="mb-4 grid size-10 place-items-center rounded-xl bg-primary/10 text-primary"
          >
            <UIcon :name="item.icon" class="size-5" />
          </span>
          <h4 class="font-bold text-highlighted">
            {{ item.title }}
          </h4>
          <p class="mt-2 text-sm leading-6 text-muted">
            {{ item.description }}
          </p>
        </template>
      </article>
    </div>
  </section>
</template>
