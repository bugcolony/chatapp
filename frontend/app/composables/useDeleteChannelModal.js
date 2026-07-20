import DeleteChannelModal from '~/components/channels/DeleteChannelModal.vue'

export function useDeleteChannelModal() {
  const overlay = useOverlay()

  function openDeleteChannelModal(item, childCount = 0) {
    const modal = overlay.create(DeleteChannelModal, { destroyOnClose: true })

    return modal.open({ item, childCount })
  }

  return { openDeleteChannelModal }
}
