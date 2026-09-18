# API reference

All endpoints are served under the `/api/v1` prefix (see `routes/api.php`).
Responses are JSON. Authenticated endpoints require a Laravel Sanctum bearer
token obtained from `POST /api/v1/auth/login`.

## Authentication

### `POST /api/v1/auth/login`

Rate limit: 10 requests/minute.

Request body:

```json
{
  "username": "alice",
  "password": "secret",
  "remember": false
}
```

Response `200`:

```json
{
  "token": "1|abcdef...",
  "token_type": "Bearer",
  "user": {
    "id": 1,
    "username": "alice",
    "is_admin": false
  }
}
```

A previous `eping-ui` token for the same user is revoked before a new one is
issued, so only one active desktop-client session exists per user at a time.

### `GET /api/v1/auth/me`

Requires `Authorization: Bearer <token>`. Returns the current user.

### `POST /api/v1/auth/logout`

Requires authentication. Revokes the current access token.

## Targets

### `GET /api/v1/targets`

Public, rate limit: 60 requests/minute. Returns the active ping targets, grouped
by provider.

Query parameters:

| Param | Type | Description |
|---|---|---|
| `category` | string | Filter by category key (see `PingTarget::categories()`) |
| `search` | string | Matches name, host, location, or provider |

Response `200`:

```json
{
  "count": 42,
  "categories": { "aws": "Amazon AWS", "cloudflare": "Cloudflare", "...": "..." },
  "groups": [
    {
      "provider": "Amazon AWS",
      "description_markdown": "**AWS** is ...",
      "description_html": "<p><strong>AWS</strong> is ...</p>",
      "targets": [
        {
          "id": 1,
          "name": "AWS Frankfurt",
          "host": "ec2.eu-central-1.amazonaws.com",
          "category": "aws",
          "category_label": "Amazon AWS",
          "provider": "Amazon AWS",
          "location": "Frankfurt, Germany",
          "country_code": "DE",
          "description": null
        }
      ]
    }
  ],
  "targets": ["... flat list, same shape as above ..."]
}
```

## Results

### `POST /api/v1/targets/{target}/results`

Requires authentication. Rate limit: 120 requests/minute. Submits a measurement
performed by the desktop/terminal client for the given target.

Request body (all latency fields required when `status` is `success`):

```json
{
  "session_id": "b3b1e2b0-....-....-....-............",
  "status": "success",
  "min_latency_ms": 12.3,
  "max_latency_ms": 18.9,
  "avg_latency_ms": 14.7,
  "jitter_ms": 2.1,
  "packet_loss_percent": 0,
  "packets_sent": 4,
  "packets_received": 4,
  "samples": [12.3, 14.1, 15.0, 18.9],
  "metric": "http_ttfb",
  "client_version": "eping-ui/1.0.0",
  "network_analysis": {
    "summary": "healthy",
    "path_summary": "8 hops, all public",
    "path": { "hop_count": 8, "local_hops": 1, "public_hops": 7, "timeout_hops": 0, "tool": "traceroute" }
  }
}
```

`status` must be one of `success`, `failed`, `timeout`. Response `201`:

```json
{
  "id": 123,
  "target_id": 1,
  "status": "success",
  "avg_latency_ms": 14.7,
  "resolved_ip": "3.120.1.1",
  "network_status": "healthy",
  "tested_at": "2026-07-24T21:00:00+00:00"
}
```

## History

### `GET /api/v1/results/history`

Requires authentication. Rate limit: 60 requests/minute. Returns the
authenticated user's own result history.

Query parameters:

| Param | Type | Description |
|---|---|---|
| `target_id` | int | Scope to a single target |
| `session_id` | string (UUID) | Filter by batch test session ID |
| `limit` | int | 1–200, default 50 |

### `GET /api/v1/results/trend`

Requires authentication. Rate limit: 60 requests/minute. Compares the user's
recent measurements against their own historical baseline (see
`App\Services\NetworkTrendService`).

Query parameters:

| Param | Type | Description |
|---|---|---|
| `target_id` | int | If set, returns the trend for a single target only |

Without `target_id`, returns an overall summary (`↑ improving` / `↓ degrading` /
`→ stable`) plus per-target breakdowns.

## Network Quality Tests

Endpoints for real-world web access quality assessments (probes across 12 distinct global and regional services like Google, Cloudflare, Apple, Netflix, e-Devlet, etc.).

### `POST /api/v1/network-quality`

Public or Authenticated (Sanctum bearer token). Rate limit: 60 requests/minute. Submits a completed network quality test.

Request body:

```json
{
  "score": 94,
  "grade": "A+",
  "status": "excellent",
  "summary": "Mükemmel web ve servis erişim kalitesi, düşük gecikme.",
  "avg_latency_ms": 142.5,
  "avg_dns_ms": 12.3,
  "avg_tcp_ms": 45.1,
  "avg_tls_ms": 32.8,
  "avg_ttfb_ms": 52.3,
  "packet_loss_percent": 0.0,
  "connection_type": "ethernet",
  "results": [
    {
      "name": "Cloudflare CDN",
      "url": "https://cloudflare.com",
      "category": "cdn",
      "dns_ms": 0.7,
      "tcp_ms": 32.1,
      "tls_ms": 23.5,
      "ttfb_ms": 38.2,
      "total_ms": 94.5,
      "status_code": 200,
      "ok": true
    }
  ],
  "insights": {
    "dns": "Ultra Hızlı DNS",
    "stability": "Kesintisiz %100 Erişim"
  },
  "tested_at": "2026-09-18T20:00:00Z"
}
```

Response `201`:

```json
{
  "id": 1,
  "score": 94,
  "grade": "A+",
  "status": "excellent",
  "client_ip": "1.2.3.4",
  "client_isp": "Superonline",
  "tested_at": "2026-09-18T20:00:00.000000Z"
}
```

### `GET /api/v1/network-quality/latest`

Public or Authenticated. Returns the most recent network quality test for the authenticated user (or matching client IP).

### `GET /api/v1/network-quality`

Public or Authenticated. Returns paginated history of network quality tests.

### `GET /api/v1/quality/targets` (or `GET /api/v1/network-quality/targets`)

Public endpoint. Returns the active probe targets defined dynamically in the database via the Admin Panel (`/admin/quality-targets`). Used by the Go CLI, TUI, and daemon to fetch the test targets dynamically.

Response `200`:

```json
{
  "count": 2,
  "targets": [
    {
      "id": 1,
      "name": "Cloudflare CDN",
      "url": "https://cloudflare.com",
      "domain": "cloudflare.com",
      "category": "cdn"
    },
    {
      "id": 2,
      "name": "Google",
      "url": "https://www.google.com",
      "domain": "www.google.com",
      "category": "search"
    }
  ]
}
```

## Web-only endpoints

These are not part of the versioned API and are only used by the Blade web app
itself (`routes/web.php`) — auth, the member panel, and the admin panel:

- `POST /locale` — switches the UI language (`tr`/`en`) for the current session.
- `GET /captcha` — returns a captcha image (registration flow).
- `GET /history` — the member panel: lists the authenticated user's ping
  results (submitted by the terminal client via the API above), grouped by date and session.
- `GET /quality` — Network Quality dashboard: shows recent scores, aggregates, and past quality tests.
- `GET /quality/{test}` — Network Quality detail view: displays all probed web targets and breakdown metrics.

