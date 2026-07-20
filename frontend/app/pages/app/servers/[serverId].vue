<script setup lang="js">
import ChannelSidebar from '~/components/channels/ChannelSidebar.vue'
import MembersSidebar from '~/components/members/MembersSidebar.vue'
</script>

<template>
  <div class="contents">
    <ChannelSidebar />
    <NuxtPage />
    <MembersSidebar />
  </div>
</template>
