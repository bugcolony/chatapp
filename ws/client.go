package blueberry

import (
	"bytes"
	"encoding/json"
	"slices"
	"sync"
	"time"

	"github.com/gorilla/websocket"
)

type Client struct {
	user                *User
	hub                 *Hub
	serverSubscriptions []int
	friends             []int
	once                sync.Once
	ws                  *websocket.Conn
	send                chan []byte
}

type TypingState struct {
	ServerId  int
	ChannelId int
	ExpiresAt *time.Time
}

func (c *Client) close() {
	c.once.Do(func() {
		c.hub.unsubscribe <- c
		c.ws.Close()
	})
}

func (c *Client) writer() {
	ticker := time.NewTicker(time.Second * 25)
	defer ticker.Stop()
	defer c.close()

	for {
		select {
		case msg, ok := <-c.send:
			c.ws.SetWriteDeadline(time.Now().Add(time.Second * 10))

			if !ok {
				// Send channel closed
				c.ws.WriteMessage(websocket.CloseMessage, []byte{})
				return
			}

			// Or offload buffer to ws with NewWriter
			if err := c.ws.WriteMessage(websocket.TextMessage, msg); err != nil {
				return
			}
		case <-ticker.C:
			c.ws.SetWriteDeadline(time.Now().Add(time.Second * 10))

			if err := c.ws.WriteMessage(websocket.PingMessage, []byte{}); err != nil {
				return
			}
		}
	}
}

func (c *Client) reader() {
	defer c.close()

	c.ws.SetReadDeadline(time.Now().Add(time.Second * 60))
	c.ws.SetPongHandler(func(string) error {
		return c.ws.SetReadDeadline(time.Now().Add(time.Second * 60))
	})

	for {
		_, msg, err := c.ws.ReadMessage()
		if err != nil {
			return
		}

		var command EventData[ChannelRef]

		decoder := json.NewDecoder(bytes.NewReader(msg))

		if err := decoder.Decode(&command); err != nil {
			continue
		}

		ref := command.Data

		if ref.ServerId != nil && !slices.Contains(c.serverSubscriptions, *ref.ServerId) {
			continue
		}

		switch command.Op {
		case OpServerActive:
			c.hub.activateServer <- &SetActiveServerCommand{c, ref.ServerId}
		case OpTypingStart:
			if ref.ServerId == nil || ref.ChannelId == nil {
				continue
			}

			expiresAt := time.Now().Add(TypePresenceExpirationTime * time.Second)

			c.hub.typeReg <- &SetTypingPresenceCommand{
				start:  true,
				client: c,
				typingPresence: &TypingState{
					ServerId:  *ref.ServerId,
					ChannelId: *ref.ChannelId,
					ExpiresAt: &expiresAt,
				},
			}
		case OpTypingStop:
			if ref.ServerId == nil || ref.ChannelId == nil {
				continue
			}

			c.hub.typeReg <- &SetTypingPresenceCommand{
				start:  false,
				client: c,
				typingPresence: &TypingState{
					ServerId:  *ref.ServerId,
					ChannelId: *ref.ChannelId,
				},
			}
		}
	}
}
