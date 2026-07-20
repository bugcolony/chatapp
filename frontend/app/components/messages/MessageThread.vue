<script setup lang="js">
import { computed, ref } from 'vue'
import { storeToRefs } from 'pinia'
import MessageComposer from '~/components/messages/MessageComposer.vue'
import MessageItem from '~/components/messages/MessageItem.vue'
import MessageSkeleton from '~/components/messages/MessageSkeleton.vue'
import PinnedServerBar from '~/components/servers/PinnedServerBar.vue'

const props = defineProps({
  channelId: {
    type: Number,
    required: true,
  },
})

const channelId = props.channelId
const store = useServerStore()
const {channelMessages, channelMeta, channelsLoading} = storeToRefs(store)

const activeMessageBucket = computed(() => channelMessages.value.get(channelId) ?? null)
const hasMore = computed(() => channelMeta.value.get(channelId)?.has_more_pages ?? false)
const channelLoading = computed(() => channelsLoading.value.has(channelId))
const draft = ref('')
const scrollArea = useTemplateRef('chatWindow')
const messageLane = useTemplateRef('messageLane')
const smooth = ref(false)
const viewingHistory = ref(false)
const skeletonCount = ref(3)
const previousScrollHeight = ref(0)
const scrollBehavior = computed(() => smooth.value ? 'smooth' : 'auto')
const scrollAdjusted = ref(false)
const pendingHistoryScroll = ref(null)

const {y, arrivedState} = useScroll(scrollArea, {
  behavior: scrollBehavior,
  offset: {bottom: 100},
})

useResizeObserver(messageLane, () => {
  if (pendingHistoryScroll.value?.ready) {
    restoreHistoryScroll(pendingHistoryScroll.value)
    return
  }

  if (pendingHistoryScroll.value) {
    return
  }

  if (scrollAdjusted.value && !viewingHistory.value) {
    scrollToBottom()
  }
})

function scrollToBottom() {
  if (!scrollArea.value) return

  // smooth.value = false

  y.value = scrollArea.value.scrollHeight

  // smooth.value = true
}

function handleSend() {
  store.sendMessage(draft.value)
  draft.value = ''
}

async function prependHistory() {
  const area = scrollArea.value

  if (!channelId || !area || pendingHistoryScroll.value) return

  smooth.value = false
  previousScrollHeight.value = area.scrollHeight
  pendingHistoryScroll.value = {
    ready: false,
    top: area.scrollTop,
    height: area.scrollHeight,
  }

  try {
    await store.fetchActiveChannelHistory(channelId)
  } finally {
    if (scrollArea.value !== area || !pendingHistoryScroll.value) {
      pendingHistoryScroll.value = null
      smooth.value = true
    } else {
      pendingHistoryScroll.value = {
        ...pendingHistoryScroll.value,
        ready: true,
      }
    }
  }
}

function restoreHistoryScroll(anchor) {
  if (!scrollArea.value || pendingHistoryScroll.value !== anchor) return

  y.value = anchor.top + (scrollArea.value.scrollHeight - anchor.height)
  pendingHistoryScroll.value = null
  smooth.value = true
}

watchPostEffect(() => {
  if (scrollAdjusted.value || activeMessageBucket.value === null || !scrollArea.value) {
    return
  }

  scrollToBottom()
  scrollAdjusted.value = true
  smooth.value = true
})

watchPostEffect(() => {
  const anchor = pendingHistoryScroll.value

  if (!anchor?.ready || !scrollArea.value || scrollArea.value.scrollHeight !== anchor.height) {
    return
  }

  restoreHistoryScroll(anchor)
})

watchPostEffect(() => {
  viewingHistory.value = scrollAdjusted.value && scrollArea.value
    ? y.value < scrollArea.value.scrollHeight - 1.8 * scrollArea.value.clientHeight
    : false
})

onMounted(async () => {
  if (!channelMessages.value.has(channelId)) {
    await store.fetchChannelMessages(channelId)
  }
})

watch(() => arrivedState.top, (atTop, wasAtTop) => {
  if (atTop && !wasAtTop && scrollAdjusted.value && !channelLoading.value && hasMore.value) {
    prependHistory()
  }
})
</script>

<template>
  <main class="chat-panel relative flex min-h-0 flex-col rounded-4xl border border-white/8 bg-slate-950/62 shadow-2xl shadow-black/25 backdrop-blur-xl">
    <PinnedServerBar />

    <DevOnly>
      <div class="absolute left-2/6 top-3 grid w-1/6 grid-cols-2 gap-2 whitespace-nowrap">
        <div><span class="font-bold text-pink-600">Y:</span> {{ y }}</div>
        <div><span class="font-bold text-indigo-600">SHold:</span> {{ previousScrollHeight }}</div>
        <div><span class="font-bold text-green-700">SHc:</span> {{ scrollArea?.scrollHeight }}</div>
        <div><span class="font-bold text-cyan-800">STop:</span> {{ scrollArea?.scrollTop }}</div>
      </div>

      <div class="absolute left-1/2 top-3 flex w-1/6 items-center gap-2 font-bold">
        <div
          class="size-6 shrink-0 rounded-full bg-pink-500 text-center"
          :class="{ grayscale: !viewingHistory }"
          title="Away from bottom"
        >
          J
        </div>
        <div>
          <div
            class="mb-1 size-6 rounded-full bg-blue-500 text-center"
            :class="{ grayscale: !arrivedState.top }"
            title="Chat Top"
          >
            T
          </div>
          <div
            class="size-6 rounded-full bg-purple-500 text-center"
            :class="{ grayscale: !arrivedState.bottom }"
            title="Chat Bottom"
          >
            B
          </div>
        </div>
        <div>
          <div
            class="mb-1 size-6 rounded-full bg-fuchsia-500 text-center"
            :class="{ grayscale: !hasMore }"
            title="Partially loaded"
          >
            P
          </div>
          <div
            class="size-6 rounded-full bg-emerald-500 text-center"
            :class="{ grayscale: hasMore }"
            title="Fully loaded"
          >
            D
          </div>
        </div>
        <div>
          <div
            class="mb-1 size-6 shrink-0 rounded-full bg-red-600 text-center"
            :class="{ grayscale: scrollAdjusted }"
            title="Settling channel scroll"
          >
            S
          </div>
          <div
            class="size-6 shrink-0 rounded-full bg-yellow-500 text-center"
            :class="channelLoading ? 'animate-pulse' : 'grayscale'"
            title="Fetching"
          >
            L
          </div>
        </div>
        <div
          class="size-6 shrink-0 rounded-full bg-white text-center text-black"
          title="Mounted channel id"
        >
          {{ channelId }}
        </div>
      </div>
    </DevOnly>

    <div ref="chatWindow" class="min-h-0 flex-1 overflow-y-auto">
      <div ref="messageLane" class="flex min-h-full flex-col justify-end gap-1 px-4 py-4">
        <div v-if="hasMore">
          <MessageSkeleton
            v-for="n in skeletonCount"
            :key="n"
          />
        </div>
        <MessageItem
          v-for="[id, message] in activeMessageBucket ?? []"
          :key="id"
          :message="message"
        />
      </div>
    </div>

    <div class="relative">
      <MessageComposer
        v-model="draft"
        class="mb-10"
        @send="handleSend"
      />

      <UButton
        v-if="viewingHistory"
        variant="ghost"
        icon="i-lucide-arrow-down"
        class="absolute -top-13 left-1/2 -translate-x-1/2 rounded-full border border-slate-800 bg-slate-900 px-6 py-2 font-bold text-slate-200 shadow-lg shadow-slate-950 hover:bg-slate-800 active:bg-slate-950"
        label="Jump back to present"
        @click="scrollToBottom()"
      />
    </div>
  </main>
</template>
