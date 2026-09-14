export function updatePendingMemberPresence(members = [], userState) {
    const index = members.findIndex((member) => member.user_id === userState.user_id)

    if (userState.status === 'online') {
        if (index === -1) {
            return [...members, userState]
        }

        return [
            ...members.slice(0, index),
            {...members[index], ...userState},
            ...members.slice(index + 1).filter((member) => member.user_id !== userState.user_id),
        ]
    }

    if (userState.status === 'offline') {
        return members.filter((member) => member.user_id !== userState.user_id)
    }

    return members
}
