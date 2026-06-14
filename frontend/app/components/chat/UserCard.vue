<script setup lang="js">
import { computed } from 'vue'
import { storeToRefs } from 'pinia'

const auth = useAuthStore()
const socket = useSocketStore()
const { user } = storeToRefs(auth)
const { status } = storeToRefs(socket)
const { disconnect } = socket

const displayName = computed(() => user.value?.name ?? '')
const handle = computed(() => user.value?.email ?? '')
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

function initials(name) {
  if (!name) return '?'
  return name
    .split(' ')
    .map((part) => part[0])
    .filter(Boolean)
    .join('')
    .slice(0, 2)
    .toUpperCase()
}

async function handleLogout() {
  disconnect()
  await auth.logout()

  await navigateTo('/')
}

const menuItems = computed(() => [
  [
    {
      label: 'Profile',
      icon: 'i-lucide-user',
    },
    {
      label: 'Settings',
      icon: 'i-lucide-settings',
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
    <div class="flex min-w-0 items-center gap-3">
      <span
        class="relative grid size-11 shrink-0 place-items-center rounded-2xl bg-linear-to-br from-orange-400 to-orange-600 text-sm font-black text-white"
      >
        {{ initials(displayName) }}
        <span class="absolute bottom-0 right-0 size-3 rounded-full bg-emerald-400 ring-2 ring-slate-900" />
      </span>
      <span class="min-w-0">
        <span class="block truncate text-sm font-black text-white">{{ displayName || 'Guest' }}</span>
        <span v-if="handle" class="block truncate text-xs text-slate-500">{{ handle }}</span>
      </span>
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
