<script setup lang="js">

import AudioTrack from "~/components/channels/AudioTrack.vue";
import VideoTrack from "~/components/channels/VideoTrack.vue";
import {fallbackAvatarSrc} from "~/composables/useServerAvatar.js";
import {useVoiceStore} from "~/stores/voiceStore.js";

const props = defineProps({
  userId: {
    type: String,
    required: true,
  },
  participant: {
    type: Object,
    required: true,
  }
})

const store = useServerStore()
const voiceStore = useVoiceStore()
const {microphoneEnabled} = storeToRefs(voiceStore)
const {activeServerId, serverMembers} = storeToRefs(store)
const member = computed(() => serverMembers.value[activeServerId.value]?.find((m) => m.user.id === Number(props.userId)) ?? null)
const trackList = computed(() => Array.from(props.participant.tracks).map((entry) => entry[1]) ?? [])
const microphone = computed(() => trackList.value.find((t) => t.source === 'microphone'))
const camera = computed(() => trackList.value.find((t) => t.source === 'camera'))
const screen = computed(() => trackList.value.find((t) => t.source === 'screen_share'))
const screenAudio = computed(() => trackList.value.find((t) => t.source === 'screen_share_audio'))
const isStreaming = computed(() => (camera.value && !camera.value?.muted) || (screen.value && !screen.value?.muted))
const isMuted = computed(() => props.participant.local ? !microphoneEnabled.value : trackList.value.length === 0 || (microphone.value && microphone.value?.muted))

</script>

<template>
<div
    class="group rounded-md aspect-video w-full flex justify-center items-center relative"
    :class="participant.isSpeaking && !microphone?.muted ? 'ring-4 ring-indigo-400' : 'ring-1 ring-slate-700/30'"
>
  <UAvatar
      :src="fallbackAvatarSrc(member?.display_name)"
      class="size-20"
      :class="{'hidden': isStreaming}"
  />
  <AudioTrack v-if="microphone" :track="microphone" :key="microphone.sid"></AudioTrack>
  <AudioTrack v-if="screenAudio" :track="screenAudio" :key="screenAudio.sid"></AudioTrack>
  <VideoTrack v-if="camera && !camera?.muted" :track="camera" :key="camera.sid"></VideoTrack>
  <VideoTrack v-if="screen && !screen?.muted" :track="screen" :key="screen.sid"></VideoTrack>
  <span class="hidden group-hover:block px-2 py-1 text-slate-400 text-xs absolute bottom-0 left-0">{{member.display_name}}</span>
  <UIcon
      v-show="isMuted"
      name="i-lucide-mic-off"
      class="size-4 bg-slate-400 absolute right-2 bottom-2"
  />
</div>
</template>

<style scoped lang="scss">

</style>