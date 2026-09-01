<script setup lang="js">
import { storeToRefs } from 'pinia'

const auth = useAuthStore()
const { user } = storeToRefs(auth)

const fields = computed(() => [
  { key: 'username', label: 'Username', value: user.value?.username ?? '' },
  { key: 'email', label: 'Email', value: user.value?.email ?? '' },
])
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
  </div>
</template>
