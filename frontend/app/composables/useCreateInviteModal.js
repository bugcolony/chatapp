import CreateInviteModal from '~/components/invites/CreateInviteModal.vue'

export function useCreateInviteModal() {
  const overlay = useOverlay()

  function openCreateInviteModal(server) {
    const modal = overlay.create(CreateInviteModal, { destroyOnClose: true })

    return modal.open({ server })
  }

  return { openCreateInviteModal }
}
