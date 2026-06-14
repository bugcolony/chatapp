package blueberry

type Hub struct {
	register            chan *Client
	subscribe           chan *Client
	unsubscribe         chan *Client
	subscribeToServer   chan *Subscription
	broadcast           chan Broadcast
	users               map[int]map[*Client]bool
	connections         map[*Client]bool
	serverSubscriptions map[int]map[*Client]bool
}

func (h *Hub) closeConnections(client *Client) {
	if _, exists := h.connections[client]; !exists {
		return
	}

	h.closeClientServerSubscriptions(client)

	delete(h.connections, client)

	h.closeUserClient(client)

	close(client.send)
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

func (h *Hub) broadcastMessage(msg Broadcast) {
	for client := range h.serverSubscriptions[msg.targetServerId] {
		select {
		case client.send <- msg.data:
		default:
			// buffer full - close connections
			h.closeConnections(client)
		}
	}
}

func (h *Hub) run() {
	for {
		select {
		case client := <-h.register:
			_, ok := h.users[client.user.Id]

			if !ok {
				h.users[client.user.Id] = make(map[*Client]bool)
			}

			h.users[client.user.Id][client] = true

			if _, exists := h.connections[client]; !exists {
				h.connections[client] = true
			}
		case msg := <-h.broadcast:
			h.broadcastMessage(msg)
		case sub := <-h.subscribeToServer:
			_, ok := h.serverSubscriptions[sub.serverId]

			if !ok {
				h.serverSubscriptions[sub.serverId] = make(map[*Client]bool)
			}

			h.serverSubscriptions[sub.serverId][sub.client] = true
		case client := <-h.unsubscribe:
			h.closeConnections(client)
		}
	}
}
