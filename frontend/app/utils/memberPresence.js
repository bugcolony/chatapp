export function updatePendingMemberPresence(members = [], userState) {
    const index = members.findIndex((member) => member.id === userState.id)

    if (userState.status === 'online') {
        if (index === -1) {
            return [...members, userState]
        }

        return [
            ...members.slice(0, index),
            {...members[index], ...userState},
            ...members.slice(index + 1).filter((member) => member.id !== userState.id),
        ]
    }

    if (userState.status === 'offline') {
        return members.filter((member) => member.id !== userState.id)
    }

    return members
}
