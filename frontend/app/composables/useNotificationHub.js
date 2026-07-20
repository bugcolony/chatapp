import { createGlobalState } from '@vueuse/core'

export const useNotificationHub = createGlobalState(() => {
  let player = null

  return {
    register: callback => player = callback,
    play: () => player?.(),
  }
})
