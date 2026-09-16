package blueberry

import (
	"bytes"
	"encoding/json"
	"sync"
	"testing"
	"time"
)

func newTestHub() *Hub {
	return &Hub{
		register:             make(chan *Client),
		unsubscribe:          make(chan *Client),
		subscribeToServer:    make(chan *SubscribeServerCommand),
		broadcast:            make(chan Broadcast),
		activateServer:       make(chan *SetActiveServerCommand),
		typeReg:              make(chan *SetTypingPresenceCommand),
		users:                make(map[int]map[*Client]bool),
		connections:          make(map[*Client]bool),
		serverSubscriptions:  make(map[int]map[*Client]bool),
		typePresenceReg:      make(map[*Client]*TypingState),
		activeServerByClient: make(map[*Client]int),
		activeServerClients:  make(map[int]map[*Client]bool),
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

	if _, exists := hub.users[client.user.Id]; !exists {
		hub.users[client.user.Id] = make(map[*Client]bool)
	}
	hub.users[client.user.Id][client] = true

	for _, serverID := range client.serverSubscriptions {
		if _, exists := hub.serverSubscriptions[serverID]; !exists {
			hub.serverSubscriptions[serverID] = make(map[*Client]bool)
		}
		hub.serverSubscriptions[serverID][client] = true
	}
}

func receiveTestMessage(t *testing.T, client *Client) []byte {
	t.Helper()

	select {
	case msg := <-client.send:
		return msg
	case <-time.After(time.Second):
		t.Fatal("timed out waiting for hub message")
		return nil
	}
}

func receiveTypingEvent(t *testing.T, client *Client) EventData[TypingData] {
	t.Helper()

	var event EventData[TypingData]
	if err := json.Unmarshal(receiveTestMessage(t, client), &event); err != nil {
		t.Fatalf("failed to decode typing event: %v", err)
	}

	return event
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

	hub.broadcastMessageTo(hub.serverSubscriptions[10], []byte("next message"))

	if _, exists := hub.connections[client]; exists {
		t.Fatal("client with a full send buffer was not removed")
	}
	if _, exists := hub.serverSubscriptions[10]; exists {
		t.Fatal("client with a full send buffer remained subscribed")
	}
}

func TestSnapshotSkipsClientRemovedAfterFullSendBuffer(t *testing.T) {
	hub := newTestHub()
	client := newTestClient(hub, 42, 10)
	registerTestClient(hub, client)
	client.send <- []byte("already queued")

	hub.broadcastMessageTo(map[*Client]bool{client: true}, []byte("overflow"))
	hub.sendServerMemberStatusSnapshot(10, client)

	if msg, ok := <-client.send; !ok || string(msg) != "already queued" {
		t.Fatalf("unexpected buffered message after disconnect: %q, open: %t", msg, ok)
	}
	if _, ok := <-client.send; ok {
		t.Fatal("snapshot was queued after the client send channel was closed")
	}
}

func TestHubIgnoresCommandsQueuedAfterClientRemoval(t *testing.T) {
	hub := newTestHub()
	disconnected := newTestClient(hub, 42, 10)
	registerTestClient(hub, disconnected)
	disconnected.send <- []byte("already queued")
	hub.broadcastMessageTo(map[*Client]bool{disconnected: true}, []byte("overflow"))

	live := newTestClient(hub, 7, 10)
	registerTestClient(hub, live)
	go hub.run()

	serverID := 10
	hub.activateServer <- &SetActiveServerCommand{client: disconnected, serverId: &serverID}
	hub.broadcast <- Broadcast{route: Route{ServerId: serverID}, data: []byte("active command barrier")}
	if msg := receiveTestMessage(t, live); string(msg) != "active command barrier" {
		t.Fatalf("unexpected active-command barrier message: %q", msg)
	}

	if _, exists := hub.activeServerByClient[disconnected]; exists {
		t.Fatal("disconnected client was restored to active-server state")
	}

	expiresAt := time.Now().Add(time.Hour)
	hub.typeReg <- &SetTypingPresenceCommand{
		start:  true,
		client: disconnected,
		typingPresence: &TypingState{
			ServerId:  serverID,
			ChannelId: 20,
			ExpiresAt: &expiresAt,
		},
	}
	hub.broadcast <- Broadcast{route: Route{ServerId: serverID}, data: []byte("typing command barrier")}
	if msg := receiveTestMessage(t, live); string(msg) != "typing command barrier" {
		t.Fatalf("unexpected typing-command barrier message: %q", msg)
	}

	if _, exists := hub.typePresenceReg[disconnected]; exists {
		t.Fatal("disconnected client restored typing presence")
	}
}

func TestTypingSwitchIgnoresStaleStop(t *testing.T) {
	hub := newTestHub()
	sender := newTestClient(hub, 42, 10)
	observer := newTestClient(hub, 7, 10)
	observer.send = make(chan []byte, 8)
	registerTestClient(hub, sender)
	registerTestClient(hub, observer)
	hub.activeServerClients[10] = map[*Client]bool{observer: true}
	hub.activeServerByClient[observer] = 10
	go hub.run()

	expiresAt := time.Now().Add(time.Hour)
	channelA := &TypingState{ServerId: 10, ChannelId: 20, ExpiresAt: &expiresAt}
	channelB := &TypingState{ServerId: 10, ChannelId: 30, ExpiresAt: &expiresAt}

	hub.typeReg <- &SetTypingPresenceCommand{start: true, client: sender, typingPresence: channelA}
	firstStart := receiveTypingEvent(t, observer)
	if firstStart.Op != OpTypingStart || firstStart.Data.ChannelId != channelA.ChannelId || firstStart.Data.UserId != sender.user.Id {
		t.Fatalf("unexpected initial typing event: %+v", firstStart)
	}

	hub.typeReg <- &SetTypingPresenceCommand{start: true, client: sender, typingPresence: channelB}
	oldStop := receiveTypingEvent(t, observer)
	newStart := receiveTypingEvent(t, observer)
	if oldStop.Op != OpTypingStop || oldStop.Data.ChannelId != channelA.ChannelId {
		t.Fatalf("unexpected previous-channel stop: %+v", oldStop)
	}
	if newStart.Op != OpTypingStart || newStart.Data.ChannelId != channelB.ChannelId {
		t.Fatalf("unexpected new-channel start: %+v", newStart)
	}

	hub.typeReg <- &SetTypingPresenceCommand{start: false, client: sender, typingPresence: channelA}
	hub.broadcast <- Broadcast{route: Route{ServerId: 10}, data: []byte("stale stop barrier")}
	if msg := receiveTestMessage(t, observer); string(msg) != "stale stop barrier" {
		t.Fatalf("stale stop produced an event: %q", msg)
	}

	current, exists := hub.typePresenceReg[sender]
	if !exists || current.ServerId != channelB.ServerId || current.ChannelId != channelB.ChannelId {
		t.Fatalf("stale stop removed the current typing state: %+v", current)
	}

	hub.typeReg <- &SetTypingPresenceCommand{start: false, client: sender, typingPresence: channelB}
	finalStop := receiveTypingEvent(t, observer)
	if finalStop.Op != OpTypingStop || finalStop.Data.ChannelId != channelB.ChannelId {
		t.Fatalf("unexpected final typing stop: %+v", finalStop)
	}
	if _, exists := hub.typePresenceReg[sender]; exists {
		t.Fatal("matching stop did not clear typing state")
	}
}

func TestMemberSnapshotData(t *testing.T) {
	hub := newTestHub()
	client := newTestClient(hub, 42, 10)
	registerTestClient(hub, client)

	hub.sendServerMemberStatusSnapshot(10, client)

	var eventData EventData[MemberSnapshotData]
	if err := json.NewDecoder(bytes.NewReader(receiveTestMessage(t, client))).Decode(&eventData); err != nil {
		t.Fatalf("failed to decode snapshot: %v", err)
	}

	want := EventData[MemberSnapshotData]{OpMemberStatusSnapshot, MemberSnapshotData{ServerId: 10, Members: []MemberState{{UserId: 42, Status: MemberStatusOnline}}}}
	if eventData.Op != want.Op || eventData.Data.ServerId != want.Data.ServerId || len(eventData.Data.Members) != 1 || eventData.Data.Members[0] != want.Data.Members[0] {
		t.Fatalf("unexpected snapshot event data: %+v", eventData)
	}
}

func TestUserRouteReachesEveryConnectionOfListedUsersOnly(t *testing.T) {
	hub := newTestHub()
	firstTab := newTestClient(hub, 42, 10)
	secondTab := newTestClient(hub, 42, 10)
	other := newTestClient(hub, 7)
	unlisted := newTestClient(hub, 99, 10)
	for _, client := range []*Client{firstTab, secondTab, other, unlisted} {
		registerTestClient(hub, client)
	}
	go hub.run()

	hub.broadcast <- Broadcast{route: Route{UserIds: []int{42, 7}}, data: []byte("direct")}

	for _, client := range []*Client{firstTab, secondTab, other} {
		if msg := receiveTestMessage(t, client); string(msg) != "direct" {
			t.Fatalf("unexpected message for user %d: %q", client.user.Id, msg)
		}
	}

	hub.broadcast <- Broadcast{route: Route{ServerId: 10}, data: []byte("barrier")}
	if msg := receiveTestMessage(t, unlisted); string(msg) != "barrier" {
		t.Fatalf("unlisted user received a user-routed event: %q", msg)
	}
}
