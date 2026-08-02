<script setup lang="js">
defineProps({
  data: {
    type: Object,
    required: true,
  },
})
</script>

<template>
  <section>
    <h3 class="text-2xl font-bold tracking-tight text-highlighted">
      {{ data.title }}
    </h3>

    <img
      :src="data.image.src"
      :alt="data.image.alt"
      loading="lazy"
      class="mt-5 w-120 mx-auto rounded-xl border-3 border-indigo-300"
    >

    <p class="mt-5 max-w-3xl text-base leading-7 text-muted">
      {{ data.paragraph }}
    </p>
  </section>
</template>
