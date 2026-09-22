import { userAvatarSrc } from '~/composables/useServerAvatar.js'

export function useMentionCandidates(channelId) {
  const store = useServerStore()
  const { activeServerId, serverMembers, directChannels } = storeToRefs(store)

  return computed(() => {
    const members = serverMembers.value[activeServerId.value]

    if (members) {
      return members.map(member => ({
        id: member.user.id,
        label: member.display_name,
        src: userAvatarSrc(member),
      }))
    }

    const participants = directChannels.value.get(toValue(channelId))?.participants ?? []

    return participants.map(participant => ({
      id: participant.id,
      label: participant.name,
      src: userAvatarSrc(participant),
    }))
  })
}
