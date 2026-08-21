<script setup lang="js">
const props = defineProps({
  track: {
    type: Object,
    required: true
  },
  title: {
    type: String,
    required: false
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
      class="w-full"
      ref="el"
      autoplay
      playsinline
      muted
  />
</template>

<style scoped lang="scss">

</style>