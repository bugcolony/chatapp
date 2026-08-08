import assert from 'node:assert/strict'
import test from 'node:test'

import {updatePendingMemberPresence} from '../app/utils/memberPresence.js'

test('adds and updates online members without duplicates', () => {
    const initial = updatePendingMemberPresence([], {id: 42, status: 'online'})
    const updated = updatePendingMemberPresence(initial, {id: 42, status: 'online', name: 'Alice'})

    assert.deepEqual(updated, [{id: 42, status: 'online', name: 'Alice'}])
})

test('removes an offline member from the pending online snapshot', () => {
    const initial = [
        {id: 42, status: 'online'},
        {id: 84, status: 'online'},
        {id: 42, status: 'online'},
    ]

    const updated = updatePendingMemberPresence(initial, {id: 42, status: 'offline'})

    assert.deepEqual(updated, [{id: 84, status: 'online'}])
})

test('collapses duplicate snapshot entries when an online member is updated', () => {
    const initial = [
        {id: 42, status: 'online'},
        {id: 42, status: 'online'},
    ]

    const updated = updatePendingMemberPresence(initial, {id: 42, status: 'online', name: 'Alice'})

    assert.deepEqual(updated, [{id: 42, status: 'online', name: 'Alice'}])
})

test('does not add an offline member absent from the snapshot', () => {
    const updated = updatePendingMemberPresence([], {id: 42, status: 'offline'})

    assert.deepEqual(updated, [])
})
