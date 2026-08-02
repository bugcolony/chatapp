<script setup lang="js">
defineProps({
  data: {
    type: Object,
    required: true,
  },
})

const changeTypes = {
  add: {
    label: 'Added',
    icon: 'i-lucide-plus',
    class: 'text-success',
  },
  changed: {
    label: 'Changed',
    icon: 'i-lucide-pencil',
    class: 'text-warning',
  },
  fixed: {
    label: 'Fixed',
    icon: 'i-lucide-wrench',
    class: 'text-info',
  },
  removed: {
    label: 'Removed',
    icon: 'i-lucide-minus',
    class: 'text-error',
  },
}

function typeFor(item) {
  return changeTypes[item.type] ?? changeTypes.changed
}
</script>

<template>
  <section v-if="data.list?.length">
    <header class="mb-4 max-w-2xl">
      <h3 class="text-xl font-bold text-highlighted">
        {{ data.title ?? 'Release details' }}
      </h3>
      <p v-if="data.description" class="mt-2 text-sm leading-6 text-muted">
        {{ data.description }}
      </p>
    </header>

    <ul class="-mx-3 space-y-1" role="list">
      <li
        v-for="item in data.list"
        :key="item.description"
        class="grid gap-2 rounded-lg px-3 py-3 transition-colors hover:bg-muted/60 sm:grid-cols-[6.5rem_minmax(0,1fr)] sm:items-start sm:gap-4"
      >
        <span
          class="inline-flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide"
          :class="typeFor(item).class"
        >
          <UIcon :name="typeFor(item).icon" class="size-3.5 shrink-0" />
          {{ typeFor(item).label }}
        </span>
        <span class="text-sm leading-6 text-toned">
          {{ item.description }}
        </span>
      </li>
    </ul>
  </section>
</template>
