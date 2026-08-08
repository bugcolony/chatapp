<script setup lang="js">
import { computed } from 'vue'
import MessageThread from '~/components/messages/MessageThread.vue'

const route = useRoute()
const channelId = computed(() => Number(route.params.channelId))
const serverId = computed(() => Number(route.params.serverId))
</script>

<template>
  <MessageThread
    :key="channelId"
    :channel-id="channelId"
    :server-id="serverId"
    class="my-2 min-w-0 flex-1 self-stretch"
  />
</template>
