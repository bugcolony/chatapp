# Chat API

The backend is a Laravel 13 application responsible for authentication,
authorization, chat domain data, persistence, queues, and publishing real-time
events.

For the complete Docker setup, start with the [project README](../README.md).

## Stack

- PHP 8.5 and Laravel 13
- Laravel Sanctum for session authentication
- Laravel Socialite for GitHub and Google OAuth
- PostgreSQL for application data
- Redis for sessions, cache, queues, and real-time Pub/Sub
- Horizon for queue workers and monitoring
- Telescope for local diagnostics
- Pest 4 for automated tests

## Responsibilities

- Authenticate users and manage browser sessions
- Authorize server and channel access through policies and middleware
- Manage servers, members, channels, messages, and invitations
- Issue one-time WebSocket tickets
- Publish message events to the real-time Redis channel
- Run migrations, scheduled tasks, and Horizon workers
- Expose dependency-aware health checks

## API Overview

All JSON API routes are prefixed with `/api/v1`.

| Method | Path | Authentication | Purpose |
| --- | --- | --- | --- |
| `GET` | `/api/health` | No | Database and Redis health |
| `POST` | `/api/v1/login` | No | Start a session |
| `GET` | `/api/v1/me` | Sanctum | Current user |
| `POST` | `/api/v1/logout` | Sanctum | End the session |
| `POST` | `/api/v1/ws/ticket` | Sanctum | Create a one-time WebSocket ticket |
| `GET/POST` | `/api/v1/servers` | Sanctum | List or create servers |
| `GET` | `/api/v1/servers/{server}` | Member | Server details |
| `GET` | `/api/v1/servers/{server}/members` | Member | Server members |
| `GET` | `/api/v1/servers/{server}/channels` | Member | Server channels |
| `POST` | `/api/v1/servers/{server}/invites` | Member | Create an invite |
| `POST` | `/api/v1/servers/{server}/leave` | Member | Leave a server |
| `GET/POST` | `/api/v1/channels/{channel}/messages` | Channel member | Read or send messages |
| `GET` | `/api/v1/invites/{code}` | No | Inspect an invite |
| `POST` | `/api/v1/invites/{code}/join` | Sanctum | Join through an invite |

OAuth routes are exposed at `/auth/{provider}/redirect` and
`/auth/{provider}/callback`.

## Project Layout

```text
app/
  Actions/       application use cases
  Data/          typed input data
  Enums/         permissions, roles, channel types, and broadcast operations
  Events/        domain events
  Http/          controllers, middleware, requests, and API resources
  Listeners/     event side effects
  Models/        Eloquent models
  Policies/      authorization rules
  Services/      social authentication and permission services
database/
  factories/     model factories
  migrations/    application and Telescope schema
  seeders/       roles and local fixtures
routes/          API, OAuth, console, and scheduler routes
tests/           Pest feature and unit tests
```

## Development

The supported integration environment is the root Docker Compose stack. Run
Artisan commands from the repository root:

```bash
docker compose --env-file .env -f compose.yaml -f compose.dev.yaml exec api-php \
  php artisan route:list
```

For host-based backend work:

```bash
composer install
php artisan test --compact
vendor/bin/pint --format agent
```

The test suite uses in-memory SQLite and array-backed cache, queue, mail, and
session drivers, so it does not require the Docker dependencies.

Frontend assets included in the Laravel skeleton can be built with:

```bash
npm ci
npm run build
```

## Database and Fixtures

Migrations run automatically through the Compose `migrate` service. To seed
roles and local sample data:

```bash
docker compose --env-file .env -f compose.yaml -f compose.dev.yaml exec api-php \
  php artisan db:seed
```

`DatabaseSeeder` always creates the system roles. In the `local` environment it
also creates sample users, servers, channels, members, and messages. Factory
users use `password`.

For a predictable demo dataset that is also compatible with the production
image, use the dedicated lifecycle commands:

```bash
php artisan demo:provision
php artisan demo:reset
php artisan demo:remove
```

`demo:provision` may be run repeatedly and creates or reconciles the canonical
fixtures without deleting additional activity. `demo:reset` removes all
activity associated with the demo users before recreating the canonical
dataset. `demo:remove` removes the demo users and their related data without
recreating them. The same provisioning operation is available through
`php artisan db:seed --class=DemoFixturesSeeder --force`.

The dataset contains three servers with categorized text and voice channels,
plus ten verified users. Every user is an active member of every server, and
`example1@example.com` owns all three. Login emails range from
`example1@example.com` through `example10@example.com`; every account uses
`password`.

## Real-Time Flow

1. An authenticated client requests `POST /api/v1/ws/ticket`.
2. Laravel stores a random one-time ticket in real-time Redis for 60 seconds.
3. The WebSocket service consumes the ticket during connection setup.
4. When a message is created, Laravel publishes a `MESSAGE_CREATED` payload to
   the realtime Redis channel.
5. The WebSocket service forwards the payload to clients subscribed to the
   target server.

See the [WebSocket service README](../ws/README.md) for protocol details.

## Configuration

Docker injects configuration from a root `.env` created from
[`.env.example`](../.env.example). Important groups include:

- Application URL, key, environment, and logging
- PostgreSQL connection values
- Operations and real-time Redis connections
- Sanctum stateful domains and CORS origins
- Queue and session settings
- S3-compatible object storage
- Mail, Horizon, and Telescope
- GitHub and Google OAuth credentials

Never commit a real `.env` file or production credentials.
