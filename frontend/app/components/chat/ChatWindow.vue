<script setup lang="js">
import {computed, ref} from 'vue'
import {storeToRefs} from 'pinia'
import ChatMessage from '~/components/chat/ChatMessage.vue'
import MessageComposer from '~/components/chat/MessageComposer.vue'
import ServerStrip from '~/components/chat/ServerStrip.vue'
import MessageSkeleton from "~/components/chat/MessageSkeleton.vue";

const store = useServerStore()
const {activeChannelId, channelMessages, channelMeta, channelsLoading} = storeToRefs(store)

const activeMessageBucket = computed(() => channelMessages.value.get(activeChannelId.value) ?? null)
const hasMore = computed(() => channelMeta.value.get(activeChannelId.value)?.has_more_pages ?? false)
const channelLoading = computed(() => channelsLoading.value.has(activeChannelId.value))
const draft = ref('')
const scrollArea = useTemplateRef('chatWindow')
const messageLane = useTemplateRef('messageLane')
const smooth = ref(true)
const viewingHistory = ref(false)
const skeletonCount = ref(3)
const previousScrollHeight = ref(0)
const scrollBehavior = computed(() => smooth.value ? 'smooth' : 'auto')
const pendingInitialScrollChannelId = ref(null)

const {y, arrivedState} = useScroll(scrollArea, {
  behavior: scrollBehavior,
  offset: {bottom: 100},
})

useResizeObserver(messageLane, () => {
  if (!viewingHistory.value) {
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
  const channelId = activeChannelId.value

  if (!channelId) return

  smooth.value = false

  const prevY = y.value

  previousScrollHeight.value = scrollArea.value.scrollHeight

  await store.fetchActiveChannelHistory(channelId)

  const newScrollHeight = scrollArea.value.scrollHeight

  y.value = prevY + (newScrollHeight - previousScrollHeight.value)

  smooth.value = true
}

watchPostEffect(() => {
  viewingHistory.value = scrollArea.value ? y.value < scrollArea.value.scrollHeight - 1.8 * scrollArea.value.clientHeight : false
})

watch(activeChannelId, async (channelId) => {
  if (!channelId) {
    return
  }

  pendingInitialScrollChannelId.value = channelId

  if (!channelMessages.value.has(channelId)) {
    await store.fetchChannelMessages(channelId)
  }
}, { immediate: true })

watchPostEffect(() => {
  const channelId = pendingInitialScrollChannelId.value

  if (!channelId || activeChannelId.value !== channelId || !channelMessages.value.has(channelId)) {
    return
  }

  scrollToBottom()
  pendingInitialScrollChannelId.value = null
})

watch(arrivedState, (state) => {
  if (state.top && !channelLoading.value && hasMore.value && !pendingInitialScrollChannelId.value) {
    prependHistory()
  }
})
</script>

<template>
  <main
      class="chat-panel relative flex min-h-0 flex-col rounded-4xl border border-white/8 bg-slate-950/62 shadow-2xl shadow-black/25 backdrop-blur-xl">

    <ServerStrip/>
    <DevOnly>
      <div class="absolute left-2/6 w-1/6 top-3 gap-2 grid grid-cols-2 whitespace-nowrap">
        <div><span class="font-bold text-pink-600">Y:</span> {{ y }}</div>
        <div><span class="font-bold text-indigo-600">SHold:</span> {{ previousScrollHeight }}</div>
        <div><span class="font-bold text-green-700">SHc:</span> {{ scrollArea?.scrollHeight }}</div>
        <div><span class="font-bold text-cyan-800">STop:</span> {{ scrollArea?.scrollTop }}</div>
      </div>

      <div class="absolute left-1/2 w-1/6 top-3 gap-2 font-bold flex items-center">
        <div
class="size-6 text-center bg-pink-500 rounded-full shrink-0" :class="{'grayscale': !viewingHistory}"
             title="Away from bottom">J
        </div>
        <div>
          <div
class="size-6 text-center bg-blue-500 rounded-full mb-1" :class="{'grayscale': !arrivedState.top}"
               title="Chat Top">T
          </div>
          <div
class="size-6 text-center bg-purple-500 rounded-full" :class="{'grayscale': !arrivedState.bottom}"
               title="Chat Bottom">B
          </div>
        </div>
        <div>
          <div
class="size-6 text-center bg-fuchsia-500 rounded-full mb-1" :class="{'grayscale': !hasMore}"
               title="Partially loaded">P
          </div>
          <div
class="size-6 text-center bg-emerald-500 rounded-full" :class="{'grayscale': hasMore}"
               title="Fully loaded">
            D
          </div>
        </div>
        <div>
          <div
class="size-6 text-center bg-red-600 rounded-full shrink-0"
               :class="{'grayscale' : !pendingInitialScrollChannelId }"
               title="Settling channel scroll">H
          </div>
          <div
class="size-6 text-center bg-yellow-500 rounded-full shrink-0"
               :class="channelLoading ? 'animate-pulse' : 'grayscale'"
               title="Fetching">L
          </div>

        </div>
        <div
class="size-6 text-center bg-white text-black rounded-full shrink-0"
             title="Pending initial scroll channel id">{{ pendingInitialScrollChannelId }}
        </div>
      </div>
    </DevOnly>


    <div ref="chatWindow" class="flex-1 min-h-0 overflow-y-auto">
      <div ref="messageLane" class="flex min-h-full flex-col justify-end gap-1 px-4 py-4">
        <div v-if="hasMore">
          <MessageSkeleton v-for="n in skeletonCount" :key="n"/>
        </div>
        <ChatMessage
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
          class="shadow-slate-950 shadow-lg absolute -top-13 rounded-full hover:bg-slate-800 bg-slate-900 active:bg-slate-950 border
            border-slate-800 text-slate-200 py-2 px-6 font-bold left-1/2
            -translate-x-1/2" label="Jump back to present"
          @click="scrollToBottom"
      />
    </div>
  </main>
</template>
