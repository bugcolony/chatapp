<script setup lang="js">
</script>

<template>
  <UApp>
    <NuxtLayout>
      <NuxtPage />
    </NuxtLayout>
  </UApp>
</template>

<style scoped>
.auth-boot-enter-active,
.auth-boot-leave-active {
  transition: opacity 0.18s ease;
}

.auth-boot-enter-from,
.auth-boot-leave-to {
  opacity: 0;
}
</style>
