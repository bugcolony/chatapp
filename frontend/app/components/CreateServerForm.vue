<script setup lang="js">
import * as z from 'zod'

const emit = defineEmits(['success', 'cancel'])

const store = useServerStore()
const toast = useToast()
const {reconnect} = useSocketStore()

const schema = z.object({
  name: z.string().trim().min(3, 'At least 3 characters').max(255, 'At most 255 characters'),
})

const state = reactive({ name: '' })
const loading = ref(false)
const formError = ref('')
const formRef = ref(null)

async function onSubmit(event) {
  loading.value = true
  formError.value = ''

  try {
    const { $apiFetch } = useNuxtApp()
    const res = await $apiFetch('/servers', {
      method: 'POST',
      body: event.data,
    })

    const server = res.data

    store.addServer(server)

    toast.add({
      title: 'Server created',
      description: `"${server.name}" is ready.`,
      color: 'success',
      icon: 'i-lucide-check',
    })

    state.name = ''
    emit('success')

    await reconnect()
    await navigateTo(`/app/servers/${server.id}`)

  } catch (err) {
    console.error('Failed to create server:', err)
    const status = err?.response?.status ?? err?.statusCode

    if (status === 422 && err?.data?.errors) {
      formRef.value?.setErrors(
        Object.entries(err.data.errors).map(([name, messages]) => ({
          name,
          message: Array.isArray(messages) ? messages[0] : messages,
        })),
      )
      return
    }

    formError.value = err?.data?.message ?? 'Failed to create server. Try again.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <UForm
    ref="formRef"
    :schema="schema"
    :state="state"
    class="space-y-4"
    @submit="onSubmit"
  >
    <UFormField
      label="Server name"
      name="name"
      required
      hint="3–255 characters"
      :ui="{ label: 'text-xs font-bold uppercase tracking-[0.18em] text-slate-400', hint: 'text-xs text-slate-500' }"
    >
      <UInput
        v-model="state.name"
        placeholder="e.g. Night Shift"
        size="lg"
        autofocus
        :disabled="loading"
        class="w-full"
        :ui="{ base: 'w-full bg-white/5 text-white placeholder:text-slate-500' }"
      />
    </UFormField>

    <UAlert
      v-if="formError"
      icon="i-lucide-triangle-alert"
      color="error"
      variant="soft"
      :title="formError"
      close
      @close="formError = ''"
    />

    <div class="flex items-center justify-end gap-2 pt-2">
      <UButton
        color="neutral"
        variant="ghost"
        :disabled="loading"
        @click="emit('cancel')"
      >
        Cancel
      </UButton>
      <UButton
        type="submit"
        color="primary"
        icon="i-lucide-plus"
        :loading="loading"
        :disabled="loading"
      >
        Create server
      </UButton>
    </div>
  </UForm>
</template>
