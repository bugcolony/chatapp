<script setup lang="js">
import ChannelSidebar from '~/components/chat/ChannelSidebar.vue'
import ChatWindow from '~/components/chat/ChatWindow.vue'
import MembersSidebar from '~/components/chat/MembersSidebar.vue'
</script>

<template>
  <div class="contents">
    <ChannelSidebar />
    <ChatWindow class="my-2 min-w-0 flex-1 self-stretch" />
    <MembersSidebar />
  </div>
</template>
