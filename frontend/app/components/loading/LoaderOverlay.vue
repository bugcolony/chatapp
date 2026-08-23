<script setup lang="js">
defineProps({
  loading: {
    type: Boolean,
    default: false
  }
})
</script>

<template>
  <div class="relative">
    <div
        :class="[
        loading ? 'opacity-5 pointer-events-none select-none' : '',
        'transition-opacity duration-200'
      ]"
    >
      <slot />
    </div>

    <div
        v-if="loading"
        class="absolute inset-0 z-10 flex items-center justify-center"
    >
      <UIcon
          name="i-lucide-loader-2"
          class="size-4 animate-spin"
      />
    </div>
  </div>
</template>