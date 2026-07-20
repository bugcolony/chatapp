import CreateCategoryModal from '../components/channels/CreateCategoryModal.vue'

export function useCreateCategoryModal() {
  const overlay = useOverlay()

  function openModal(props) {
    const modal = overlay.create(CreateCategoryModal, { destroyOnClose: true })

    return modal.open(props)
  }

  function openCreateCategoryModal(server) {
    return openModal({ server })
  }

  function openEditCategoryModal(server, category) {
    return openModal({ server, category })
  }

  return { openCreateCategoryModal, openEditCategoryModal }
}
