package blueberry

import (
	"sync"
	"time"

	"github.com/gorilla/websocket"
)

type Client struct {
	user                *User
	hub                 *Hub
	serverSubscriptions []int
	once                sync.Once
	ws                  *websocket.Conn
	send                chan []byte
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
		_, _, err := c.ws.ReadMessage()
		if err != nil {
			return
		}

		// Client events go here
	}
}
