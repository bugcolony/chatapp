import SettingsModal from "~/components/account/SettingsModal.vue";

export function useSettingsModal() {
    const overlay = useOverlay()

    function openSettingsModal() {
        const modal = overlay.create(SettingsModal, { destroyOnClose: true })

        return modal.open()
    }

    return { openSettingsModal }
}