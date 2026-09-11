<script setup lang="js">
import { computed, ref } from 'vue'
import { storeToRefs } from 'pinia'
import CurrentUserCard from '~/components/account/CurrentUserCard.vue'
import AppSidebar from '~/components/layout/AppSidebar.vue'
import { useChatUIStore } from '~/stores/chatUIStore.js'
import { userAvatarSrc } from '~/composables/useServerAvatar.js'

const store = useServerStore()
const uiStore = useChatUIStore()
const toast = useToast()
const { friends, incomingFriendRequests } = storeToRefs(store)
const { rightSidebarOpen } = storeToRefs(uiStore)

const tab = ref('friends')
const adding = ref(false)
const username = ref('')
const addError = ref('')
const sending = ref(false)

const tabs = computed(() => [
  { label: 'Friends', icon: 'i-lucide-users', value: 'friends' },
  { label: 'Invites', icon: 'i-lucide-user-round-arrow-left', value: 'invites', badge: incomingFriendRequests.value.length || undefined },
])

const list = computed(() => (tab.value === 'friends' ? friends.value : incomingFriendRequests.value))

function toggleAdd() {
  adding.value = !adding.value
  addError.value = ''
}

async function sendRequest() {
  if (!username.value.trim() || sending.value) return

  sending.value = true
  addError.value = ''

  try {
    const res = await store.sendFriendRequest(username.value)

    toast.add({ title: res.message, color: 'success', icon: 'i-lucide-check' })
    username.value = ''
    adding.value = false
  } catch (err) {
    addError.value = err?.data?.errors?.username?.[0] ?? err?.data?.message ?? 'Could not send friend request.'
  } finally {
    sending.value = false
  }
}

async function accept(user) {
  try {
    await store.acceptFriendRequest(user.id)
  } catch {
    toast.add({ title: 'Could not accept request', color: 'error' })
  }
}

async function decline(user) {
  try {
    await store.removeFriend(user.id)
  } catch {
    toast.add({ title: 'Could not decline request', color: 'error' })
  }
}

async function unfriend(user) {
  try {
    await store.removeFriend(user.id)
  } catch {
    toast.add({ title: 'Could not remove friend', color: 'error' })
  }
}

async function block(user) {
  try {
    await store.blockFriend(user.id)
  } catch {
    toast.add({ title: 'Could not block user', color: 'error' })
  }
}

function friendMenuItems(user) {
  return [
    [
      { label: 'Unfriend', icon: 'i-lucide-user-minus', color: 'warning', onSelect: () => unfriend(user) },
      { label: 'Block', icon: 'i-lucide-ban', color: 'error', onSelect: () => block(user) },
    ],
  ]
}
</script>

<template>
  <AppSidebar
    v-model:open="rightSidebarOpen"
    side="right"
    width="360px"
  >
    <aside class="flex h-full min-h-0 flex-col">
      <CurrentUserCard />

      <div class="min-h-0 flex-1 overflow-y-auto p-3 pt-2">
        <div class="mb-3 flex items-end justify-between gap-3">
          <div class="min-w-0">
            <h2 class="text-xs font-bold uppercase tracking-[0.24em] text-orange-200/65">
              Friends
            </h2>
          </div>
        </div>

        <div class="flex flex-col gap-5">
          <div class="w-full flex gap-3 items-center">
            <UTabs
                v-model="tab"
                :items="tabs"
                :content="false"
                color="neutral"
                size="sm"
                class="flex-1"
            />

            <UButton
                icon="i-lucide-user-plus"
                color="neutral"
                variant="soft"
                :class="{'ring-2 ring-indigo-500' : adding}"
                @click="toggleAdd"
            />
          </div>

          <form
              v-if="adding"
              @submit.prevent="sendRequest"
          >
            <div class="flex gap-2">
              <UInput
                  v-model="username"
                  placeholder="Username"
                  autofocus
                  class="flex-1"
                  :color="addError ? 'error' : 'neutral'"
                  :highlight="!!addError"
              />
              <UButton
                  type="submit"
                  label="Send"
                  :loading="sending"
                  :disabled="!username.trim()"
              />

            </div>
            <p
                v-if="addError"
                class="mt-1 px-1 text-xs text-error"
            >
              {{ addError }}
            </p>

            <USeparator class="my-5" />
          </form>
        </div>

        <p
          v-if="!list.length"
          class="px-2 py-6 text-center text-sm text-slate-500"
        >
          {{ tab === 'friends' ? 'No friends yet.' : 'No incoming friend requests.' }}
        </p>

        <div class="space-y-1">
          <UContextMenu
            v-for="user in list"
            :key="user.id"
            :items="friendMenuItems(user)"
            :disabled="tab !== 'friends'"
            :modal="false"
            :ui="{
              content: 'w-44 rounded-xl border border-white/10 bg-slate-950/95 shadow-2xl shadow-black/40 backdrop-blur-xl',
              item: 'rounded-lg',
            }"
          >
            <div class="flex items-center gap-3 rounded-2xl px-2 py-2 transition hover:bg-white/6">
              <UAvatar
                :src="userAvatarSrc(user)"
                size="lg"
              />
              <span class="min-w-0 flex-1">
                <span class="block truncate text-sm font-bold text-white">{{ user.name }}</span>
                <span class="block truncate text-xs text-slate-500">@{{ user.username }}</span>
              </span>
              <template v-if="tab === 'invites'">
                <UButton
                  icon="i-lucide-check"
                  color="success"
                  variant="soft"
                  size="sm"
                  @click="accept(user)"
                />
                <UButton
                  icon="i-lucide-x"
                  color="error"
                  variant="soft"
                  size="sm"
                  @click="decline(user)"
                />
              </template>
            </div>
          </UContextMenu>
        </div>
      </div>
    </aside>
  </AppSidebar>
</template>
