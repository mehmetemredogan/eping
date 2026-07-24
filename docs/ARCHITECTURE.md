# Architecture

ePing has two independently deployable components that talk to each other only
over HTTP/JSON:

```
┌────────────────────────────┐
│         Browser             │
│  Blade + Alpine.js frontend │
│  (auth + member/admin only) │
└──────────────┬───────────────┘
               │ session cookies (web guard)
               ▼
┌─────────────────────────────────────────────┐
│               Laravel 13 app                 │
│                                               │
│  routes/web.php   → auth/history/admin pages │
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
│  — the only measurement tool │
└─────────────────────────────┘
```

## Web application (Laravel)

The web app has **no browser-based ping tool**. It only provides authentication,
a member panel (ping history submitted by the terminal client), the admin panel,
and the REST API the terminal client talks to.

- **Routing** — `routes/web.php` (`/` redirects to the member panel or login,
  `/history` = member panel, `/admin/*`; Blade pages, session auth),
  `routes/api.php` (stateless JSON API, Sanctum token auth), `routes/auth.php`
  (Breeze-based login/register).
- **Controllers** — thin; delegate DNS/geo logic to `app/Services/*`.
  Split into `Http/Controllers` (member panel/history), `Http/Controllers/Api`
  (v1 JSON API used by the terminal client), and `Http/Controllers/Admin`
  (admin panel, behind `auth` + `admin` middleware).
- **Services**:
  - `DnsLookupService` — resolves DNS records, PTR (rDNS), and EDNS/DoH data
    for results submitted via the API.
  - `NetworkTrendService` — compares a user's recent results against their own
    historical baseline (used by `/api/v1/results/trend`).
  - `CaptchaService` — generates the registration captcha image/answer pair.
- **Models** — `PingTarget` (host + category + provider metadata),
  `PingResult` (one measurement row per terminal-client submission, including
  DNS JSON blobs and the Go-client-submitted `network_analysis`), `Provider`
  (Markdown description, managed in the admin panel), `User`
  (username/password auth, `is_admin` flag).
- **Middleware** — `SetLocale` (resolves `tr`/`en` from the session on every web
  request), `EnsureUserIsAdmin` (guards `/admin/*`).
- **Localization** — `lang/{tr,en}/ping.php` (auth + member panel strings) and
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

The Go terminal client measures HTTP TTFB and (optionally) runs a traceroute
locally, then submits the result via `POST /api/v1/targets/{target}/results`.
DNS lookup for the stored result is performed server-side (`DnsLookupService`)
at submission time, and the result is persisted to the `ping_results` table
with the submitting user's `user_id` (from the Sanctum token).

The web app's member panel (`/history`) and the admin panel's results view
both read from that same table — they display whatever the terminal client
has submitted, they don't produce measurements themselves.

## Storage

- **PostgreSQL** in development/production (see `config/database.php`,
  `DATABASE_URL` in `.env`).
- **SQLite (`:memory:`)** in the test environment (`phpunit.xml`), so running
  `php artisan test` never touches a real database.
