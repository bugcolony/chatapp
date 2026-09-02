<script setup lang="js">
const props = defineProps({
  username: {
    type: String,
    default: null,
  },
  ownedServersCount: {
    type: Number,
    default: 0,
  },
})

const emit = defineEmits(['close'])

const auth = useAuthStore()

const typed = ref('')
const loading = ref(false)
const error = ref('')

const requiresConfirmation = computed(() => Boolean(props.username))

const canSubmit = computed(() => {
  if (!requiresConfirmation.value) return true

  return typed.value.trim().toLowerCase() === props.username.toLowerCase()
})

const ownedServersWarning = computed(() => {
  if (props.ownedServersCount === 0) return ''

  const noun = props.ownedServersCount === 1 ? 'server' : 'servers'

  return `You own ${props.ownedServersCount} ${noun}. They will stay up, owned by a deleted account.`
})

async function submit() {
  if (!canSubmit.value || loading.value) return

  error.value = ''
  loading.value = true

  try {
    await auth.deleteAccount(typed.value.trim().toLowerCase())

    emit('close', true)
  } catch (e) {
    error.value = e?.data?.message ?? 'Could not close your account. Try again.'
    loading.value = false
  }
}
</script>

<template>
  <UModal
    :dismissible="!loading"
    :ui="{
      content: 'max-w-md rounded-2xl bg-default shadow-2xl shadow-black/40 ring ring-default',
    }"
  >
    <template #content>
      <div class="p-6">
        <div class="flex items-start gap-4">
          <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-error/10 text-error">
            <UIcon name="i-lucide-triangle-alert" class="size-5" />
          </span>

          <div class="min-w-0">
            <h2 class="text-lg font-black tracking-tight text-highlighted">
              Delete your account?
            </h2>
            <p class="mt-1 text-sm leading-6 text-muted">
              Your account will be permanently closed and you will be signed out everywhere.
              Your messages stay visible, attributed to
              <span class="font-bold text-default">Deleted User</span>.
              This cannot be undone.
            </p>
            <p v-if="ownedServersWarning" class="mt-2 text-sm text-warning">
              {{ ownedServersWarning }}
            </p>
          </div>
        </div>

        <div v-if="requiresConfirmation" class="mt-5">
          <label for="delete-account-confirm" class="text-xs font-bold uppercase tracking-[0.18em] text-muted">
            Type <span class="text-default">{{ username }}</span> to confirm
          </label>
          <UInput
            id="delete-account-confirm"
            v-model="typed"
            class="mt-2 w-full"
            autocomplete="off"
            autocapitalize="off"
            spellcheck="false"
            :disabled="loading"
            @keydown.enter.prevent="submit"
          />
        </div>

        <p v-if="error" class="mt-3 text-sm font-bold text-error">
          {{ error }}
        </p>

        <div class="mt-6 flex justify-end gap-2">
          <UButton
            label="Cancel"
            color="neutral"
            variant="ghost"
            :disabled="loading"
            @click="emit('close', false)"
          />
          <UButton
            label="Delete account"
            icon="i-lucide-trash-2"
            color="error"
            :disabled="!canSubmit"
            :loading="loading"
            @click="submit"
          />
        </div>
      </div>
    </template>
  </UModal>
</template>
