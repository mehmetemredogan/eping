# Architecture

ePing has two independently deployable components that talk to each other only
over HTTP/JSON:

```
```
┌─────────────────────────────────────────┐
│                Browser                  │
│       Blade + Alpine.js frontend        │
│  (auth, member history, quality, admin) │
└────────────────────┬────────────────────┘
                     │ session cookies (web guard)
                     ▼
┌────────────────────────────────────────────────────────┐
│                     Laravel 13 app                     │
│                                                        │
│  routes/web.php   → auth/history/quality/admin pages   │
│  routes/api.php   → /api/v1/* (Sanctum auth)           │
│                                                        │
│  Controllers → Services → Models → DB                  │
└────────────────────┬───────────────────────────────────┘
                     │ REST / JSON (Sanctum bearer token)
                     ▼
┌────────────────────────────────────────────────────────┐
│                  Go terminal client                    │
│                        (ui/)                           │
│  • Interactive TUI (Bubble Tea)                        │
│  • CLI Quality Probe (eping quality)                   │
│  • Headless Background Daemon (eping daemon)           │
└────────────────────────────────────────────────────────┘
```

## Web application (Laravel)

The web app provides authentication, the member panel (ping history), the network quality dashboard, the admin panel, and the REST API the terminal client talks to.

- **Routing**:
  - `routes/web.php` (`/` member panel / login redirect, `/history` member panel, `/quality` & `/quality/{test}` network quality dashboard, `/admin/*` admin panel).
  - `routes/api.php` (stateless JSON API: auth, targets, results, trend, `/api/v1/network-quality`).
  - `routes/auth.php` (Breeze-based login/register).
- **Controllers**:
  - `Http/Controllers` — `HistoryController`, `NetworkQualityController` (web views).
  - `Http/Controllers/Api` — `ResultController`, `NetworkQualityController`, `TargetController`, `AuthController`.
  - `Http/Controllers/Admin` — `DashboardController`, `TargetController`, `ProviderController`, `LogController`.
- **Services**:
  - `DnsLookupService` — resolves DNS records, PTR (rDNS), and EDNS/DoH data for results submitted via the API.
  - `NetworkTrendService` — compares a user's recent results against their own historical baseline (used by `/api/v1/results/trend`).
  - `CaptchaService` — generates the registration captcha image/answer pair.
- **Models**:
  - `PingTarget` (host + category + provider metadata).
  - `PingResult` (one measurement row per terminal-client submission, including DNS JSON blobs and `network_analysis`).
  - `NetworkQualityTest` (comprehensive web access tests measuring DNS, TCP, TLS, TTFB across 12 services with score 0-100 and grade).
  - `Provider` (Markdown description, managed in the admin panel).
  - `User` (username/password auth, `is_admin` flag).
- **Middleware**:
  - `SetLocale` (resolves `tr`/`en` from the session on every web request).
  - `EnsureUserIsAdmin` (guards `/admin/*`).
- **Localization**:
  - `lang/{tr,en}/ping.php` (auth, member panel, and network quality strings).
  - `lang/{tr,en}/admin.php` (admin panel).

## Terminal client (`ui/`, Go)

An independently versioned Go module (`pinglab/ui`) supporting multiple operational modes:

- `internal/config` — loads `config.yaml` (`%AppData%/eping` on Windows, `~/.config/eping` on Linux/macOS) and environment overrides (`EPING_API_URL`).
- `internal/api` — HTTP client for Laravel `/api/v1/*` endpoints (login, targets, results, trend, network-quality).
- `internal/ping` — local HTTP TTFB measurement with DNS/TCP/TLS timing breakdown and percentile (p50/p95) computation.
- `internal/traceroute` — wraps the OS's `tracert`/`traceroute`/`tracepath` and classifies each hop.
- `internal/quality` — concurrent probe runner (`httptrace`) testing 12 real web services (Google, Cloudflare, Apple, Netflix, etc.), calculating 0–100 quality score, grade (A+ to F), and formatting terminal/JSON reports.
- `internal/daemon` — headless background monitoring service executing periodic quality tests and API reporting.
- `internal/ui` — the Bubble Tea model/view/update loop, keyboard shortcuts, and rendering (`app.go`, `layout.go`, `measure.go`).

### Operational Modes:
1. **Interactive TUI (`eping`)**: Visual target browsing and on-demand ping/traceroute/quality testing (`n` shortcut).
2. **CLI Quality Probe (`eping quality`)**: One-shot probe producing an ANSI summary card or JSON (`--json`).
3. **Background Daemon (`eping daemon`)**: Continuous headless monitoring loop with randomized dynamic intervals (15–60 min).

## Data flow

1. **Target Ping**: The terminal client measures HTTP TTFB + traceroute, then submits to `POST /api/v1/targets/{target}/results`. Results are persisted to `ping_results` and displayed in `/history`.
2. **Network Quality Test**: The client probes 12 diverse web targets, evaluates DNS, TCP, TLS, TTFB, packet loss, and overall score, then submits to `POST /api/v1/network-quality`. Results are persisted to `network_quality_tests` and displayed in `/quality`.

## Storage

- **PostgreSQL** in development/production (see `config/database.php`, `DATABASE_URL` in `.env`). Optimized on Windows with automatic IPv4 hostaddr resolution to eliminate libpq dual-stack timeouts.
- **SQLite (`:memory:`)** in the test environment (`phpunit.xml`), so running `composer run test` or `php artisan test` never touches a real database.
