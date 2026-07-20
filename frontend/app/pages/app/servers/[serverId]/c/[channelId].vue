<script setup lang="js">
import { computed } from 'vue'
import MessageThread from '~/components/messages/MessageThread.vue'

const route = useRoute()
const channelId = computed(() => Number(route.params.channelId))
</script>

<template>
  <MessageThread
    :key="channelId"
    :channel-id="channelId"
    class="my-2 min-w-0 flex-1 self-stretch"
  />
</template>
