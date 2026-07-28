<script setup lang="js">
import { useDropZone, useObjectUrl } from '@vueuse/core'
import { computed, ref, shallowRef } from 'vue'
import MessageEmojiPicker from '~/components/messages/MessageEmojiPicker.vue'
import {
  messageEmojiExtension,
  messageEmojiMenuItems,
} from '~/utils/messageEmojiExtension'
import { fallbackAvatarSrc } from '~/composables/useServerAvatar.js'
import { isPreviewableImageType } from '~/utils/messageAttachment.js'
import { createMessageMentionExtension } from '~/utils/messageMention.js'

const MAX_ATTACHMENT_SIZE = 2_000_000

defineProps({
  placeholder: {
    type: String,
    default: 'Message',
  },
})

const emit = defineEmits(['send'])
const draft = defineModel({ type: String, default: '' })

const store = useServerStore()
const {activeServerId, serverMembers} = storeToRefs(store)
const toast = useToast()
const composerDropZone = useTemplateRef('composerDropZone')
const attachment = shallowRef(null)
const emojiOpen = ref(false)
const editorExtensions = [
  messageEmojiExtension,
  createMessageMentionExtension(resolveMentionLabel),
]
const emojiMenuFilterFields = [
  'name',
  'shortcodes',
  'tags',
  'searchAliases',
]
const emojiMenuOptions = {
  strategy: 'fixed',
  placement: 'top-start',
  offset: 8,
  flip: true,
  shift: { padding: 8 },
}
const emojiMenuSuggestion = {
  shouldShow: ({ query }) => query.trim().length > 0,
}

const trimmed = computed(() => draft.value.trim())
const canSend = computed(() => trimmed.value.length > 0 || attachment.value !== null)
const characterCount = computed(() => draft.value.length)
const showCount = computed(() => characterCount.value > 180)
const isPreviewableImage = computed(() =>
  isPreviewableImageType(attachment.value?.type),
)
const attachmentPreviewUrl = useObjectUrl(() =>
  isPreviewableImage.value ? attachment.value : null,
)

const members = computed(() => {
  return serverMembers.value[activeServerId.value] ? serverMembers.value[activeServerId.value].map((item) => {
    return {
      id: item.user.id,
      label: item.display_name,
      avatar: {
        src: fallbackAvatarSrc(item.display_name),
        loading: 'lazy'
      }
    }
  }) : []
})

function resolveMentionLabel(id) {
  return members.value.find(member => String(member.id) === String(id))?.label ?? null
}

const { isOverDropZone } = useDropZone(composerDropZone, {
  multiple: true,
  onDrop: handleDroppedFiles,
})

const editorProps = {
  handleKeyDown: onKeydown,
  handlePaste: onPaste,
}

function handleSend() {
  if (!canSend.value) return

  emit('send', {
    content: trimmed.value,
    attachment: attachment.value,
  })
  clearAttachment()
}

function onKeydown(view, event) {
  if (event.key !== 'Enter') return false

  if (event.shiftKey || event.isComposing || view.composing || event.keyCode === 229) {
    return false
  }

  const { $from } = view.state.selection
  const textBeforeCursor = $from.parent.textBetween(0, $from.parentOffset)

  if (
    $from.parent.type.spec.code
    || /^(?:```|~~~)[a-z]*$/.test(textBeforeCursor)
  ) {
    return false
  }

  event.preventDefault()
  handleSend()

  return true
}

function onPaste(_view, event) {
  const files = Array.from(event.clipboardData?.files ?? [])

  if (!files.length) return false

  event.preventDefault()
  handleDroppedFiles(files)

  return true
}

function insertEmoji(editor, emoji) {
  editor.chain().focus().insertContent(emoji).run()
  emojiOpen.value = false

  requestAnimationFrame(() => {
    editor.commands.focus()
  })
}

function getEmojiMenuContainer() {
  return document.body
}

function handleDroppedFiles(files) {
  if (!files?.length) return

  if (files.length > 1) {
    toast.add({
      title: 'Only one attachment allowed',
      description: 'Only the first file can be attached. Send the rest in separate messages.',
      icon: 'i-lucide-files',
      color: 'warning',
    })
  }

  setAttachment(files[0])
}

function setAttachment(file) {
  if (!file) {
    clearAttachment()

    return
  }

  if (file.size > MAX_ATTACHMENT_SIZE) {
    toast.add({
      title: 'File is too large',
      description: `${file.name} is ${formatFileSize(file.size)}. The limit is 2 MB.`,
      icon: 'i-lucide-file-warning',
      color: 'error',
    })

    return
  }

  attachment.value = file
}

function clearAttachment() {
  attachment.value = null
}

function formatFileSize(bytes) {
  if (!Number.isFinite(bytes) || bytes <= 0) return '0 B'

  const units = ['B', 'KB', 'MB', 'GB']
  const unitIndex = Math.min(
    Math.floor(Math.log(bytes) / Math.log(1000)),
    units.length - 1,
  )
  const value = bytes / 1000 ** unitIndex

  return `${value >= 10 || unitIndex === 0 ? value.toFixed(0) : value.toFixed(1)} ${units[unitIndex]}`
}

const appendToBody = import.meta.client ? () => document.body : undefined
</script>

<template>
  <div class="px-3 pb-3 pt-2 sm:px-4">
    <div
      ref="composerDropZone"
      class="relative mx-auto max-w-6xl"
    >
      <UEditor
        v-slot="{ editor }"
        v-model="draft"
        content-type="markdown"
        :placeholder="{ placeholder, mode: 'firstLine' }"
        :editor-props="editorProps"
        :extensions="editorExtensions"
        :mention="false"
        class="group/composer relative grid w-full grid-cols-[auto_minmax(0,1fr)_auto] items-end gap-x-1.5 rounded-2xl border bg-elevated/95 p-2 shadow-[0_18px_48px_rgba(0,0,0,0.24)] transition-[border-color,box-shadow,background-color] duration-200 focus-within:shadow-[0_20px_56px_rgba(0,0,0,0.32)]"
        :class="isOverDropZone
          ? 'border-primary bg-primary/8 ring-2 ring-primary/25'
          : 'border-muted focus-within:border-accented'"
        :ui="{
          content: 'col-start-2 row-start-2 h-auto min-w-0 self-stretch overflow-y-auto',
          base: 'min-h-12 max-h-40 px-2 py-2.5 text-[15px] leading-6 text-highlighted [&>*]:my-0 sm:px-2.5',
        }"
      >
        <UEditorEmojiMenu
          :editor="editor"
          :items="messageEmojiMenuItems"
          :filter-fields="emojiMenuFilterFields"
          :limit="12"
          :options="emojiMenuOptions"
          :suggestion="emojiMenuSuggestion"
          :append-to="getEmojiMenuContainer"
          plugin-key="messageEmojiMenu"
          size="lg"
          :ui="{
            content: 'w-[min(21rem,calc(100vw-1rem))] max-w-none max-h-72 rounded-xl border border-muted bg-elevated/98 shadow-2xl backdrop-blur',
            viewport: 'divide-y-0 p-1.5',
            group: 'p-0',
            item: 'items-center rounded-lg px-2 py-1',
            itemLeadingIcon: 'size-9 text-2xl',
            itemLabel: 'font-medium',
          }"
        />

        <UEditorMentionMenu :editor="editor" :items="members" :append-to="appendToBody" />
        <div
          v-if="attachment"
          class="col-span-3 row-start-1 mb-2 flex min-w-0 items-center gap-3 rounded-xl border border-muted bg-default/65 p-2 pr-10"
        >
          <div
            v-if="isPreviewableImage"
            class="h-20 w-28 shrink-0 overflow-hidden rounded-lg border border-muted bg-muted"
          >
            <img
              :src="attachmentPreviewUrl"
              :alt="attachment.name"
              class="size-full object-cover"
            >
          </div>

          <div
            v-else
            class="grid size-12 shrink-0 place-items-center rounded-xl bg-accented text-muted"
          >
            <UIcon name="i-lucide-file" class="size-5" />
          </div>

          <div class="min-w-0">
            <p class="truncate text-sm font-semibold text-highlighted">
              {{ attachment.name }}
            </p>
            <p class="mt-0.5 text-xs text-muted">
              {{ formatFileSize(attachment.size) }}
            </p>
          </div>

          <UButton
            type="button"
            icon="i-lucide-x"
            color="neutral"
            variant="ghost"
            size="sm"
            square
            :aria-label="`Remove ${attachment.name}`"
            title="Remove attachment"
            class="absolute right-3 top-3 rounded-lg text-muted hover:text-highlighted"
            @click="clearAttachment"
          />
        </div>

        <div class="col-start-1 row-start-2 mb-1 flex shrink-0 items-center gap-0.5">
          <UFileUpload
            :model-value="attachment"
            variant="button"
            :dropzone="false"
            :preview="false"
            reset
            @update:model-value="setAttachment"
          >
            <template #default="{ open }">
              <UButton
                type="button"
                icon="i-lucide-paperclip"
                color="neutral"
                variant="ghost"
                size="lg"
                square
                aria-label="Add attachment"
                title="Add attachment"
                class="size-9 rounded-xl text-muted transition hover:text-highlighted"
                :ui="{ leadingIcon: 'size-4.5' }"
                @click="open()"
              />
            </template>
          </UFileUpload>

          <UPopover
            v-model:open="emojiOpen"
            :content="{ side: 'top', align: 'start', sideOffset: 12 }"
            :ui="{ content: 'overflow-hidden rounded-2xl border border-muted bg-elevated p-0 shadow-2xl' }"
          >
            <UButton
              type="button"
              icon="i-lucide-smile"
              color="neutral"
              variant="ghost"
              size="lg"
              square
              aria-label="Add emoji"
              title="Add emoji"
              class="size-9 rounded-xl text-muted transition hover:text-highlighted"
              :ui="{ leadingIcon: 'size-4.5' }"
            />

            <template #content>
              <MessageEmojiPicker @select="insertEmoji(editor, $event)" />
            </template>
          </UPopover>
        </div>

        <div class="col-start-3 row-start-2 mb-1 flex shrink-0 items-center gap-2">
          <span
            v-if="showCount"
            class="hidden min-w-10 text-right text-[11px] font-semibold tabular-nums sm:inline"
            :class="characterCount > 420 ? 'text-warning' : 'text-dimmed'"
          >
            {{ characterCount }}
          </span>

          <UButton
            type="button"
            icon="i-lucide-arrow-up"
            color="neutral"
            :variant="canSend ? 'solid' : 'soft'"
            size="lg"
            square
            :disabled="!canSend"
            aria-label="Send message"
            aria-keyshortcuts="Enter"
            title="Send message"
            class="size-10 rounded-xl transition-transform active:scale-95 disabled:opacity-45"
            :ui="{ leadingIcon: 'size-5' }"
            @click="handleSend"
          />
        </div>
      </UEditor>

      <div
        v-if="isOverDropZone"
        class="pointer-events-none absolute inset-0 z-20 grid place-items-center rounded-2xl border-2 border-dashed border-primary bg-elevated/95 p-4 text-center shadow-2xl backdrop-blur-sm"
      >
        <div>
          <UIcon name="i-lucide-cloud-upload" class="mx-auto size-7 text-primary" />
          <p class="mt-2 text-sm font-semibold text-highlighted">
            Drop file to attach
          </p>
          <p class="mt-0.5 text-xs text-muted">
            One file per message, up to 2 MB
          </p>
        </div>
      </div>
    </div>
  </div>
</template>
