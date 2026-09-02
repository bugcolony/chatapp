import DeleteAccountModal from '~/components/account/settings/DeleteAccountModal.vue'

export function useDeleteAccountModal() {
  const overlay = useOverlay()

  function openDeleteAccountModal(username, ownedServersCount = 0) {
    const modal = overlay.create(DeleteAccountModal, { destroyOnClose: true })

    return modal.open({ username, ownedServersCount })
  }

  return { openDeleteAccountModal }
}
