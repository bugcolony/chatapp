<script setup lang="js">
const props = defineProps({
  track: {
    type: Object,
    required: true
  }
})

const el = useTemplateRef('el')

onMounted(() => {
  props.track.t.attach(el.value)
})

onUnmounted(() => {
  props.track.t.detach(el.value)
})
</script>

<template>
  <video
      ref="el"
      class="w-full"
      autoplay
      playsinline
      muted
  />
</template>

<style scoped lang="scss">

</style>