<script setup lang="js">
import { storeToRefs } from 'pinia'

const auth = useAuthStore()
const { user } = storeToRefs(auth)
const socket = useSocketStore()
const voice = useVoiceStore()
const { openDeleteAccountModal } = useDeleteAccountModal()

const fields = computed(() => [
  { key: 'username', label: 'Username', value: user.value?.username ?? '' },
  { key: 'email', label: 'Email', value: user.value?.email ?? '' },
])

const isDemo = computed(() => Boolean(user.value?.is_demo))

async function confirmDeletion() {
  if (isDemo.value) {
    return
  }

  const deleted = await openDeleteAccountModal(
    user.value?.username ?? null,
    user.value?.owned_servers_count ?? 0,
  )

  if (!deleted) {
    return
  }

  await voice.disconnectAll()
  socket.disconnect()

  reloadNuxtApp({ path: '/', ttl: 0 })
}
</script>

<template>
  <div>
    <h1 class="text-xl font-black tracking-tight text-highlighted">Account</h1>

    <UCard class="mt-8" :ui="{ body: 'p-0 divide-y divide-default' }">
      <div v-for="field in fields" :key="field.key" class="p-5">
        <p class="text-xs font-bold uppercase tracking-[0.18em] text-muted">
          {{ field.label }}
        </p>
        <p
          class="mt-1 truncate text-sm"
          :class="field.value ? 'font-bold text-highlighted' : 'text-dimmed'"
        >
          {{ field.value || '—' }}
        </p>
      </div>
    </UCard>

    <section class="mt-10">
      <h2 class="text-xs font-bold uppercase tracking-[0.18em] text-error">
        Danger zone
      </h2>

      <div class="mt-3 flex flex-wrap items-center justify-between gap-4 rounded-2xl p-5 ring ring-error/25">
        <div class="min-w-0">
          <p class="text-sm font-bold text-highlighted">Delete account</p>
          <p class="mt-1 text-sm text-muted">
            {{
              isDemo
                ? 'Demo accounts are shared with everyone, so they cannot be deleted.'
                : 'Closes your account for good. Your messages stay, shown as Deleted User.'
            }}
          </p>
        </div>

        <UButton
          type="button"
          label="Delete account"
          icon="i-lucide-trash-2"
          color="error"
          variant="outline"
          class="shrink-0 font-bold"
          :disabled="isDemo"
          @click="confirmDeletion"
        />
      </div>
    </section>
  </div>
</template>
