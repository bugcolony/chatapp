<script setup lang="js">
import { computed, ref } from 'vue'
import { storeToRefs } from 'pinia'
import HomeSidebar from '~/components/servers/HomeSidebar.vue'
import FriendsSidebar from '~/components/friends/FriendsSidebar.vue'
import PinnedServerBar from '~/components/servers/PinnedServerBar.vue'
import MessageThread from '~/components/messages/MessageThread.vue'
import VoicePanel from '~/components/channels/VoicePanel.vue'
import { useVoiceStore } from '~/stores/voiceStore.js'
import { userAvatarSrc } from '~/composables/useServerAvatar.js'

definePageMeta({
  title: 'Chat',
})

const route = useRoute()
const store = useServerStore()
const uiStore = useChatUIStore()
const authStore = useAuthStore()
const voiceStore = useVoiceStore()
const { activeChannelId } = storeToRefs(voiceStore)
const { directChannels, voiceChannelParticipants } = storeToRefs(store)
const { voiceTextVisible } = storeToRefs(uiStore)

const channelId = computed(() => Number(route.params.channelId))
const channel = computed(() => directChannels.value.get(channelId.value) ?? null)
const channelParticipants = computed(() => channel.value?.participants.filter((p) => p.id !== authStore.user?.id ) ?? [])
const title = computed(() => channelParticipants.value.map((p) => p.name).join(', ') ?? '')
const avatars = computed(() => channelParticipants.value.map((p) => userAvatarSrc(p)) ?? [])
const callParticipants = computed(() => voiceChannelParticipants.value.get(channelId.value) ?? new Set())
const joined = computed(() => activeChannelId.value === channelId.value)
const showVoicePanel = computed(() => joined.value || callParticipants.value.size > 0)
const voicePanelHidden = ref(false)

uiStore.setServerDirectTab('direct')


</script>

<template>
  <div class="contents">
    <HomeSidebar />

    <main class="chat-panel relative my-2 flex min-h-0 min-w-0 flex-1 flex-col self-stretch rounded-4xl border border-white/8 bg-slate-950/62 shadow-2xl shadow-black/25 backdrop-blur-xl">
      <PinnedServerBar />

      <div v-show="callParticipants.size === 0" class="px-4 py-3 opacity-30 hover:opacity-100 flex items-center">
        <div
          class="flex items-center rounded-full border gap-2 border-white/10 bg-white/6 pr-3 pl-1 py-1"
          :class="callParticipants.size ? 'w-full' : 'w-fit'"
        >
          <UCarousel
            v-if="avatars.length > 1"
            v-slot="{ item }"
            loop
            fade
            :autoplay="{ delay: 3000 }"
            :items="avatars"
            :ui="{ item: 'basis-full ps-0', container: 'ms-0' }"
            class="w-9"
          >
            <UAvatar
              :src="item"
              size="sm"
            />
          </UCarousel>
          <UAvatar
            v-else
            :src="avatars[0]"
            size="sm"
          />

          <span class="text-xs font-bold text-white">{{ title }}</span>
        </div>
        <UButton
            icon="i-lucide-phone"
            color="neutral"
            variant="ghost"
            class="ml-3 rounded-xl bg-green-500 font-bold text-white hover:bg-green-400"
            @click="voiceStore.connect(channelId)"
        />
<!--        <UButton-->
<!--            icon="i-lucide-user-plus"-->
<!--            color="neutral"-->
<!--            variant="ghost"-->
<!--            class="ml-1 rounded-xl font-bold text-white bg-slate-800 hover:bg-slate-700"-->
<!--        />-->
      </div>

      <div class="relative flex min-h-0 min-w-0 flex-1 flex-col">
        <VoicePanel
          v-if="showVoicePanel"
          :key="channelId"
          v-model:collapsed="voicePanelHidden"
          :channel-id="channelId"
          direct
          :auto-connect="false"
          :class="voicePanelHidden ? '' : 'flex-1'"
        />
        <MessageThread
          v-show="!showVoicePanel || voicePanelHidden || voiceTextVisible"
          :key="channelId"
          :channel-id="channelId"
          class="min-w-0 flex-1 self-stretch"
        />
      </div>
    </main>

    <FriendsSidebar />
  </div>
</template>
