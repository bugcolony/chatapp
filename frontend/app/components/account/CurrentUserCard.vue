<script setup lang="js">
import { computed } from 'vue'
import { storeToRefs } from 'pinia'
import {useSettingsModal} from "~/composables/useSettingsModal.js";
import {userAvatarSrc} from "~/composables/useServerAvatar.js";

const auth = useAuthStore()
const socket = useSocketStore()
const { user } = storeToRefs(auth)
const { status } = storeToRefs(socket)
const { disconnect } = socket
const {openSettingsModal} = useSettingsModal()

const displayName = computed(() => user.value?.name ?? '')
const connectionIndicator = computed(() => {
  switch (status.value) {
    case 'OPEN':
      return {
        icon: 'i-lucide-wifi',
        label: 'Connected',
        class: 'text-white',
      }
    case 'CONNECTING':
      return {
        icon: 'i-lucide-wifi',
        label: 'Connecting',
        class: 'animate-pulse text-warning',
      }
    default:
      return {
        icon: 'i-lucide-wifi-off',
        label: 'Disconnected',
        class: 'text-error',
      }
  }
})

async function handleLogout() {
  disconnect()
  await auth.logout()

  await navigateTo('/')
}

function handleSettings() {
  openSettingsModal()
}

function chipStatusColor(status) {
  switch (status) {
    case 'online':
      return 'bg-green-400'
    case 'away':
      return 'bg-amber-300'
    default:
      return 'bg-slate-700'
  }
}

const menuItems = computed(() => [
  [
    {
      label: 'Settings',
      icon: 'i-lucide-settings',
      onSelect: handleSettings
    },
  ],
  [
    {
      label: 'Logout',
      icon: 'i-lucide-log-out',
      color: 'error',
      onSelect: handleLogout,
    },
  ],
])
</script>

<template>
  <div class="flex items-center justify-between gap-3 border-b border-white/8 p-3">
    <div class="flex justify-center items-center gap-2">
      <UAvatar
          :src="userAvatarSrc(user)"
          size="lg"
      />
      <div class="min-w-0 flex-1">
        <div class="flex items-center gap-2">
          <span class="truncate text-sm font-bold" :class="{'text-white': user?.status === 'online'}">{{ displayName }}</span>
        </div>
        <div class="flex gap-1">
          <UChip standalone inset class="" :ui="{base: chipStatusColor(user?.status)}"  />
          <span class="block truncate text-xs text-slate-500">{{ user?.status }}</span>
        </div>
      </div>
    </div>


    <div class="flex shrink-0 items-start gap-1">
      <UDropdownMenu
        :items="menuItems"
        :content="{ align: 'end', side: 'bottom', sideOffset: 8 }"
        :ui="{
          content: 'w-48 rounded-xl border border-white/10 bg-slate-950/95 shadow-2xl shadow-black/40 backdrop-blur-xl',
        }"
      >
        <UButton
          icon="i-lucide-chevron-down"
          color="neutral"
          variant="ghost"
          aria-label="User menu"
          class="rounded-2xl bg-white/8 text-white hover:bg-white/12"
        />
      </UDropdownMenu>

      <span
        class="grid size-8 place-items-center rounded-xl bg-white/8"
        :aria-label="connectionIndicator.label"
        :title="connectionIndicator.label"
        role="status"
      >
        <UIcon
          :name="connectionIndicator.icon"
          class="size-4"
          :class="connectionIndicator.class"
        />
      </span>
    </div>
  </div>
</template>
