<script setup lang="js">
import ServerInvitePanel from "~/components/invites/ServerInvitePanel.vue";
import {usePendingInvite} from "~/composables/usePendingInvite.js";
import {until} from "@vueuse/core";

definePageMeta({
  layout: 'naked',
  title: 'Server invite',
})

const routeCode = useRoute().params.code ?? ''
const auth = useAuthStore()
const {code,queue} = usePendingInvite()

onMounted(async () => {
  queue(routeCode)

  await until(() => auth.isResolved).toBe(true)

  if (auth.isAuthenticated) {
    await navigateTo('/app')
  }
})

</script>

<template>
  <main class="relative flex min-h-screen items-center justify-center overflow-hidden text-slate-100">
    <ServerInvitePanel
        :code="code"
        class="rounded-4xl border border-white/10 shadow-2xl shadow-black/35"
    />
  </main>
</template>

<style scoped></style>
