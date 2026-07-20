import CreateChannelModal from '~/components/channels/CreateChannelModal.vue'

export function useCreateChannelModal() {
  const overlay = useOverlay()

  function openModal(props) {
    const modal = overlay.create(CreateChannelModal, { destroyOnClose: true })

    return modal.open(props)
  }

  function openCreateChannelModal(server, parentId = null) {
    return openModal({ server, parentId })
  }

  function openEditChannelModal(server, channel) {
    return openModal({ server, channel, parentId: channel.parent_id ?? null })
  }

  return { openCreateChannelModal, openEditChannelModal }
}
