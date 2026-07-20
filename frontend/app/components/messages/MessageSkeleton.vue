<script setup lang="js">
const lineCount = ref(0)
const lineWidth = ['w-full', 'w-3/4', 'w-2/3', 'w-1/2', 'w-1/4', 'w-1/12', 'w-7/12']

onMounted(() => {
  lineCount.value = randomNumber(1, 5)
})

function randomNumber(min, max) {
  return Math.floor(Math.random() * (max - min) + min)
}
</script>

<template>
  <div class="flex gap-4 px-3 py-5">
    <USkeleton class="size-10 shrink-0 rounded-full" />

    <div class="grid gap-3 pt-1 w-full">
      <USkeleton v-for="n in lineCount" :key="n" class="h-3" :class="lineWidth[randomNumber(0, lineWidth.length)]"/>
    </div>
  </div>
</template>

<style scoped>

</style>