export function usePendingInvite() {
    const code = useSessionStorage('pending-invite-code', null)

    return {
        code,
        queue: inviteCode => code.value = inviteCode,
        clear: () => code.value = null,
    }
}