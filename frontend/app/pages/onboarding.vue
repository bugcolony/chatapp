<script setup lang="js">
import z from "zod";

definePageMeta({
  middleware: ["auth"],
  title: "Pick a username",
  layout: "naked",
});

const auth = useAuthStore();

const backgroundStyle = {
  backgroundImage: 'url("/images/bgpatt.webp")',
  backgroundSize: "auto 500px",
};

const schema = z.object({
  username: z
    .string()
    .min(3, "At least 3 characters")
    .max(32, "At most 32 characters")
    .regex(
      /^[a-z0-9._]+$/,
      "Only lowercase letters, numbers, dots and underscores",
    ),
});

const state = reactive({ username: "" });
const loading = ref(false);
const form = useTemplateRef("form");

function normalize(value) {
  state.username = (value ?? "").toLowerCase().trim();
}

async function onSubmit(payload) {
  loading.value = true;

  try {
    await auth.completeOnboarding(payload.data.username);
    await navigateTo("/app", { replace: true });
  } catch (err) {
    if (err.response?.status === 409) {
      auth.setAuthenticated(await auth.fetchUser());
      await navigateTo("/app", { replace: true });

      return;
    }

    const errors = err.response?._data?.errors;

    form.value?.setErrors(
      errors
        ? Object.entries(errors).map(([name, messages]) => ({
            name,
            message: messages[0],
          }))
        : [{ name: "username", message: "Could not save that username" }],
    );
  } finally {
    loading.value = false;
  }
}

async function onLogout() {
  await auth.logout();
  await navigateTo("/login", { replace: true });
}
</script>

<template>
  <div
    :style="backgroundStyle"
    class="h-screen w-full overflow-hidden bg-repeat flex items-center justify-center p-6"
  >
    <div
      class="w-full max-w-md rounded-2xl border border-gray-200 bg-white/90 p-8 shadow-xl backdrop-blur-sm dark:border-gray-800 dark:bg-gray-900/90"
    >
      <p class="text-sm text-gray-500 dark:text-gray-400">One last step</p>
      <h1 class="mt-1 text-2xl font-semibold">Pick a username</h1>
      <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
        Your username identifies you across the app.
      </p>

      <UForm
        ref="form"
        :schema="schema"
        :state="state"
        class="mt-6 space-y-4"
        @submit="onSubmit"
      >
        <UFormField label="Username" name="username">
          <UInput
            v-model="state.username"
            autofocus
            autocomplete="off"
            class="w-full"
            @update:model-value="normalize"
          />
        </UFormField>

        <UButton type="submit" block :loading="loading" label="Continue" />
      </UForm>

      <UButton
        class="mt-4"
        color="neutral"
        variant="link"
        size="sm"
        label="Log out"
        @click="onLogout"
      />
    </div>
  </div>
</template>

<style scoped></style>
