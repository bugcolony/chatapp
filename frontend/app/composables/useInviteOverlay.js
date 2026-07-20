import ServerInviteModal from '~/components/invites/ServerInviteModal.vue'

let inviteModal

export function useInviteOverlay() {
  const overlay = useOverlay()

  inviteModal ??= overlay.create(ServerInviteModal)

  function openInvite(code) {
    inviteModal.open({ code })
  }

  return { openInvite }
}
