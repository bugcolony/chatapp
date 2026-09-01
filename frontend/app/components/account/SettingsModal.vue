<script setup lang="js">
import { markRaw } from 'vue'
import AccountSection from '~/components/account/settings/AccountSection.vue'
import ProfileSection from '~/components/account/settings/ProfileSection.vue'
import { provideSettingsSection } from '~/composables/useSettingsSection.js'

const open = defineModel('open', { type: Boolean, default: false })

const auth = useAuthStore()
const socket = useSocketStore()

const sections = [
  {
    id: 'account',
    label: 'Account',
    icon: 'i-lucide-user',
    component: markRaw(AccountSection),
  },
  {
    id: 'profile',
    label: 'Profile',
    icon: 'i-lucide-id-card',
    component: markRaw(ProfileSection),
  },
]

const activeSection = ref(sections[0].id)
const nudging = ref(false)

const form = provideSettingsSection()

const activeComponent = computed(
  () => sections.find(section => section.id === activeSection.value)?.component,
)

const { start: startNudge } = useTimeoutFn(() => {
  nudging.value = false
}, 700, { immediate: false })

function guard(action) {
  if (!form.isDirty.value) {
    action()
    return
  }

  nudging.value = true
  startNudge()
}

function selectSection(id) {
  if (id === activeSection.value) return

  guard(() => {
    activeSection.value = id
  })
}

function close() {
  guard(() => {
    open.value = false
  })
}

async function handleLogout() {
  socket.disconnect()
  await auth.logout()

  open.value = false
  await navigateTo('/')
}
</script>

<template>
  <UModal
    v-model:open="open"
    fullscreen
    :dismissible="!form.isDirty.value"
    :ui="{ content: 'bg-[#0b1114] text-slate-100', body: 'p-0' }"
  >
    <template #content>
      <div class="flex h-full min-h-0">
        <nav class="hidden shrink-0 grow basis-64 justify-end border-r border-white/8 bg-black/20 sm:flex">
          <div class="flex w-64 flex-col px-3 py-8">
            <p class="px-2 text-[0.65rem] font-black uppercase tracking-[0.18em] text-dimmed">
              User settings
            </p>

            <ul class="mt-3 space-y-0.5">
              <li v-for="section in sections" :key="section.id">
                <UButton
                  type="button"
                  :icon="section.icon"
                  :label="section.label"
                  color="neutral"
                  :variant="activeSection === section.id ? 'soft' : 'ghost'"
                  block
                  :ui="{ base: 'justify-start rounded-lg font-bold' }"
                  @click="selectSection(section.id)"
                />
              </li>
            </ul>

            <div class="mt-auto border-t border-white/8 pt-3">
              <UButton
                type="button"
                icon="i-lucide-log-out"
                label="Log out"
                color="error"
                variant="ghost"
                block
                :ui="{ base: 'justify-start rounded-lg font-bold' }"
                @click="handleLogout"
              />
            </div>
          </div>
        </nav>

        <section class="chat-panel relative min-w-0 grow basis-[46rem] overflow-y-auto">
          <div class="sticky top-0 z-10 flex justify-end bg-[#0b1114]/90 p-4 backdrop-blur">
            <div class="flex flex-col items-center gap-1">
              <UButton
                type="button"
                icon="i-lucide-x"
                color="neutral"
                variant="outline"
                aria-label="Close settings"
                class="rounded-full"
                @click="close"
              />
              <span class="text-[0.6rem] font-black uppercase tracking-[0.18em] text-dimmed">Esc</span>
            </div>
          </div>

          <div class="w-full max-w-2xl px-6 pb-12 sm:px-10">
            <component :is="activeComponent" :key="activeSection" />

            <Transition
              enter-active-class="transition duration-200 ease-out"
              enter-from-class="translate-y-3 opacity-0"
              leave-active-class="transition duration-200 ease-out"
              leave-to-class="translate-y-3 opacity-0"
            >
              <div
                v-if="form.isDirty.value"
                class="sticky bottom-6 z-20 mt-6 flex items-center justify-between gap-4 rounded-2xl bg-default p-3 pl-5 shadow-2xl shadow-black/50 ring ring-default transition"
                :class="nudging ? 'ring-2 ring-warning' : ''"
                role="status"
              >
                <p class="text-sm font-bold text-toned">Unsaved changes</p>

                <div class="flex shrink-0 gap-2">
                  <UButton
                    type="button"
                    label="Reset"
                    color="neutral"
                    variant="ghost"
                    :disabled="form.loading.value"
                    @click="form.reset()"
                  />
                  <UButton
                    type="button"
                    label="Save changes"
                    color="primary"
                    :loading="form.loading.value"
                    @click="form.submit()"
                  />
                </div>
              </div>
            </Transition>
          </div>
        </section>
      </div>
    </template>
  </UModal>
</template>
