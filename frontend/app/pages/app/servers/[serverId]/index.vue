<script setup lang="js">
import ChannelSidebar from '~/components/chat/ChannelSidebar.vue'
import MembersSidebar from '~/components/chat/MembersSidebar.vue'
import ServerStrip from '~/components/chat/ServerStrip.vue'
</script>

<template>
  <div class="contents">
    <ChannelSidebar />
    <main class="chat-panel relative my-2 flex min-h-0 min-w-0 flex-1 flex-col self-stretch rounded-4xl border border-white/8 bg-slate-950/62 shadow-2xl shadow-black/25 backdrop-blur-xl">
      <ServerStrip />
      <div class="flex flex-1 flex-col items-center justify-center text-slate-400">
        <UIcon name="i-lucide-message-square" class="size-10 text-slate-600" />
        <p class="mt-3 text-sm font-black text-slate-300">Pick a channel</p>
        <p class="mt-1 max-w-65 text-center text-xs text-slate-500">
          Select a channel from the left to start chatting.
        </p>
      </div>
    </main>
    <MembersSidebar />
  </div>
</template>
