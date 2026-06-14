# Chat WebSocket Gateway

The WebSocket gateway is a small Go service that authenticates connections with
one-time Laravel-issued tickets and fans Redis Pub/Sub messages out to connected
members of the target server.

For the complete Docker setup, start with the [project README](../README.md).

## Responsibilities

- Expose the `/ws` WebSocket endpoint
- Consume short-lived, single-use authentication tickets from Redis
- Register each client for the servers included in its ticket
- Subscribe to the Laravel real-time Redis channel
- Broadcast events only to clients subscribed to the target server
- Expose a lightweight `/health` endpoint

The service does not query PostgreSQL or implement application authorization.
Laravel determines the user's allowed server subscriptions when issuing the
ticket.

## Connection Flow

1. The browser requests `POST /api/v1/ws/ticket` using its authenticated
   Sanctum session.
2. Laravel stores `ticket:<random-value>` in real-time Redis with a 60-second
   lifetime.
3. The browser opens `/ws?ticket=<random-value>`.
4. The gateway atomically consumes the ticket with Redis `GETDEL`.
5. The connection is registered for the user and every server ID encoded in
   the ticket.

A ticket cannot be reused after the gateway authenticates the request.

## Broadcast Flow

Laravel publishes JSON payloads to the configured Redis channel. The gateway
reads `targetServerId` to select recipients and forwards the original payload
unchanged.

Example message event:

```json
{
  "op": 1,
  "targetServerId": 12,
  "targetChannelId": 34,
  "senderId": 56,
  "data": {
    "id": 78,
    "message": "Hello"
  }
}
```

Operation `1` is `MESSAGE_CREATED`.

## Configuration

| Variable | Default | Purpose |
| --- | --- | --- |
| `WS_HOST` | `0.0.0.0` | HTTP listen address |
| `WS_PORT` | `8080` | HTTP listen port |
| `REDIS_HOST` | `127.0.0.1` | Real-time Redis host |
| `REDIS_PORT` | `6379` | Real-time Redis port |
| `REDIS_PASSWORD` | Empty | Real-time Redis password |
| `REDIS_DB` | `0` | Real-time Redis database |
| `REDIS_CHANNEL` | `messages.created` | Pub/Sub channel |

Docker maps these values from the root `.env` and connects the service only to
the edge and real-time networks. Laravel currently publishes message events to
the hard-coded `messages.created` channel, so keep `REDIS_CHANNEL` at that value
unless the backend publisher is updated at the same time.

## Development

Requirements:

- Go 1.26 or newer
- A reachable Redis instance
- A valid ticket producer, normally the Laravel API

Run the service:

```bash
go run ./cmd/web
```

Run formatting and tests:

```bash
gofmt -w .
go test ./...
```

If the default Go cache directory is not writable:

```bash
GOCACHE=/tmp/chat-go-build GOTMPDIR=/tmp go test ./...
```

Build a local binary:

```bash
go build -o bin/ws ./cmd/web
```

## Endpoints

| Method | Path | Purpose |
| --- | --- | --- |
| `GET` | `/health` | Return `{"status":"ok"}` |
| `GET` | `/ws?ticket=...` | Upgrade an authenticated connection |

In the full stack, Traefik exposes the WebSocket endpoint at
`ws://localhost/ws` in development and `wss://<APP_DOMAIN>/ws` in production.
