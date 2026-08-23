<script setup lang="js">
import * as z from 'zod'
import {storeToRefs} from "pinia";

const props = defineProps({
  server: {
    type: Object,
    required: true,
  },
  channel: {
    type: Object,
    default: null,
  },
  parentId: {
    type: [Number, String],
    default: null,
  },
})

const emit = defineEmits(['success'])
const open = defineModel('open', { type: Boolean, default: false })
const store = useServerStore()
const { serverChannels } = storeToRefs(store)

const isEditing = computed(() => Boolean(props.channel?.id))

const state = reactive({
  name: props.channel?.name ?? '',
  type: props.channel?.type ?? 'text',
  parent_id: props.channel?.parent_id ?? props.parentId,
})

const loading = ref(false)
const formError = ref('')
const toast = useToast()
const formRef = useTemplateRef('channelForm')
const categories = computed(() => {
  const channels = serverChannels.value[props.server.id] ?? []

  return channels.filter(item => item.type === 'category')
})

const types = [
  {
    id: 'text',
    name: 'Text',
  },
  {
    id: 'voice',
    name: 'Voice',
  }
]

const schema = z.object({
  name: z.string().trim().min(1, 'Enter a channel name').max(100, 'At most 100 characters'),
})

async function onSubmit() {
  loading.value = true
  formError.value = ''

  try {
    const { $apiFetch } = useNuxtApp()
    const payload = {
      name: state.name.trim(),
      parent_id: state.parent_id ?? null,
    }

    if (!isEditing.value) {
      payload.type = state.type
    }

    const response = await $apiFetch(
      isEditing.value
        ? `/channels/${props.channel.id}`
        : `/servers/${props.server.id}/channels`,
      {
        method: isEditing.value ? 'PATCH' : 'POST',
        body: payload,
      },
    )
    const savedChannel = response?.data ?? (isEditing.value
      ? { ...props.channel, ...payload }
      : null)

    if (savedChannel?.id) {
      store.upsertServerChannel(props.server.id, savedChannel)
    }

    toast.add({
      title: isEditing.value ? 'Channel updated' : 'Channel created',
      description: isEditing.value
        ? `#${payload.name} has been updated.`
        : `#${payload.name} is ready to use.`,
      color: 'success',
      icon: 'i-lucide-check',
    })

    emit('success', savedChannel)
    open.value = false
  } catch (error) {
    console.error(`Failed to ${isEditing.value ? 'update' : 'create'} channel:`, error)
    const status = error?.response?.status ?? error?.statusCode

    if (status === 422 && error?.data?.errors) {
      formRef.value?.setErrors(
        Object.entries(error.data.errors).map(([name, messages]) => ({
          name,
          message: Array.isArray(messages) ? messages[0] : messages,
        })),
      )
      return
    }

    formError.value = error?.data?.message
      ?? `Failed to ${isEditing.value ? 'update' : 'create'} channel. Try again.`
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <UModal
    v-model:open="open"
    :title="isEditing ? 'Edit channel' : 'Create new channel'"
    :description="isEditing ? 'Update this channel’s name or category.' : 'Add a text channel to this server.'"
    :ui="{
      content: 'max-w-md rounded-2xl border border-white/10 bg-slate-950/95 shadow-2xl shadow-black/40 backdrop-blur-xl',
      header: 'border-b border-white/8 p-5',
      title: 'text-lg font-black tracking-tight text-white',
      description: 'text-sm text-slate-400',
      body: 'p-5',
    }"
  >
    <template #body>
      <UForm
        ref="channelForm"
        :schema="schema"
        :state="state"
        :validate-on="[]"
        class="space-y-4"
        @submit="onSubmit"
      >
        <UFormField
            label="In category"
            name="parent_id"
            :ui="{ label: 'text-xs font-bold uppercase tracking-[0.18em] text-slate-400', hint: 'text-xs text-slate-500' }"
        >
          <USelectMenu
              v-model="state.parent_id"
              value-key="id"
              label-key="name"
              placeholder="Select a category"
              clear
              :items="categories"
              class="w-full"
          />
        </UFormField>

        <UFormField
            label="Type"
            name="type"
            :ui="{ label: 'text-xs font-bold uppercase tracking-[0.18em] text-slate-400', hint: 'text-xs text-slate-500' }"
        >
          <USelectMenu
              v-model="state.type"
              value-key="id"
              label-key="name"
              placeholder="Select type"
              :items="types"
              class="w-full"
              :disabled="isEditing"
          />
        </UFormField>

        <UFormField
          label="Channel name"
          name="name"
          required
          :ui="{ label: 'text-xs font-bold uppercase tracking-[0.18em] text-slate-400', hint: 'text-xs text-slate-500' }"
        >
          <UInput
            v-model="state.name"
            placeholder="e.g. general"
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
            @click="open = false"
          >
            Cancel
          </UButton>
          <UButton
            type="submit"
            color="primary"
            :icon="isEditing ? 'i-lucide-save' : 'i-lucide-plus'"
            :loading="loading"
            :disabled="loading"
          >
            {{ isEditing ? 'Save changes' : 'Create channel' }}
          </UButton>
        </div>
      </UForm>
    </template>
  </UModal>
</template>
