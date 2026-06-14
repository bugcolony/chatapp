# Chat Frontend

The frontend is a Nuxt 4 single-page application for authentication, server
navigation, channel messaging, invitations, and real-time updates.

For the complete Docker setup, start with the [project README](../README.md).

## Stack

- Nuxt 4 and Vue 3
- Nuxt UI 4 and Tailwind CSS 4
- Pinia for client state
- VueUse for browser and WebSocket composables
- Zod for form schemas
- ESLint through `@nuxt/eslint`

Server-side rendering is disabled. The production Docker image generates static
assets and serves them through Nginx with SPA fallback routing.

## Project Layout

```text
app/
  assets/        global CSS
  components/    forms, modals, and chat interface components
  composables/   API, application URL, auth, and invite helpers
  layouts/       authenticated and unauthenticated shells
  middleware/    auth, guest, and bootstrap guards
  pages/         file-based application routes
  plugins/       API client and navigation progress
  stores/        auth, chat UI, server data, and WebSocket state
  utils/         shared utility functions
public/          static images, favicon, and robots.txt
```

## Development

The recommended workflow runs Nuxt through the root Compose stack. Traefik then
serves the frontend and API on the same origin at <http://localhost>, matching
the production routing model.

For frontend-only work:

```bash
npm ci
npm run dev
```

The standalone dev server listens on <http://localhost:3000>. To connect it to
the Docker API, set:

```bash
NUXT_PUBLIC_API_BASE=http://localhost
NUXT_PUBLIC_APP_URL=http://localhost:3000
NUXT_PUBLIC_WS_URL=ws://localhost/ws
```

The backend CORS and Sanctum stateful-domain settings must also allow
`http://localhost:3000` when using this cross-origin setup.

## Scripts

| Command | Purpose |
| --- | --- |
| `npm run dev` | Start the Nuxt development server |
| `npm run lint` | Run ESLint with zero warnings allowed |
| `npm run build` | Create a production Nuxt build |
| `npm run generate` | Generate the static production application |
| `npm run preview` | Preview a completed Nuxt build |

Before pushing frontend changes:

```bash
npm run lint
npm run build
```

## Runtime Configuration

| Variable | Default | Purpose |
| --- | --- | --- |
| `NUXT_PUBLIC_API_BASE` | Same origin | Base URL for API and auth requests |
| `NUXT_PUBLIC_APP_URL` | `http://localhost` | Public application URL |
| `NUXT_PUBLIC_WS_URL` | Same-origin `/ws` | WebSocket endpoint |
| `NUXT_AUTH_TIMEOUT` | `10000` | Authentication bootstrap timeout in milliseconds |

These values are public and are embedded in the client build. Do not place
secrets in them.

## API and Authentication

The `apiFetch` plugin creates a credentialed client rooted at `/api/v1`. It:

- Sends cookies on every request
- Adds the decoded `XSRF-TOKEN` value to state-changing requests
- Refreshes the Sanctum CSRF cookie and retries once after a `419`
- Resets local authentication and redirects after a `401`

The auth store handles CSRF initialization, login, logout, current-user
loading, and bootstrap state. Route middleware protects authenticated and guest
pages.

## WebSockets

The socket store requests a one-time ticket from `/api/v1/ws/ticket`, connects
to the configured WebSocket URL, and applies exponential-backoff reconnection.
Incoming message events either append to the active channel or invalidate the
inactive channel so it can be refreshed later.

See the [WebSocket service README](../ws/README.md) for the server-side flow.
