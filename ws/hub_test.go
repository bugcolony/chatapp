package blueberry

import (
	"sync"
	"testing"
)

func newTestHub() *Hub {
	return &Hub{
		register:            make(chan *Client),
		subscribe:           make(chan *Client),
		unsubscribe:         make(chan *Client),
		subscribeToServer:   make(chan *Subscription),
		broadcast:           make(chan Broadcast),
		users:               make(map[int]map[*Client]bool),
		connections:         make(map[*Client]bool),
		serverSubscriptions: make(map[int]map[*Client]bool),
	}
}

func newTestClient(hub *Hub, userID int, serverIDs ...int) *Client {
	return &Client{
		user:                &User{Id: userID, ServerSubscriptions: serverIDs},
		hub:                 hub,
		serverSubscriptions: serverIDs,
		once:                sync.Once{},
		send:                make(chan []byte, 1),
	}
}

func registerTestClient(hub *Hub, client *Client) {
	hub.connections[client] = true
	hub.users[client.user.Id] = map[*Client]bool{client: true}

	for _, serverID := range client.serverSubscriptions {
		hub.serverSubscriptions[serverID] = map[*Client]bool{client: true}
	}
}

func TestCloseConnectionsRemovesClientFromHub(t *testing.T) {
	hub := newTestHub()
	client := newTestClient(hub, 42, 10, 20)
	registerTestClient(hub, client)

	hub.closeConnections(client)

	if _, exists := hub.connections[client]; exists {
		t.Fatal("client was not removed from connections")
	}
	if _, exists := hub.users[client.user.Id]; exists {
		t.Fatal("client was not removed from users")
	}
	for _, serverID := range client.serverSubscriptions {
		if _, exists := hub.serverSubscriptions[serverID]; exists {
			t.Fatalf("client subscription to server %d was not removed", serverID)
		}
	}
	if _, ok := <-client.send; ok {
		t.Fatal("client send channel was not closed")
	}
}

func TestCloseConnectionsIsIdempotent(t *testing.T) {
	hub := newTestHub()
	client := newTestClient(hub, 42, 10)
	registerTestClient(hub, client)

	hub.closeConnections(client)
	hub.closeConnections(client)
}

func TestBroadcastRemovesClientWithFullSendBuffer(t *testing.T) {
	hub := newTestHub()
	client := newTestClient(hub, 42, 10)
	registerTestClient(hub, client)
	client.send <- []byte("already queued")

	hub.broadcastMessage(Broadcast{targetServerId: 10, data: []byte("next message")})

	if _, exists := hub.connections[client]; exists {
		t.Fatal("client with a full send buffer was not removed")
	}
	if _, exists := hub.serverSubscriptions[10]; exists {
		t.Fatal("client with a full send buffer remained subscribed")
	}
}
