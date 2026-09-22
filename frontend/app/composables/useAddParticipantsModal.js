import AddParticipantsModal from '~/components/channels/AddParticipantsModal.vue'

export function useAddParticipantsModal() {
  const overlay = useOverlay()

  function openAddParticipantsModal(channel) {
    const modal = overlay.create(AddParticipantsModal, { destroyOnClose: true })

    return modal.open({ channel })
  }

  return { openAddParticipantsModal }
}
