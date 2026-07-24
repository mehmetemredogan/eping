# Architecture

ePing has two independently deployable components that talk to each other only
over HTTP/JSON:

```
┌────────────────────────────┐
│         Browser             │
│  Blade + Alpine.js frontend │
└──────────────┬───────────────┘
               │ session cookies (web guard)
               ▼
┌─────────────────────────────────────────────┐
│               Laravel 13 app                 │
│                                               │
│  routes/web.php   → ping/history/admin pages │
│  routes/api.php   → /api/v1/* (Sanctum auth) │
│                                               │
│  Controllers → Services → Models → DB        │
└──────────────┬────────────────────────────────┘
               │ REST / JSON (Sanctum bearer token)
               ▼
┌─────────────────────────────┐
│     Go terminal client       │
│           (ui/)              │
│  Bubble Tea TUI, independent │
│  Go module (pinglab/ui)      │
└─────────────────────────────┘
```

## Web application (Laravel)

- **Routing** — `routes/web.php` (Blade pages, session auth), `routes/api.php`
  (stateless JSON API, Sanctum token auth), `routes/auth.php` (Breeze-based
  login/register).
- **Controllers** — thin; delegate measurement/DNS/geo logic to `app/Services/*`.
  Split into `Http/Controllers` (public pages), `Http/Controllers/Api` (v1 JSON
  API), and `Http/Controllers/Admin` (admin panel, behind `auth` + `admin`
  middleware).
- **Services**:
  - `PingService` — runs the actual ICMP/HTTP ping against a target host.
  - `DnsLookupService` — resolves DNS records, PTR (rDNS), and EDNS/DoH data.
  - `PingTestService` — orchestrates a full ping+DNS test and persists a
    `PingResult`.
  - `FreeIpApiService` — resolves the client's IP to a city/country/ASN via a
    third-party geo API.
  - `NetworkTrendService` — compares a user's recent results against their own
    historical baseline (used by `/api/v1/results/trend`).
  - `CaptchaService` — generates the registration captcha image/answer pair.
- **Models** — `PingTarget` (host + category + provider metadata),
  `PingResult` (one measurement row, including client geo/DNS JSON blobs and
  Go-client-submitted `network_analysis`), `Provider` (Markdown description
  shown per provider group), `User` (username/password auth, `is_admin` flag).
- **Middleware** — `SetLocale` (resolves `tr`/`en` from the session on every web
  request), `EnsureUserIsAdmin` (guards `/admin/*`).
- **Localization** — `lang/{tr,en}/ping.php` (public UI + shared strings) and
  `lang/{tr,en}/admin.php` (admin panel).

## Terminal client (`ui/`, Go)

An independently versioned Go module (`pinglab/ui`) built with
[Bubble Tea](https://github.com/charmbracelet/bubbletea):

- `internal/config` — loads `config.yaml` (`%AppData%/eping` on Windows,
  `~/.config/eping` on Linux/macOS) and environment overrides
  (`EPING_API_URL`).
- `internal/api` — HTTP client for the Laravel `/api/v1/*` endpoints
  (login, targets, results, trend).
- `internal/ping` — local HTTP TTFB measurement with DNS/TCP/TLS timing
  breakdown and percentile (p50/p95) computation.
- `internal/traceroute` — wraps the OS's `tracert`/`traceroute`/`tracepath` and
  classifies each hop (loopback / link-local / private / CGNAT / public).
- `internal/netinfo` — combines ping + traceroute output into a single
  `network_analysis` payload that's submitted alongside each result.
- `internal/ui` — the Bubble Tea model/view/update loop, keyboard shortcuts,
  and rendering (`app.go`, `layout.go`, `measure.go`).

The client can operate anonymously (read-only target browsing) or authenticate
via `/api/v1/auth/login` to submit results and see historical trend
comparisons.

## Data flow: running a test

1. **Browser flow**: `resources/js/client-ping.js` performs an HTTP request
   against the target and posts the timing back to
   `POST /api/ping/{target}/report`, which uses `PingTestService` to persist a
   `PingResult` alongside a fresh DNS lookup.
2. **Terminal client flow**: the Go client measures HTTP TTFB and (optionally)
   runs a traceroute locally, then submits the result directly via
   `POST /api/v1/targets/{target}/results`. DNS lookup for the stored result is
   performed server-side at submission time.

In both flows, results are stored in the same `ping_results` table, so the
admin panel and history/trend features work uniformly regardless of which
client produced the measurement.

## Storage

- **PostgreSQL** in development/production (see `config/database.php`,
  `DATABASE_URL` in `.env`).
- **SQLite (`:memory:`)** in the test environment (`phpunit.xml`), so running
  `php artisan test` never touches a real database.
