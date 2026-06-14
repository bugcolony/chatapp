<script setup lang="js">
import { until } from "@vueuse/core";

definePageMeta({
  layout: "naked",
});

const authStore = useAuthStore();
const state = ref("loading");
const title = ref("Processing...");
const eyebrow = ref("Please wait while we process your authentication.");
const message = ref("Stuff happening");

onMounted(() => {
  (async () => {
    await until(() => authStore.isResolved).toBe(true);

    if (authStore.isAuthenticated) {
      await navigateTo("/app");
    } else {
      await navigateTo("/login");
    }
  })();
});
</script>

<template>
  <AppLoadingScreen panel-class="max-w-lg">
    <template #icon>
      <div
        class="flex h-14 w-14 items-center justify-center rounded-2xl"
        :class="
          state === 'loading'
            ? 'bg-emerald-100 text-emerald-600'
            : 'bg-rose-100 text-rose-600'
        "
      >
        <UIcon
          :name="
            state === 'loading'
              ? 'i-heroicons-arrow-path'
              : 'i-heroicons-exclamation-triangle'
          "
          class="h-7 w-7"
          :class="state === 'loading' ? 'animate-spin' : ''"
        />
      </div>
    </template>

    <template #eyebrow>
      {{ eyebrow }}
    </template>

    <template #title>
      {{ title }}
    </template>

    {{ message }}

    <template #progress>
      <div
        v-if="state === 'loading'"
        class="h-2 w-1/3 rounded-full bg-emerald-500 animate-pulse"
      />
      <div v-else class="h-2 w-full rounded-full bg-rose-500/80" />
    </template>

    <template v-if="state === 'error'" #actions>
      <UButton
        label="Back to login"
        color="neutral"
        variant="soft"
        to="/login"
      />
    </template>
  </AppLoadingScreen>
</template>
