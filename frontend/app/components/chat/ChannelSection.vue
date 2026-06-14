<script setup lang="js">
import ChannelListItem from '~/components/chat/ChannelListItem.vue'

defineProps({
  section: { type: Object, required: true },
  activeId: { type: [String, Number], default: null },
})

defineEmits(['select'])
</script>

<template>
  <section>
    <div class="mb-1.5 flex items-center justify-between px-1">
      <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-500">
        {{ section.title }}
      </p>
      <UIcon
        name="i-lucide-plus"
        class="size-3.5 text-slate-500"
      />
    </div>

    <div class="space-y-1">
      <ChannelListItem
        v-for="item in section.items"
        :key="item.id"
        :item="item"
        :active="item.id === activeId"
        @select="$emit('select', $event)"
      />
    </div>
  </section>
</template>
