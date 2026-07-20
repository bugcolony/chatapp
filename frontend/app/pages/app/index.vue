<script setup lang="js">
import HomeSidebar from '~/components/servers/HomeSidebar.vue'
import FriendsSidebar from '~/components/friends/FriendsSidebar.vue'
import PinnedServerBar from '~/components/servers/PinnedServerBar.vue'

definePageMeta({
  title: 'Chat',
})

</script>

<template>
  <div class="contents">
    <HomeSidebar />

    <main class="chat-panel relative my-2 flex min-h-0 min-w-0 flex-1 flex-col self-stretch rounded-4xl border border-white/8 bg-slate-950/62 shadow-2xl shadow-black/25 backdrop-blur-xl">
      <PinnedServerBar />
    </main>

    <FriendsSidebar />


  </div>
</template>
