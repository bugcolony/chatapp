<script setup lang="js">
defineProps({
  href: {
    type: String,
    required: true,
  },
  name: {
    type: String,
    default: 'Attachment',
  },
})
</script>

<template>
  <a
    :href="href"
    target="_blank"
    rel="noopener noreferrer"
    class="mt-2 block w-fit max-w-full overflow-hidden rounded-xl border border-white/8 bg-white/4 transition hover:border-white/15 hover:bg-white/6"
    :aria-label="`Open ${name}`"
  >
    <img
      :src="href"
      :alt="name"
      loading="lazy"
      decoding="async"
      class="block max-h-80 max-w-full object-contain"
    >
  </a>
</template>
