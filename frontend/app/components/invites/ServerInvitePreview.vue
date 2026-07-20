<script setup lang="js">
import {useInviteOverlay} from "~/composables/useInviteOverlay.js";
import {serverAvatarSrc} from "~/composables/useServerAvatar.js";
import InvalidInviteCard from "~/components/invites/InvalidInviteCard.vue";

const props = defineProps({
  code: {
    type: String,
    required: true,
  },
})

const store = useServerStore()
const valid = ref(false)
const invite = ref({})
const joined = computed(() => valid.value ? store.serverIds.includes(invite.value.server.id) : false)

async function fetchServerInvite() {
  try {
    const {$apiFetch} = useNuxtApp();
    const res = await $apiFetch(`/invites/${props.code}`);

    invite.value = res.data || {}

    if (invite.value.id) {
      valid.value = true
    }
  } catch {
    valid.value = false
    invite.value = {}
  }
}

watchEffect(async() => {
  if (props.code) {
    await fetchServerInvite();
  }
})

const { openInvite } = useInviteOverlay()
</script>

<template>
  <div class="mt-3 max-w-sm overflow-hidden rounded-2xl border border-white/10 bg-slate-900/75 shadow-lg shadow-black/15">
    <div v-if="valid" class="flex items-center gap-3 p-3">
      <UAvatar
          :src="serverAvatarSrc(invite.server)"
          :alt="invite.server.name"
          size="3xl"
          class="shrink-0 rounded-full"
      />

      <div class="min-w-0 flex-1">
        <p class="text-[10px] font-black uppercase tracking-[0.18em] text-[#909541]">
          Server invite
        </p>
        <p class="mt-0.5 truncate text-sm font-black text-white">
          {{ invite.server.name || 'Unknown server' }}
        </p>
        <p class="mt-1 truncate text-xs text-slate-500">
          Invite code: {{ code }}
        </p>
      </div>
    </div>
    <div v-else class="flex items-center gap-3 p-3">
      <InvalidInviteCard />
    </div>

    <div v-if="valid" class="text-end p-1">
      <UButton
        v-if="!joined"
        block
        color="neutral"
        variant="ghost"
        icon="i-lucide-log-in"
        label="View invite"
        class="justify-center w-1/2 rounded-xl bg-[#954166] font-bold text-white transition hover:bg-[#954166]/70 active:bg-[#954166]/85"
        @click="openInvite(props.code)"
      />
    </div>
<!--    <div v-else class="text-end p-2 mb-1">-->
<!--      <span class=" text-sm text-red-500 bg-red-300/10 px-2 py-1 border border-red-500/50 rounded-xl">Invalid invite</span>-->
<!--    </div>-->
  </div>
</template>
