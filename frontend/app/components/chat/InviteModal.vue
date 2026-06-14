<script setup lang="js">
import { computed, onMounted, ref } from 'vue'
import {useAppUrl} from "~/composables/useAppUrl.js";

const emit = defineEmits(['close'])

const props = defineProps({
  server: {
    type: Object,
    required: true,
  },
})

const loading = ref(false)
const error = ref('')
const invite = ref(null)
const { appUrl } = useAppUrl()
const { copy, copied } = useClipboard()

const inviteUrl = computed(() => invite.value?.code ? `${appUrl}/invite/${invite.value.code}` : '')

async function createInvite() {
  loading.value = true
  error.value = ''

  try {
    const { $apiFetch } = useNuxtApp()
    const res = await $apiFetch(`/servers/${props.server.id}/invites`, {
      method: 'POST',
    })

    invite.value = res?.data ?? null
  } catch (e) {
    console.error(e)
    error.value = 'Could not create invite.'
  } finally {
    loading.value = false
  }
}

function copyInvite() {
  if (inviteUrl.value) {
    copy(inviteUrl.value)
  }
}

onMounted(createInvite)
</script>

<template>
  <UModal>
    <template #content>
      <div class="p-6 space-y-3">
        <div v-if="loading" class="flex items-center gap-3 text-sm text-muted">
          <UIcon name="i-lucide-loader-circle" class="size-4 animate-spin" />
          Creating invite...
        </div>

        <div v-else-if="error" class="space-y-3">
          <p class="text-sm text-error">
            {{ error }}
          </p>
          <UButton
              label="Retry"
              color="neutral"
              variant="soft"
              @click="createInvite"
          />
        </div>

        <div v-else >
          <p class="font-semibold text-xl mb-4">{{`Invite people to ${server.name}.`}}</p>
          <div class="flex gap-2 mb-1">
            <input
                readonly
                :value="inviteUrl"
                class="min-w-0 flex-1 rounded-md border border-default bg-muted px-3 py-2 text-sm text-highlighted outline-none"
            >
            <UButton
                :label="copied ? 'Copied' : 'Copy'"
                :icon="copied ? 'i-lucide-copy-check' : 'i-lucide-copy'"
                color="neutral"
                @click="copyInvite"
            />
          </div>

          <p class="text-sm text-muted">
            Anyone with this link can join this server.
          </p>
        </div>

        <div class="text-end">
          <UButton
              label="Done"
              color="neutral"
              variant="ghost"
              @click="emit('close')"
          />
        </div>
      </div>
    </template>
  </UModal>
</template>
