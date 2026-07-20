import LeaveServerModal from '~/components/servers/LeaveServerModal.vue'

export function useLeaveServerModal() {
  const overlay = useOverlay()

  function openLeaveServerModal(server) {
    const modal = overlay.create(LeaveServerModal, { destroyOnClose: true })

    return modal.open({ server })
  }

  return { openLeaveServerModal }
}
