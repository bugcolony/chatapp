<script setup lang="js">
import {useVoiceStore} from "~/stores/voiceStore";
import VoiceParticipant from "~/components/channels/VoiceParticipant.vue";
import LoaderOverlay from "~/components/loading/LoaderOverlay.vue";

const props = defineProps({
  channelId: {
    type: Number,
    required: true,
  },
  direct: {
    type: Boolean,
    default: false
  },
  collapsed: {
    type: Boolean,
    default: false
  },
  autoConnect: {
    type: Boolean,
    default: true
  }
})

const emit = defineEmits(['update:collapsed'])

const uiStore = useChatUIStore()
const voiceStore = useVoiceStore()
const {
  participants,
  microphoneEnabled,
  cameraEnabled,
  screenEnabled,
  microphoneStateLoading,
  cameraStateLoading,
  screenStateLoading,
  connectionStateConnected,
  connectionStateDisconnected,
  connectionStateConnecting,
} = storeToRefs(voiceStore)


const {voiceChannelParticipants} = storeToRefs(useServerStore())

const voiceParticipants = computed(() => {
  const cards = new Map(Array.from(participants.value, ([id, p]) => [Number(id), p]))

  if (!props.direct) {
    return cards
  }

  const present = voiceChannelParticipants.value.get(props.channelId) ?? new Set()

  present.forEach((userId) => {
    if (!cards.has(userId)) {
      cards.set(userId, null)
    }
  })

  return cards
})

let joinedChannelId = null

onMounted(() => {
  joinedChannelId = props.channelId

  if (props.autoConnect) {
    voiceStore.connect(joinedChannelId)
  }
})

onBeforeUnmount(() => {
  voiceStore.disconnect(joinedChannelId)
})

function handleLeaveJoinClick() {
  if (connectionStateConnected.value) {
    voiceStore.disconnect(joinedChannelId)

    return
  }

  if (connectionStateDisconnected.value) {
    voiceStore.connect(joinedChannelId)
  }
}
</script>

<template>
  <div class="w-full relative" :class="collapsed ? 'bg-black p-1' : 'bg-black/50 p-5'">
    <div v-show="!collapsed" class="h-full flex flex-wrap justify-center items-center gap-3">
      <VoiceParticipant
          v-for="[id, p] in voiceParticipants"
          :key="id"
          :participant="p"
          :user-id="id"
          :channel-id="channelId"
          :direct="direct"
          class="max-w-100"
      />
    </div>
    <div
        class="flex justify-center items-center p-1 bg-slate-900 ring-1 ring-white/10 rounded-md"
        :class="collapsed ? 'mx-auto w-fit' : 'absolute bottom-5 left-1/2 -translate-x-1/2'"
    >
      <LoaderOverlay :loading="microphoneStateLoading">
        <div
            class="hover:bg-slate-600 rounded-md cursor-pointer flex justify-center items-center p-1 w-9 h-9"
            :class="{'bg-red-400/40' : !microphoneEnabled}"
            @click="voiceStore.toggleMicrophone()"
        >
          <UTooltip :text="microphoneEnabled ? 'Mute microphone' : 'Unmute microphone'">
            <UIcon
                class="size-5"
                :class="microphoneEnabled ? 'bg-slate-400' : 'bg-red-600'"
                :name="microphoneEnabled ? 'i-lucide-mic' : 'i-lucide-mic-off'"
            />
          </UTooltip>
        </div>
      </LoaderOverlay>
      <LoaderOverlay v-if="!connectionStateDisconnected" :loading="cameraStateLoading">
        <div class="hover:bg-slate-600 rounded-md cursor-pointer flex justify-center items-center p-1 w-9 h-9" @click="voiceStore.toggleCamera()">
          <UTooltip :text="cameraEnabled ? 'Turn off camera' : 'Turn on camera'">
            <UIcon
                class=""
                :class="cameraEnabled ? 'animate-pulse bg-red-600 size-4' : 'bg-slate-400 size-6'"
                :name="cameraEnabled ? 'i-lucide-circle' : 'i-lucide-video'"
            />
          </UTooltip>
        </div>
      </LoaderOverlay>
      <LoaderOverlay v-if="!connectionStateDisconnected" :loading="screenStateLoading">
        <div
            class="hover:bg-slate-600 rounded-md cursor-pointer flex justify-center items-center p-1 w-9 h-9"
            @click="voiceStore.toggleScreen()"
        >
          <UTooltip :text="screenEnabled ? 'Turn off screen share' : 'Share screen'">
            <UIcon
                class=""
                :class="screenEnabled ? 'animate-pulse bg-red-600 size-4' : 'bg-slate-400 size-5'"
                :name="screenEnabled ? 'i-lucide-screen-share-off' : 'i-lucide-screen-share'"
            />
          </UTooltip>
        </div>
      </LoaderOverlay>

      <USeparator v-if="!collapsed" orientation="vertical" class="h-7 mx-2"/>
      <div
          v-if="!collapsed"
          class="hover:bg-slate-600 rounded-md cursor-pointer flex justify-center items-center p-1 w-9 h-9"
          :class="{ 'bg-slate-700': uiStore.voiceTextVisible}"
          @click="uiStore.toggleVoiceTextVisible()"
      >
        <UTooltip :text="uiStore.voiceTextVisible ? 'Hide chat' : 'Show chat'">
          <UIcon
              class="size-5"
              :class="uiStore.voiceTextVisible ? 'bg-slate-100' : 'bg-slate-400'"
              name="i-lucide-message-square"
          />
        </UTooltip>
      </div>

      <USeparator orientation="vertical" class="h-7 mx-2"/>
      <LoaderOverlay :loading="connectionStateConnecting">
        <div
            class=" rounded-md cursor-pointer flex justify-center items-center p-1 w-9 h-9"
            :class="connectionStateDisconnected ? 'bg-green-500 hover:bg-green-300' : 'bg-red-700 hover:bg-red-500'"
            @click="handleLeaveJoinClick()"
        >
          <UTooltip :text="connectionStateDisconnected ? 'Join call' : 'Leave call'">
            <UIcon
                class="size-5"
                :name="connectionStateDisconnected ? 'i-lucide-phone' : 'i-lucide-phone-off'"
            />
          </UTooltip>
        </div>
      </LoaderOverlay>
      <USeparator orientation="vertical" class="h-7 mx-2"/>
      <div
          class="hover:bg-slate-600 rounded-md cursor-pointer flex justify-center items-center p-1 w-9 h-9"
          @click="emit('update:collapsed', !collapsed)"
      >
        <UTooltip :text="collapsed ? 'Expand call' : 'Collapse call'">
          <UIcon
              class="size-5 bg-slate-400"
              :name="collapsed ? 'i-lucide-chevron-down' : 'i-lucide-chevron-up'"
          />
        </UTooltip>
      </div>
    </div>
  </div>
</template>

<style scoped lang="scss">

</style>
