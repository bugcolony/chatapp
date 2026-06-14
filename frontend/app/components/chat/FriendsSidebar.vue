<script setup lang="js">
import { computed } from 'vue'
import { storeToRefs } from 'pinia'
import ChatSidebar from '~/components/chat/ChatSidebar.vue'
import UserCard from '~/components/chat/UserCard.vue'
import { useChatUIStore } from '~/stores/chatUIStore.js'

const store = useServerStore()
const uiStore = useChatUIStore()
const { friends } = storeToRefs(store)
const { rightSidebarOpen } = storeToRefs(uiStore)

const onlineCount = computed(() => friends.value.filter((f) => f.online).length)

function initials(name) {
  return name
    .split(' ')
    .map((part) => part[0])
    .join('')
    .slice(0, 2)
    .toUpperCase()
}

function avatarStyle(color) {
  return {
    background: 'linear-gradient(135deg, ' + color + ', color-mix(in srgb, ' + color + ' 42%, white))',
  }
}
</script>

<template>
  <ChatSidebar
    v-model:open="rightSidebarOpen"
    side="right"
    width="360px"
  >
    <aside class="flex h-full min-h-0 flex-col">
      <UserCard />

      <div class="min-h-0 flex-1 overflow-y-auto p-3 pt-2">
        <div class="mb-4 flex items-end justify-between gap-3">
          <div class="min-w-0">
            <p class="text-xs font-bold uppercase tracking-[0.24em] text-orange-200/65">
              Friends
            </p>
            <h2 class="mt-1 truncate text-xl font-black text-white">
              {{ friends.length }} total
            </h2>
          </div>
          <UBadge
            variant="soft"
            color="neutral"
            class="rounded-full border border-white/10 bg-white/8 px-2.5 py-1 text-xs font-semibold text-slate-200"
          >
            {{ onlineCount }} online
          </UBadge>
        </div>

        <div class="space-y-2">
          <UButton
            v-for="friend in friends"
            :key="friend.name"
            block
            color="neutral"
            variant="ghost"
            class="flex w-full items-center gap-3 rounded-2xl px-3 py-3 text-left transition hover:bg-white/6"
            :class="friend.online ? 'text-white' : 'text-slate-500'"
            :ui="{ base: 'justify-start' }"
          >
            <span
              class="relative grid size-10 shrink-0 place-items-center rounded-2xl text-xs font-black text-white"
              :class="friend.online ? '' : 'opacity-50 grayscale'"
              :style="avatarStyle(friend.color)"
            >
              {{ initials(friend.name) }}
              <span
                class="absolute bottom-0 right-0 size-2.5 rounded-full ring-2 ring-slate-900"
                :class="friend.online ? 'bg-emerald-400' : 'bg-slate-600'"
              />
            </span>
            <span class="min-w-0 flex-1">
              <span class="flex items-center gap-2">
                <span class="truncate text-sm font-bold">{{ friend.name }}</span>
                <span class="rounded-full bg-white/8 px-2 py-0.5 text-xs font-bold text-slate-400">
                  {{ friend.handle }}
                </span>
              </span>
              <span class="block truncate text-xs text-slate-500">{{ friend.status }}</span>
            </span>
          </UButton>
        </div>
      </div>
    </aside>
  </ChatSidebar>
</template>