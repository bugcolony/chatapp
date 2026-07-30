<script setup lang="js">
/* eslint-disable vue/no-v-html -- Rendered Markdown is sanitized with DOMPurify. */
import DOMPurify from 'dompurify'
import emojiRegex from 'emoji-regex'
import MarkdownIt from 'markdown-it'
import ServerInvitePreview from '~/components/invites/ServerInvitePreview.vue'
import MessageAttachment from '~/components/messages/MessageAttachment.vue'
import { fallbackAvatarSrc } from '~/composables/useServerAvatar.js'
import { useAppUrl } from '~/composables/useAppUrl.js'
import { extractInviteCodes } from '~/utils/extractInviteCodes.js'
import { installMessageMentionMarkdown } from '~/utils/messageMention.js'

const JUMBO_EMOJI_LIMIT = 27
const SANITIZE_OPTIONS = {
  ADD_ATTR: ['referrerpolicy'],
}

const props = defineProps({
  message: {
    type: Object,
    required: true,
  },
})
const created = useDateFormat(props.message.created_at, 'DD.MM.YYYY HH:mm')
const { appUrl } = useAppUrl()
const messageContent = computed(() => String(props.message.message ?? ''))
const inviteCodes = computed(() => extractInviteCodes(messageContent.value, appUrl))
const emojiOnlyCount = computed(() => countEmojiOnlyMessage(props.message.message))
const isJumboEmojiMessage = computed(() => emojiOnlyCount.value > 0)
const sanitizedContent = computed(() =>
  DOMPurify.sanitize(
    renderMessageContent(messageContent.value),
    SANITIZE_OPTIONS,
  ),
)
const store = useServerStore()
const { activeServerId, serverMembers } = storeToRefs(store)
const mentionLabels = computed(() => new Map(
  (serverMembers.value[activeServerId.value] ?? []).map(member => [
    String(member.user.id),
    member.display_name,
  ]),
))

function resolveMentionLabel(id) {
  return mentionLabels.value.get(String(id)) ?? null
}

const md = new MarkdownIt({
  html: false,
  linkify: true,
  breaks: true,
})

installMessageMentionMarkdown(md, resolveMentionLabel)

const defaultImageRenderer = md.renderer.rules.image

md.renderer.rules.image = (tokens, index, options, env, renderer) => {
  tokens[index].attrSet('loading', 'lazy')
  tokens[index].attrSet('decoding', 'async')
  tokens[index].attrSet('referrerpolicy', 'no-referrer')

  return defaultImageRenderer
    ? defaultImageRenderer(tokens, index, options, env, renderer)
    : renderer.renderToken(tokens, index)
}

md.renderer.rules.text = (tokens, index) => {
  const text = md.utils.escapeHtml(tokens[index].content)

  return text.replace(
    emojiRegex(),
    nativeEmoji => `<span class="rendered-emoji">${nativeEmoji}</span>`,
  )
}

function renderMessageContent(content) {
  const rendered = md.render(content)
  const visibleText = rendered.replace(/<[^>]*>/g, '').trim()

  return content.trim() && !visibleText
    ? md.renderInline(content)
    : rendered
}

function countEmojiOnlyMessage(content) {
  const text = String(content ?? '').trim()
  if (!text) return 0

  const matches = text.match(emojiRegex()) ?? []
  if (!matches.length || matches.length > JUMBO_EMOJI_LIMIT) return 0

  const nonEmojiContent = text
    .replace(emojiRegex(), '')
    .replace(/\s/g, '')

  return nonEmojiContent ? 0 : matches.length
}
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

        <div
          v-if="sanitizedContent"
          class="message-content mt-1.5 max-w-4xl text-sm leading-5 wrap-break-word"
          :class="[
            message.status === 'pending' || message.status === 'failed'
              ? 'text-slate-600'
              : 'text-slate-200',
            { 'message-content--jumbo': isJumboEmojiMessage },
          ]"
          v-html="sanitizedContent"
        />

        <ServerInvitePreview
          v-for="code in inviteCodes"
          :key="code"
          :code="code"
        />

        <MessageAttachment :attachment="message.attachment" />

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

<style scoped>
.message-content :deep(.rendered-emoji) {
  display: inline-block;
  font-size: 1.5em;
  vertical-align: -0.1em;
}

.message-content :deep(.message-mention) {
  border-radius: 0.25rem;
  background: color-mix(in srgb, var(--ui-primary) 18%, transparent);
  padding: 2px 6px;
  color: var(--ui-primary);
  font-weight: 600;
}

.message-content :deep(img) {
  display: block;
  max-width: min(100%, 32rem);
  max-height: 22rem;
  margin-top: 0.5rem;
  border: 1px solid color-mix(in srgb, var(--ui-border) 75%, transparent);
  border-radius: 0.875rem;
  background: var(--ui-bg-muted);
  object-fit: contain;
  box-shadow: 0 12px 32px rgb(0 0 0 / 18%);
}

.message-content--jumbo {
  line-height: 1.2;
}

.message-content--jumbo :deep(.rendered-emoji) {
  margin: 0 0.025em 0.08em 0;
  font-size: 2.5rem;
  vertical-align: middle;
}

@media (max-width: 640px) {
  .message-content--jumbo :deep(.rendered-emoji) {
    font-size: 2.25rem;
  }
}
</style>
