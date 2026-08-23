<script setup lang="js">
import MessageThread from '~/components/messages/MessageThread.vue'
import VoicePanel from "~/components/channels/VoicePanel.vue";

const store = useServerStore()
const uiStore = useChatUIStore()
const {activeMessageChannelId, activeServerId, activeChannelId, activeChannel} = storeToRefs(store)
const {voiceTextVisible} = storeToRefs(uiStore)
const showMessageThread = computed(() => {
  return activeChannel.value.type === 'text' || voiceTextVisible.value
})
</script>

<template>
  <div class="flex flex-col justify-between h-full">
    <VoicePanel
        v-if="activeChannel?.type === 'voice'"
        :channel-id="activeChannelId"
        class="flex-1" />
    <MessageThread
        v-if="activeMessageChannelId"
        v-show="showMessageThread"
        :key="activeChannelId"
        :channel-id="activeMessageChannelId"
        :server-id="activeServerId"
        class="min-w-0 flex-1 self-stretch"
    />
  </div>

</template>
