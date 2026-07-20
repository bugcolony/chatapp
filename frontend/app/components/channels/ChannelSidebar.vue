<script setup lang="js">
import { computed } from 'vue'
import { storeToRefs } from 'pinia'
import AppSidebar from '~/components/layout/AppSidebar.vue'
import ActiveServerSwitcher from '~/components/servers/ActiveServerSwitcher.vue'
import { useChatUIStore } from '~/stores/chatUIStore.js'
import ChannelListItem from '~/components/channels/ChannelListItem.vue'
import { useCreateChannelModal } from '~/composables/useCreateChannelModal.js'
import { useCreateCategoryModal } from '~/composables/useCreateCategoryModal.js'
import { useDeleteChannelModal } from '~/composables/useDeleteChannelModal.js'
// import { useSortable } from "@vueuse/integrations/useSortable";

const store = useServerStore()
const uiStore = useChatUIStore()

const { activeServerId, activeChannelId, serverChannels, activeServer } = storeToRefs(store)
const { leftSidebarOpen } = storeToRefs(uiStore)
const { openCreateChannelModal, openEditChannelModal } = useCreateChannelModal()
const { openEditCategoryModal } = useCreateCategoryModal()
const { openDeleteChannelModal } = useDeleteChannelModal()
const toast = useToast()
// const channelListElement = useTemplateRef('channelList')

const activeServerChannels = computed(() => serverChannels.value[activeServerId.value] ?? [])
const flatChannelList = computed(() => {
  const categories = activeServerChannels.value.filter(item => item.type === 'category')
  const channels = activeServerChannels.value.filter(item => item.type === 'text')
  const grouped = Object.groupBy(channels, item => item.parent_id ?? 0)
  const list = []

  if (grouped[0]) {
    list.push(...grouped[0])
  }

  categories.forEach(cat => {
    list.push(cat)

    let chList = grouped[cat.id]

    if (!chList) {
      chList = []
    }

    list.push(...chList)
  })

  return list
})


// useSortable(channelListElement, flatChannelList, {
//   onUpdate: (e) => {
//     console.log(flatChannelList.value[e.oldIndex].name)
//     return null
//   }
// })

function handleAddChannelClick(id) {
  if (activeServer.value) {
    openCreateChannelModal(activeServer.value, id)
  }
}

function handleEditItem(item) {
  if (!activeServer.value) {
    return
  }

  if (item.type === 'category') {
    openEditCategoryModal(activeServer.value, item)

    return
  }

  openEditChannelModal(activeServer.value, item)
}

async function handleDeleteItem(item) {
  const serverId = activeServerId.value

  if (!serverId) {
    return
  }

  const childCount = item.type === 'category'
    ? activeServerChannels.value.filter(channel => channel.parent_id === item.id).length
    : 0
  const confirmedItem = await openDeleteChannelModal(item, childCount)

  if (!confirmedItem) {
    return
  }

  try {
    await store.deleteServerChannel(serverId, confirmedItem.id)

    if (confirmedItem.id === activeChannelId.value) {
      await navigateTo(`/app/servers/${serverId}`)
    }

    toast.add({
      title: confirmedItem.type === 'category' ? 'Category deleted' : 'Channel deleted',
      description: `${confirmedItem.name} has been removed.`,
      color: 'success',
      icon: 'i-lucide-check',
    })
  } catch (error) {
    console.error('Failed to delete channel:', error)
    toast.add({
      title: `Could not delete ${confirmedItem.type === 'category' ? 'category' : 'channel'}`,
      description: error?.data?.message ?? 'Try again in a moment.',
      color: 'error',
      icon: 'i-lucide-triangle-alert',
    })
  }
}

async function handleSelectChannel(channelId) {
  if (!activeServerId.value) {
    return
  }
  await navigateTo(`/app/servers/${activeServerId.value}/c/${channelId}`)
}

watchEffect(() => {
  if (activeServerId.value && !serverChannels.value[activeServerId.value]) {
    store.fetchServerChannels(activeServerId.value)
  }
})

</script>

<template>
  <AppSidebar
    v-model:open="leftSidebarOpen"
    side="left"
    width="308px"
  >
    <template #header>
      <div class="flex flex-col gap-3 w-full">
        <div class="flex justify-end lg:hidden">
          <UButton
            icon="i-lucide-x"
            color="neutral"
            variant="ghost"
            aria-label="Close channels panel"
            class="rounded-xl bg-white/8 text-white hover:bg-white/12"
            @click="leftSidebarOpen = false"
          />
        </div>

        <ActiveServerSwitcher />
      </div>
    </template>

    <div class="space-y-1 pr-1" ref="channelList">
      <ChannelListItem
        v-for="item in flatChannelList"
        :key="item.id"
        :item="item"
        :active="activeChannelId === item.id"
        @select="handleSelectChannel"
        @add-channel="handleAddChannelClick"
        @edit="handleEditItem"
        @delete="handleDeleteItem"
      />
    </div>
  </AppSidebar>
</template>
