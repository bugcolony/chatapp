<script setup lang="js">
import { computed, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { userAvatarSrc } from '~/composables/useServerAvatar.js'

const props = defineProps({
  channel: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['success'])
const open = defineModel('open', { type: Boolean, default: false })

const MAX_PARTICIPANTS = 10

const store = useServerStore()
const authStore = useAuthStore()
const toast = useToast()
const { friends } = storeToRefs(store)

const selected = ref([])
const loading = ref(false)
const formError = ref('')

const isGroup = computed(() => props.channel.type === 'group_dm')
const participantIds = computed(() => (props.channel.participants ?? []).map((p) => p.id))
const remainingSlots = computed(() => MAX_PARTICIPANTS - participantIds.value.length)

const candidates = computed(() => friends.value.filter((f) => !participantIds.value.includes(f.id)))

const atCapacity = computed(() => remainingSlots.value <= 0)
const canSubmit = computed(() => selected.value.length > 0
  && selected.value.length <= remainingSlots.value
  && !loading.value)

function toggle(friendId) {
  if (selected.value.includes(friendId)) {
    selected.value = selected.value.filter((id) => id !== friendId)

    return
  }

  if (selected.value.length >= remainingSlots.value) {
    return
  }

  selected.value = [...selected.value, friendId]
}

async function onSubmit() {
  loading.value = true
  formError.value = ''

  try {
    if (isGroup.value) {
      for (const friendId of selected.value) {
        await store.addGroupParticipant(props.channel.id, friendId)
      }

      emit('success', props.channel.id)
      open.value = false

      return
    }

    const others = participantIds.value.filter((id) => id !== authStore.user?.id)
    const group = await store.createGroupChannel([...others, ...selected.value])

    emit('success', group.id)
    open.value = false

    await navigateTo(`/app/direct/${group.id}`)
  } catch (error) {
    formError.value = error?.data?.message ?? 'Something went wrong. Please try again.'
    toast.add({ title: 'Could not add participants', description: formError.value, color: 'error' })
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <UModal
    v-model:open="open"
    :title="isGroup ? 'Add to group' : 'Start a group'"
    :description="isGroup
      ? 'Pick friends to add to this group.'
      : 'Start group with selected friends.'"
  >
    <template #body>
      <div class="space-y-3">
        <p
          v-if="atCapacity"
          class="rounded-lg bg-amber-500/10 px-3 py-2 text-sm font-semibold text-amber-200"
        >
          This group is full.
        </p>

        <p
          v-else-if="!candidates.length"
          class="rounded-lg bg-white/5 px-3 py-2 text-sm text-slate-300"
        >
          No friends left to add.
        </p>

        <div
          v-else
          class="max-h-72 space-y-1 overflow-y-auto"
        >
          <UButton
            v-for="friend in candidates"
            :key="friend.id"
            block
            color="neutral"
            variant="ghost"
            class="flex w-full items-center gap-3 rounded-xl px-2 py-2 text-left"
            :class="selected.includes(friend.id) ? 'bg-white/10 text-white' : 'text-slate-300'"
            :disabled="!selected.includes(friend.id) && selected.length >= remainingSlots"
            :ui="{ base: 'justify-start' }"
            @click="toggle(friend.id)"
          >
            <UAvatar
              :src="userAvatarSrc(friend)"
              size="sm"
            />
            <span class="min-w-0 flex-1 truncate text-sm font-bold">{{ friend.name }}</span>
            <UIcon
              v-if="selected.includes(friend.id)"
              name="i-lucide-check"
              class="size-4"
            />
          </UButton>
        </div>

        <p class="text-xs text-slate-500">
          {{ selected.length }} selected &middot; {{ remainingSlots }} slot(s) left
        </p>
      </div>
    </template>

    <template #footer>
      <div class="flex w-full justify-end gap-2">
        <UButton
          color="neutral"
          variant="ghost"
          label="Cancel"
          @click="open = false"
        />
        <UButton
          color="primary"
          :loading="loading"
          :disabled="!canSubmit"
          :label="isGroup ? 'Add' : 'Create group'"
          @click="onSubmit"
        />
      </div>
    </template>
  </UModal>
</template>
