import assert from 'node:assert/strict'
import test from 'node:test'

import {updatePendingMemberPresence} from '../app/utils/memberPresence.js'

test('adds and updates online members without duplicates', () => {
    const initial = updatePendingMemberPresence([], {user_id: 42, status: 'online'})
    const updated = updatePendingMemberPresence(initial, {user_id: 42, status: 'online', name: 'Alice'})

    assert.deepEqual(updated, [{user_id: 42, status: 'online', name: 'Alice'}])
})

test('removes an offline member from the pending online snapshot', () => {
    const initial = [
        {user_id: 42, status: 'online'},
        {user_id: 84, status: 'online'},
        {user_id: 42, status: 'online'},
    ]

    const updated = updatePendingMemberPresence(initial, {user_id: 42, status: 'offline'})

    assert.deepEqual(updated, [{user_id: 84, status: 'online'}])
})

test('collapses duplicate snapshot entries when an online member is updated', () => {
    const initial = [
        {user_id: 42, status: 'online'},
        {user_id: 42, status: 'online'},
    ]

    const updated = updatePendingMemberPresence(initial, {user_id: 42, status: 'online', name: 'Alice'})

    assert.deepEqual(updated, [{user_id: 42, status: 'online', name: 'Alice'}])
})

test('does not add an offline member absent from the snapshot', () => {
    const updated = updatePendingMemberPresence([], {user_id: 42, status: 'offline'})

    assert.deepEqual(updated, [])
})
