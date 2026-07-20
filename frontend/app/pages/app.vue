<script setup lang="js">
import {until} from '@vueuse/core';
import {useSocketStore} from "~/stores/socketStore.js";
import {usePendingInvite} from "~/composables/usePendingInvite.js";
import {useInviteOverlay} from "~/composables/useInviteOverlay.js";
import { useNotificationHub } from '~/composables/useNotificationHub.js'
import notificationSound from '~/assets/sounds/notif.mp3'

definePageMeta({
  middleware: ['auth'],
  title: 'Chat',
  layout: false,
})

const route = useRoute()
const store = useServerStore()
const auth = useAuthStore()
const {connect, disconnect} = useSocketStore()
const {openInvite} = useInviteOverlay()
const {code, clear} = usePendingInvite()
const notificationAudio = ref(null)
const {register: registerNotificationPlayer} = useNotificationHub()

const routeServerId = computed(() =>
    route.params.serverId ? Number(route.params.serverId) : null,
)
const routeChannelId = computed(() =>
    route.params.channelId ? Number(route.params.channelId) : null,
)

watchEffect(() => {
  store.activeServerId = routeServerId.value
  store.activeChannelId = routeChannelId.value
})

onMounted(async () => {
  registerNotificationPlayer(() => {
    if (navigator.userActivation && !navigator.userActivation.hasBeenActive) return

    notificationAudio.value.currentTime = 0
    void notificationAudio.value.play().catch(() => {})
  })

  await until(() => auth.isResolved).toBe(true)

  if (!auth.isAuthenticated) {
    await navigateTo('/login', {replace: true})

    return;
  }

  await store.fetchServers()
  await connect()

  handlePendingInvite()
})

onUnmounted(() => {
  registerNotificationPlayer(null)
  disconnect()
})

function handlePendingInvite() {
  if (code.value) {
    openInvite(code.value)
    clear()
  }
}

</script>

<template>
  <div class="chat-dashboard relative min-h-screen overflow-hidden bg-[#0b1114] text-slate-100 h-screen">
    <audio ref="notificationAudio" :src="notificationSound" preload="auto" />
    <div class="relative flex min-h-screen h-full">
      <NuxtPage/>
    </div>
  </div>
</template>

<style>
.chat-dashboard {
  font-family: "Avenir Next", "Trebuchet MS", "Segoe UI", sans-serif;
}

.chat-dashboard::before {
  content: "";
  position: absolute;
  inset: 0;
  background-image: url("/images/bgpatt.webp");
  opacity: 0.045;
  mix-blend-mode: screen;
  pointer-events: none;
}

.chat-panel {
  min-height: 0;
}

.chat-panel ::-webkit-scrollbar {
  width: 8px;
}

.chat-panel ::-webkit-scrollbar-track {
  background: transparent;
}

.chat-panel ::-webkit-scrollbar-thumb {
  border-radius: 999px;
  background: rgba(148, 163, 184, 0.22);
}

.chat-panel ::-webkit-scrollbar-thumb:hover {
  background: rgba(148, 163, 184, 0.36);
}
</style>
