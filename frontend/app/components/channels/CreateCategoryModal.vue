<script setup lang="js">
import * as z from "zod";

const props = defineProps({
  server: {
    type: Object,
    required: true,
  },
  category: {
    type: Object,
    default: null,
  },
})

const emit = defineEmits(['success', 'cancel'])

const open = defineModel('open', { type: Boolean, default: false })

const store = useServerStore()
const isEditing = computed(() => Boolean(props.category?.id))
const state = reactive({
  name: props.category?.name ?? '',
  type: 'category',
  parent_id: null,
})
const loading = ref(false)
const formError = ref("")
const toast = useToast()
const formRef = useTemplateRef("categoryForm")

const schema = z.object({
  name: z.string().trim().min(3, 'At least 3 characters').max(255, 'At most 255 characters'),
})

async function onSubmit() {
  loading.value = true
  formError.value = ''

  try {
    const { $apiFetch } = useNuxtApp()
    const payload = {
      name: state.name.trim(),
      type: 'category',
      parent_id: null,
    }
    const response = await $apiFetch(
      isEditing.value
        ? `/channels/${props.category.id}`
        : `/servers/${props.server.id}/channels`,
      {
        method: isEditing.value ? 'PATCH' : 'POST',
        body: payload,
      },
    )
    const savedCategory = response?.data ?? (isEditing.value
      ? { ...props.category, ...payload }
      : null)

    if (savedCategory?.id) {
      store.upsertServerChannel(props.server.id, savedCategory)
    }

    toast.add({
      title: isEditing.value ? 'Category updated' : 'Category created',
      description: isEditing.value
        ? `${payload.name} has been updated.`
        : 'You can add channels to it now.',
      color: 'success',
      icon: 'i-lucide-check',
    })

    state.name = ''
    emit('success', savedCategory)
    open.value = false

  } catch (err) {
    console.error(`Failed to ${isEditing.value ? 'update' : 'create'} category:`, err)
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

    formError.value = err?.data?.message
      ?? `Failed to ${isEditing.value ? 'update' : 'create'} category. Try again.`
  } finally {
    loading.value = false
  }
}

</script>

<template>
  <UModal
      v-model:open="open"
      :title="isEditing ? 'Edit category' : 'Create new category'"
      :description="isEditing ? 'Update how this category appears in the channel list.' : 'Group related channels together.'"
      :ui="{
      content: 'max-w-md rounded-2xl border border-white/10 bg-slate-950/95 shadow-2xl shadow-black/40 backdrop-blur-xl',
      header: 'border-b border-white/8 p-5',
      title: 'text-lg font-black tracking-tight text-white',
      description: 'text-sm text-slate-400',
      body: 'p-5',
    }"
  >
    <slot />

    <template #body>
      <UForm
          ref="categoryForm"
          :schema="schema"
          :state="state"
          :validate-on="[]"
          class="space-y-4"
          @submit="onSubmit"
      >
        <UFormField
            label="Category name"
            name="name"
            required
            hint="3–255 characters"
            :ui="{ label: 'text-xs font-bold uppercase tracking-[0.18em] text-slate-400', hint: 'text-xs text-slate-500' }"
        >
          <UInput
              v-model="state.name"
              placeholder="e.g. General"
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
            {{ isEditing ? 'Save changes' : 'Create category' }}
          </UButton>
        </div>
      </UForm>
    </template>
  </UModal>
</template>

