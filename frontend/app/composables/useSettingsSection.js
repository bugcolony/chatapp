export const settingsSectionKey = Symbol('settingsSection')

export function useDirtyForm(source) {
  const baseline = ref(source())
  const state = reactive({ ...baseline.value })

  const isDirty = computed(() =>
    Object.keys(baseline.value).some(key => state[key] !== baseline.value[key]),
  )

  function reset() {
    Object.assign(state, baseline.value)
  }

  function commit(next = null) {
    baseline.value = next ?? { ...toRaw(state) }
    reset()
  }

  return { state, isDirty, reset, commit }
}

export function useSettingsSection(api) {
  const registry = inject(settingsSectionKey, null)

  if (!registry) return

  registry.register(api)

  onUnmounted(() => registry.unregister(api))
}

export function provideSettingsSection() {
  const section = shallowRef(null)

  provide(settingsSectionKey, {
    register(api) {
      section.value = api
    },
    unregister(api) {
      if (section.value === api) {
        section.value = null
      }
    },
  })

  return {
    isDirty: computed(() => Boolean(toValue(section.value?.isDirty))),
    loading: computed(() => Boolean(toValue(section.value?.loading))),
    submit: () => section.value?.submit(),
    reset: () => section.value?.reset(),
  }
}
