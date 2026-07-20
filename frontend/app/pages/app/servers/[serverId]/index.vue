<script setup lang="js">
import PinnedServerBar from '~/components/servers/PinnedServerBar.vue'
</script>

<template>
  <main class="chat-panel relative my-2 flex min-h-0 min-w-0 flex-1 flex-col self-stretch rounded-4xl border border-white/8 bg-slate-950/62 shadow-2xl shadow-black/25 backdrop-blur-xl">
    <PinnedServerBar />
    <div class="flex flex-1 flex-col items-center justify-center text-slate-400">
      <UIcon name="i-lucide-message-square" class="size-10 text-slate-600" />
      <p class="mt-3 text-sm font-black text-slate-300">
        Pick a channel
      </p>
      <p class="mt-1 max-w-65 text-center text-xs text-slate-500">
        Select a channel from the left to start chatting.
      </p>
    </div>
  </main>
</template>
