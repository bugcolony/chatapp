<script setup lang="js">
import { fallbackAvatarSrc } from '~/composables/useServerAvatar.js'
import { extractInviteCodes } from '~/utils/extractInviteCodes.js'
import ServerInvitePreview from '~/components/invites/ServerInvitePreview.vue'
import {useAppUrl} from "~/composables/useAppUrl.js";

const props = defineProps({
  message: {
    type: Object,
    required: true,
  },
})

const created = useDateFormat(props.message.created_at, 'DD.MM.YYYY HH:mm')
const { appUrl } = useAppUrl()
const inviteCodes = computed(() => extractInviteCodes(props.message.message, appUrl))
</script>

<template>
  <article
    class="group rounded-xl border border-transparent p-4 transition hover:border-white/8 hover:bg-white/3 relative"
    :class="message.mine ? 'bg-indigo-400/4' : ''"
  >
    <div class="flex gap-3">
      <UAvatar
        :src="fallbackAvatarSrc(message.author)"
        :alt="message.author"
        size="md"
        class="shrink-0 rounded-xl ring-1 ring-white/10"
      />

      <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-baseline gap-2">
          <h3 class="text-sm font-black text-white">
            {{ message.author }}
          </h3>
          <span class="text-xs text-slate-500">{{ created }}</span>
        </div>

        <p class="mt-1.5 max-w-4xl whitespace-pre-wrap text-sm leading-6" :class="message.status === 'pending' || message.status === 'failed' ? 'text-slate-600' : 'text-slate-200'">
          {{ message.message }}
        </p>

        <ServerInvitePreview
          v-for="code in inviteCodes"
          :key="code"
          :code="code"
        />

        <UButton
          v-if="message.attachment"
          color="neutral"
          variant="ghost"
          class="mt-2 flex max-w-sm items-center gap-2.5 rounded-2xl border border-white/8 bg-white/6 px-3 py-2 text-left transition hover:bg-white/10"
          :ui="{ base: 'justify-start' }"
        >
          <span class="grid size-8 place-items-center rounded-xl bg-cyan-300/15 text-cyan-200">
            <UIcon name="i-lucide-paperclip" class="size-4" />
          </span>
          <span>
            <span class="block text-sm font-bold text-white">{{ message.attachment }}</span>
            <span class="block text-xs text-slate-500">Shared file</span>
          </span>
        </UButton>

        <div v-if="message.reactions?.length" class="mt-2 flex flex-wrap gap-1.5">
          <UButton
            v-for="reaction in message.reactions"
            :key="reaction.emoji"
            color="neutral"
            variant="ghost"
            class="inline-flex items-center gap-1.5 rounded-full border border-white/8 bg-white/6 px-2.5 py-0.5 text-sm transition hover:bg-white/10"
          >
            <span>{{ reaction.emoji }}</span>
            <span class="text-xs font-bold text-slate-300">{{ reaction.count }}</span>
          </UButton>
        </div>
      </div>

      <div class="absolute right-2 bottom-2 opacity-30">
        <UIcon v-if="message.status === 'sent'" name="i-lucide-check" class="siz-4" />
        <UIcon v-if="message.status === 'failed'" name="i-lucide-x" class="siz-4 text-red-500" title="Failed to send"/>
        <svg v-if="message.status === 'pending'" class="w-4 h-4 text-slate-300 animate-spin fill-indigo-800" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
          <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentFill"/>
        </svg>
      </div>
    </div>
  </article>
</template>
