package blueberry

import (
	"context"
	"encoding/json"
	"net"
	"net/http"
	"strings"
	"sync"

	"github.com/gorilla/websocket"
	"github.com/redis/go-redis/v9"
)

var upgrader = websocket.Upgrader{
	ReadBufferSize:  1024,
	WriteBufferSize: 1024,
}

type Server struct {
	http.Handler
	hub   *Hub
	store RealtimeStore
}

type User struct {
	Id                  int
	ServerSubscriptions []int
}

type Payload struct {
	Op             int
	TargetServerId int
	TargetChannel  int
	SenderId       int
	Data           map[string]any
}

type SubscribeServerCommand struct {
	client   *Client
	serverId int
}

type Broadcast struct {
	targetServerId int
	data           []byte
}

type RealtimeStore interface {
	AuthUser(ctx context.Context, ticket string) (*User, error)
	SubscribeToChannel(broadcast chan Broadcast, channel string) error
}

type RedisStore struct {
	db *redis.Client
}

func NewRedisStore(config RedisConfig) *RedisStore {
	return &RedisStore{redis.NewClient(&redis.Options{
		Addr:     net.JoinHostPort(config.Host, config.Port),
		Password: config.Password,
		DB:       config.DB,
	})}
}

func (r *RedisStore) AuthUser(ctx context.Context, ticket string) (*User, error) {
	data, err := r.db.GetDel(ctx, "ticket:"+ticket).Result()

	if err != nil {
		return nil, err
	}

	user := &User{}

	decoder := json.NewDecoder(strings.NewReader(data))

	if err := decoder.Decode(user); err != nil {
		return nil, err
	}

	return user, nil
}

func (r *RedisStore) SubscribeToChannel(broadcast chan Broadcast, channel string) error {
	ctx := context.Background()
	pubsub := r.db.Subscribe(ctx, channel)

	defer pubsub.Close()

	ch := pubsub.Channel()

	for msg := range ch {
		message := &Payload{}
		decoder := json.NewDecoder(strings.NewReader(msg.Payload))

		if err := decoder.Decode(message); err == nil {
			broadcast <- Broadcast{message.TargetServerId, []byte(msg.Payload)}
		}
	}

	return nil
}

func newWebSocket(w http.ResponseWriter, r *http.Request) (*websocket.Conn, error) {
	conn, err := upgrader.Upgrade(w, r, nil)

	if err != nil {
		return nil, err
	}

	return conn, nil
}

func NewServer(store RealtimeStore, redisChannel string) *Server {
	s := new(Server)

	s.hub = new(Hub{
		register:             make(chan *Client),
		subscribe:            make(chan *Client),
		subscribeToServer:    make(chan *SubscribeServerCommand),
		unsubscribe:          make(chan *Client),
		broadcast:            make(chan Broadcast),
		activateServer:       make(chan *SetActiveServerCommand),
		typeReg:              make(chan *SetTypingPresenceCommand),
		users:                make(map[int]map[*Client]bool),
		connections:          make(map[*Client]bool),
		serverSubscriptions:  make(map[int]map[*Client]bool),
		activeServerClients:  make(map[int]map[*Client]bool),
		activeServerByClient: make(map[*Client]int),
		typePresenceReg:      make(map[*Client]*TypingState),
	})

	s.store = store
	router := http.NewServeMux()

	router.Handle("/health", http.HandlerFunc(s.health))
	router.Handle("/ws", http.HandlerFunc(s.webSocket))

	s.Handler = router

	go s.hub.run()
	go func() {
		_ = s.store.SubscribeToChannel(s.hub.broadcast, redisChannel)
	}()

	return s
}

func (s *Server) health(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")
	_ = json.NewEncoder(w).Encode(map[string]any{
		"status": "ok",
	})
}

func (s *Server) webSocket(w http.ResponseWriter, r *http.Request) {
	user, err := s.store.AuthUser(r.Context(), r.URL.Query().Get("ticket"))

	if err != nil {
		w.WriteHeader(http.StatusUnauthorized)

		return
	}

	ws, err := newWebSocket(w, r)

	if err != nil {
		return
	}

	client := &Client{user, s.hub, user.ServerSubscriptions, sync.Once{}, ws, make(chan []byte, 256)}

	s.hub.register <- client

	for _, id := range client.serverSubscriptions {
		s.hub.subscribeToServer <- &SubscribeServerCommand{client, id}
	}

	go client.writer()
	go client.reader()
}
