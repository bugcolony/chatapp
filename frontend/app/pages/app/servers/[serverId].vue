<script setup lang="js">
import ChannelSidebar from '~/components/channels/ChannelSidebar.vue'
import ServerMiddlePanel from '~/components/layout/ServerMiddlePanel.vue'
import MembersSidebar from '~/components/members/MembersSidebar.vue'
</script>

<template>
  <div class="contents">
    <ChannelSidebar />
    <ServerMiddlePanel>
      <NuxtPage />
    </ServerMiddlePanel>
    <MembersSidebar />
  </div>
</template>
