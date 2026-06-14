<script setup lang="js">
import InvalidInviteCard from "~/components/chat/InvalidInviteCard.vue";

const emit = defineEmits(['joined'])

// TODO: probably accept full invite object as prop instead to pass more control to parent
const props = defineProps({
  code: {
    type: String,
    required: true,
  }
})

const { serverAvatarSrc } = useServerAvatar()
const auth = useAuthStore()
const invite = ref(null)
const store = useServerStore()
const loading = ref(false)
const member = computed(() => invite.value?.server
  ? store.serverIds.includes(invite.value.server.id)
  : false)
async function handleJoin() {
  if (!auth.isAuthenticated) {
    return navigateTo('/login')
  }

  if (store.serverIds.includes(invite.value.server.id)) {
    emit('joined', invite.value.server.id)
    return
  }

  try {
    loading.value = true

    const {$apiFetch} = useNuxtApp();

    const res = await $apiFetch(`/invites/${props.code}/join`, {
      method: 'POST'
    })

    const membership = res.data

    // TODO: Dont be lazy
    await store.fetchServers()

    emit('joined', membership.server_id)
  } catch (e) {
    console.log(e);
  } finally {
    loading.value = false
  }
}

function buttonLabel() {
  if (auth.isAuthenticated) {
    if (store.serverIds.includes(invite.value.server.id)) {
      return 'Go to server'
    }

    return 'Join server'
  }

  return 'Login to join'
}

async function fetchServerInvite() {
  try {
    const {$apiFetch} = useNuxtApp();
    const res = await $apiFetch(`/invites/${props.code}`);

    invite.value = res.data || {}
  } catch {
    invite.value = null
  }
}

watchEffect(async() => {
  if (props.code) {
    await fetchServerInvite();
  }
})
</script>

<template>
  <section class="relative w-full max-w-md overflow-hidden p-7 bg-slate-900/72 sm:p-8">
    <div v-if="invite">
      <div class="flex flex-col items-center text-center">
        <UAvatar
            class="size-25"
            :src="serverAvatarSrc(invite.server)"
        />

        <p class="mt-6 text-xs font-black uppercase tracking-[0.24em] text-[#909541]">
          {{ member ? "You are a member of server" : "You've been invited to join" }}
        </p>
        <h1 class="mt-2 text-3xl font-black tracking-tight text-white">
          {{ invite.server.name }}
        </h1>
        <p class="mt-3 max-w-xs text-sm leading-6 text-slate-400">
          {{ invite.server.description || 'description not set'}}
        </p>
      </div>

      <div class="mt-7 grid grid-cols-2 gap-3">
        <div class="rounded-2xl border border-white/8 bg-white/4 px-4 py-3">
          <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-500">
            <UIcon name="i-lucide-users" class="size-4 text-orange-300"/>
            Members
          </div>
          <p class="mt-1 text-lg font-black tabular-nums text-slate-100">
            {{ invite.server.members || 67 }}
          </p>
        </div>

        <div class="rounded-2xl border border-white/8 bg-white/4 px-4 py-3">
          <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-500">
            <span class="size-2 rounded-full bg-emerald-400 shadow-[0_0_8px_rgba(52,211,153,0.7)]"/>
            Online
          </div>
          <p class="mt-1 text-lg font-black tabular-nums text-slate-100">
            {{ invite.server.online || 0 }}
          </p>
        </div>
      </div>

      <UButton
          block
          size="xl"
          color="neutral"
          icon="i-lucide-log-in"
          :loading="loading"
          :label="buttonLabel()"
          class="mt-6 justify-center rounded-2xl bg-[#954166] font-bold text-slate-20 shadow-lg shadow-orange-500/20 transition hover:bg-[#954166]/60 active:bg-[#954166]/80"
          @click="handleJoin"
      />
    </div>
    <div v-else>
      <InvalidInviteCard />
    </div>

    <p class="mt-5 truncate text-center text-xs text-slate-600">
      Invite code: {{ code }}
    </p>
  </section>
</template>
