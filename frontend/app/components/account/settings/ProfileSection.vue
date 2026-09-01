<script setup lang="js">
import * as z from 'zod'
import { storeToRefs } from 'pinia'
import { fallbackAvatarSrc, userAvatarSrc } from '~/composables/useServerAvatar.js'
import { isPreviewableImageType } from '~/utils/messageAttachment.js'
import { useDirtyForm, useSettingsSection } from '~/composables/useSettingsSection.js'

const ACCEPTED_AVATAR_TYPES = 'image/png,image/jpeg,image/gif,image/webp,image/avif'
const MAX_AVATAR_BYTES = 2 * 1024 * 1024

const auth = useAuthStore()
const { user } = storeToRefs(auth)
const toast = useToast()

const schema = z.object({
  name: z.string().trim().min(2, 'At least 2 characters').max(32, 'At most 32 characters'),
  avatar: z
    .instanceof(File)
    .refine(file => isPreviewableImageType(file.type), 'Pick a PNG, JPEG, GIF, WebP or AVIF image.')
    .refine(file => file.size <= MAX_AVATAR_BYTES, 'AVatar must be 2 MB or smaller.')
    .nullish(),
  remove_avatar: z.boolean(),
})

const { state, isDirty, reset, commit } = useDirtyForm(() => ({
  name: user.value?.name ?? '',
  avatar: null,
  remove_avatar: false,
}))

const formRef = useTemplateRef('profileForm')
const avatarPreview = ref('')
const formError = ref('')

const loading = computed(() => Boolean(formRef.value?.loading))
const hasSavedAvatar = computed(() => Boolean(user.value?.avatar))
const hasStagedChange = computed(() => Boolean(state.avatar) || state.remove_avatar)

const currentAvatar = computed(() => {
  if (avatarPreview.value) return avatarPreview.value
  if (state.remove_avatar) return fallbackAvatarSrc(user.value?.name ?? '')

  return userAvatarSrc(user.value)
})

function cancelAvatarChange() {
  state.avatar = null
  state.remove_avatar = false
}

async function onSubmit(event) {
  formError.value = ''

  try {
    const updated = await auth.updateProfile({
      name: event.data.name,
      avatar: event.data.avatar,
      remove_avatar: event.data.remove_avatar,
    })

    commit({
      name: updated?.name ?? event.data.name,
      avatar: null,
      remove_avatar: false,
    })

    toast.add({
      title: 'Profile updated',
      color: 'success',
      icon: 'i-lucide-check',
    })
  } catch (err) {
    console.error('Failed to update profile:', err)
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

    formError.value = err?.data?.message ?? 'Failed to update profile. Try again.'
  }
}

watch(() => state.avatar, (file, _previous, onCleanup) => {
  if (!file) {
    avatarPreview.value = ''
    return
  }

  state.remove_avatar = false

  const url = URL.createObjectURL(file)

  avatarPreview.value = url
  onCleanup(() => URL.revokeObjectURL(url))
})

useSettingsSection({
  isDirty,
  loading,
  submit: () => formRef.value?.submit(),
  reset: () => {
    reset()
    formError.value = ''
    formRef.value?.clear()
  },
})
</script>

<template>
  <div>
    <h1 class="text-xl font-black tracking-tight text-highlighted">Profile</h1>

    <UForm
      ref="profileForm"
      :schema="schema"
      :state="state"
      class="mt-8 space-y-6"
      @submit="onSubmit"
    >
      <UCard :ui="{ body: 'p-5' }">
        <UFormField name="avatar" :ui="{ error: 'mt-3' }">
          <UFileUpload
            v-model="state.avatar"
            :accept="ACCEPTED_AVATAR_TYPES"
            :disabled="loading"
            :preview="false"
          >
            <template #default="{ open: pickAvatar }">
              <div class="flex flex-wrap items-center gap-5">
                <UButton
                  type="button"
                  color="neutral"
                  variant="ghost"
                  aria-label="Change avatar"
                  :disabled="loading"
                  class="group relative size-20 shrink-0 overflow-hidden rounded-full p-0 ring-2 ring-default transition hover:ring-primary"
                  @click="pickAvatar"
                >
                  <img :src="currentAvatar" alt="" class="size-full object-cover">
                  <span
                    class="absolute inset-0 grid place-items-center bg-black/60 text-[0.6rem] font-black uppercase tracking-[0.14em] text-white opacity-0 transition group-hover:opacity-100"
                  >
                    Change
                  </span>
                </UButton>

                <div class="min-w-0 flex-1">
                  <p class="text-md font-bold mb-1">Change avatar</p>
                  <p class="text-xs text-dimmed">
                    PNG, JPG, GIF, WebP, AVIF · max 2 MB
                  </p>

                  <UButton
                    v-if="hasStagedChange"
                    type="button"
                    label="Cancel"
                    icon="i-lucide-undo-2"
                    color="neutral"
                    variant="soft"
                    size="sm"
                    class="mt-3"
                    :disabled="loading"
                    @click="cancelAvatarChange"
                  />
                  <UButton
                    v-else-if="hasSavedAvatar"
                    type="button"
                    label="Remove avatar"
                    icon="i-lucide-trash-2"
                    color="error"
                    variant="soft"
                    size="sm"
                    class="mt-3"
                    :disabled="loading"
                    @click="state.remove_avatar = true"
                  />
                </div>
              </div>
            </template>
          </UFileUpload>
        </UFormField>
      </UCard>

      <UFormField
        label="Display name"
        name="name"
        required
        hint="2–32 characters"
        :ui="{
          label: 'text-xs font-bold uppercase tracking-[0.18em] text-muted',
          hint: 'text-xs text-dimmed',
        }"
      >
        <UInput
          v-model="state.name"
          placeholder="e.g. Night Owl"
          size="lg"
          :disabled="loading"
          class="w-full"
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
    </UForm>
  </div>
</template>
