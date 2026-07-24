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

## Web-only endpoints

These are not part of the versioned API and are only used by the Blade web app
itself (`routes/web.php`) — auth, the member panel, and the admin panel:

- `POST /locale` — switches the UI language (`tr`/`en`) for the current session.
- `GET /captcha` — returns a captcha image (registration flow).
- `GET /history` — the member panel: lists the authenticated user's ping
  results (submitted by the terminal client via the API above), grouped by date.
