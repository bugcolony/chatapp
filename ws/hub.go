package blueberry

import (
	"encoding/json"
	"time"
)

type Hub struct {
	register             chan *Client
	subscribe            chan *Client
	unsubscribe          chan *Client
	subscribeToServer    chan *SubscribeServerCommand
	broadcast            chan Broadcast
	activateServer       chan *SetActiveServerCommand
	typeReg              chan *SetTypingPresenceCommand
	users                map[int]map[*Client]bool
	connections          map[*Client]bool
	serverSubscriptions  map[int]map[*Client]bool
	typePresenceReg      map[*Client]*TypingState
	activeServerByClient map[*Client]int
	activeServerClients  map[int]map[*Client]bool
}

func (h *Hub) closeConnections(client *Client) {
	if _, exists := h.connections[client]; !exists {
		return
	}

	h.closeClientServerSubscriptions(client)
	h.changeActiveClientServer(&SetActiveServerCommand{client, nil})

	delete(h.connections, client)

	h.closeUserClient(client)

	close(client.send)

	if _, exists := h.users[client.user.Id]; !exists {
		h.broadcastMemberStateChange(client, MemberStatusOffline)
	}
}

func (h *Hub) closeClientServerSubscriptions(client *Client) {
	for _, serverId := range client.serverSubscriptions {
		if _, exists := h.serverSubscriptions[serverId]; !exists {
			continue
		}

		delete(h.serverSubscriptions[serverId], client)

		if len(h.serverSubscriptions[serverId]) == 0 {
			delete(h.serverSubscriptions, serverId)
		}
	}
}

func (h *Hub) closeUserClient(client *Client) {
	if _, exists := h.users[client.user.Id]; exists {
		delete(h.users[client.user.Id], client)

		if len(h.users[client.user.Id]) == 0 {
			delete(h.users, client.user.Id)
		}
	}
}

func (h *Hub) broadcastMemberStateChange(client *Client, status string) {
	for _, serverId := range client.serverSubscriptions {
		h.broadcastServerMemberStateChange(serverId, client, status)
	}
}

func (h *Hub) broadcastServerMemberStateChange(serverId int, client *Client, status string) {
	p, err := json.Marshal(ServerMemberStatusChangeEvent{OpGatewayMemberStatusChanged, serverId, MemberState{
		Id:     client.user.Id,
		Status: status,
	}})

	if err != nil {
		return
	}

	h.broadcastMessageTo(h.activeServerClients[serverId], Broadcast{serverId, p})
}

func (h *Hub) broadcastTypePresenceEventStart(client *Client, ts *TypingState) {
	h.broadcastTypePresenceEvent(client, ts, OpClientTypingStart)
}

func (h *Hub) broadcastTypePresenceEventStop(client *Client, ts *TypingState) {
	h.broadcastTypePresenceEvent(client, ts, OpClientTypingStop)
}

func (h *Hub) broadcastTypePresenceEvent(client *Client, ts *TypingState, op int) {
	p, err := json.Marshal(TypePresenceEvent{
		Op:              op,
		TargetServerId:  ts.ServerId,
		TargetChannelId: ts.ChannelId,
		Data:            map[string]any{"id": client.user.Id},
	})

	if err != nil {
		return
	}

	h.broadcastMessageTo(h.activeServerClients[ts.ServerId], Broadcast{ts.ChannelId, p})
}

func (h *Hub) broadcastMessageTo(clients map[*Client]bool, msg Broadcast) {
	for client := range clients {
		if !h.connections[client] {
			continue
		}
		select {
		case client.send <- msg.data:
		default:
			// buffer full - close connections
			h.closeConnections(client)
		}
	}
}

func (h *Hub) changeActiveClientServer(op *SetActiveServerCommand) {
	old, exists := h.activeServerByClient[op.client]

	if exists {
		if op.serverId == nil {
			delete(h.activeServerByClient, op.client)
		}

		if op.serverId != nil && old == *op.serverId {
			return
		}
		delete(h.activeServerClients[old], op.client)
	}

	if op.serverId != nil {
		if _, exists := h.activeServerClients[*op.serverId]; !exists {
			h.activeServerClients[*op.serverId] = make(map[*Client]bool)
		}

		h.activeServerClients[*op.serverId][op.client] = true
		h.activeServerByClient[op.client] = *op.serverId
	}
}

func (h *Hub) serverMemberStatusSnapshot(serverId int) []MemberState {
	var ids []MemberState

	if list, exists := h.serverSubscriptions[serverId]; exists {
		for client := range list {
			ids = append(ids, MemberState{client.user.Id, "online"})
		}
	}

	return ids
}

func (h *Hub) sendServerMemberStatusSnapshot(serverId int, client *Client) {
	ids := h.serverMemberStatusSnapshot(serverId)

	p, err := json.Marshal(ServerMemberSnapshotEvent{
		Op: OpGatewayMemberStatusSnapshot, TargetServerId: serverId, Data: map[string][]MemberState{
			"members": ids,
		},
	})

	if err != nil {
		return
	}

	h.broadcastMessageTo(map[*Client]bool{client: true}, Broadcast{serverId, p})
}

func (h *Hub) run() {
	ticker := time.NewTicker(2 * time.Second)
	defer ticker.Stop()
	for {
		select {
		case <-ticker.C:
			for client, tp := range h.typePresenceReg {
				if time.Now().After(*tp.ExpiresAt) {
					delete(h.typePresenceReg, client)
					h.broadcastTypePresenceEventStop(client, tp)
				}
			}
		case client := <-h.register:
			_, ok := h.users[client.user.Id]

			if !ok {
				h.users[client.user.Id] = make(map[*Client]bool)
			}

			h.users[client.user.Id][client] = true

			if _, exists := h.connections[client]; !exists {
				h.connections[client] = true
			}
		case activeServer := <-h.activateServer:
			if !h.connections[activeServer.client] {
				continue
			}

			h.changeActiveClientServer(activeServer)

			if activeServer.serverId != nil {
				h.sendServerMemberStatusSnapshot(*activeServer.serverId, activeServer.client)
			}
		case typeCommand := <-h.typeReg:
			if !h.connections[typeCommand.client] {
				continue
			}

			var op int
			sameTarget := false
			old, exists := h.typePresenceReg[typeCommand.client]

			if exists && old.ServerId == typeCommand.typingPresence.ServerId && old.ChannelId == typeCommand.typingPresence.ChannelId {
				sameTarget = true
			}

			if typeCommand.start {
				if exists && !sameTarget {
					h.broadcastTypePresenceEventStop(typeCommand.client, old)
				}

				h.typePresenceReg[typeCommand.client] = typeCommand.typingPresence
				op = OpClientTypingStart
			} else {
				if !sameTarget {
					continue
				}
				delete(h.typePresenceReg, typeCommand.client)
				op = OpClientTypingStop
			}

			h.broadcastTypePresenceEvent(typeCommand.client, typeCommand.typingPresence, op)
		case msg := <-h.broadcast:
			h.broadcastMessageTo(h.serverSubscriptions[msg.targetServerId], msg)
		case sub := <-h.subscribeToServer:
			_, ok := h.serverSubscriptions[sub.serverId]

			if !ok {
				h.serverSubscriptions[sub.serverId] = make(map[*Client]bool)
			}

			h.broadcastServerMemberStateChange(sub.serverId, sub.client, MemberStatusOnline)
			h.serverSubscriptions[sub.serverId][sub.client] = true
		case client := <-h.unsubscribe:
			h.closeConnections(client)
		}
	}
}
