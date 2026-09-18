<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.3%2B-777bb4?logo=php&logoColor=white" alt="PHP">
  <img src="https://img.shields.io/badge/Laravel-13-ff2d20?logo=laravel&logoColor=white" alt="Laravel">
  <img src="https://img.shields.io/badge/Go-1.24%2B-00add8?logo=go&logoColor=white" alt="Go">
  <a href="https://github.com/mehmetemredogan/eping/actions/workflows/ui-ci.yml"><img src="https://github.com/mehmetemredogan/eping/actions/workflows/ui-ci.yml/badge.svg" alt="UI CI"></a>
  <img src="https://img.shields.io/badge/license-MIT-informational" alt="License">
</p>

<p align="center"><a href="README.md">🇹🇷 Türkçe</a> · <b>🇬🇧 English</b></p>

# ePing (Extended Ping)

ePing is a latency-testing platform that lets you measure your network's round-trip
time to various cloud providers, game servers, and CDNs. It consists of two parts:

- **Web application** (this repository — Laravel 13): account/session management,
  a member panel showing the history of tests run with the terminal client, an
  admin panel, and the REST API the terminal client talks to. There is **no**
  browser-based ping tool — all measurements are performed by the terminal client.
- **Terminal client** ([`ui/`](ui/) — Go): a TUI (terminal user interface) tool that
  talks to the API and is the actual measurement tool, combining HTTP latency
  measurement with traceroute analysis.

  **Why did we write this project?**

We develop and manage software.
Seeing which platforms we connect to via which routes and with what latency is critical for us to create efficient infrastructures. We also want to ensure the best possible access to the online games we play and the platforms we use in our daily lives.

Through ePing software, we want to identify the internet service providers that allow us to access the internet fastest via the best routes and change our subscriptions accordingly.

Data collected by ePing is anonymized and published in the statistics section.

## Table of contents

- [Features](#features)
- [Architecture](#architecture)
- [Requirements](#requirements)
- [Installation](#installation)
- [Development](#development)
- [Testing](#testing)
- [Terminal client configuration (ui/)](#terminal-client-configuration-ui)
- [OS autostart on boot (boot service)](#os-autostart-on-boot-boot-service)
- [Language support](#language-support)
- [CI/CD and builds](#cicd-and-builds)
- [Project structure](#project-structure)
- [Documentation](#documentation)
- [Contributing](#contributing)
- [License](#license)

## Features

- **Global target list** — AWS, Azure, GCP, Cloudflare, DigitalOcean, Oracle,
  Hetzner, Vultr, OVH, game servers and more, grouped by category and provider.
- **Terminal client (TUI)** — High-precision HTTP TTFB (DNS/TCP/TLS breakdown, p50/p95) plus
  OS `tracert`/`traceroute`-based hop analysis.
- **Real-World Network Quality Test** — Concurrently probes 12 major independent global and regional
  web services (Google, Cloudflare, Microsoft, Apple, GitHub, AWS, Wikipedia, YouTube, Netflix,
  e-Devlet, Trendyol, Hetzner) measuring DNS, TCP, TLS, TTFB, total duration, packet loss,
  0–100 quality score, and A+/F grade.
- **Background Headless Daemon** — Runs silently in the background, periodically evaluates network
  quality, logs metrics, and reports them to the platform automatically.
- **Network Quality Web Dashboard** — View test history, score trends, and target-by-target breakdowns
  at `/quality`.
- **Member panel** — Lists your terminal test history grouped by date and `session_id`.
- **Historical comparison** — For logged-in users, shows an improving/degrading
  trend compared to their measurement history (via the API, `/api/v1/results/trend`).
- **Admin panel** — Manage targets, providers, and test logs; dashboard statistics.
- **Minimal authentication** — Username + password only (no email/real name
  required), with Sanctum tokens on the API side.
- **Multilingual UI** — Instant switching between Turkish and English (see
  [Language support](#language-support)).

## Architecture

The backend stores the target list, ping results, and network quality tests in PostgreSQL (or SQLite for
tests). Measurements are performed exclusively by the Go terminal client and submitted via the REST API;
the web application presents these in the member panel, network quality dashboard, and admin panel.

## Requirements

- PHP >= 8.3, Composer
- Node.js >= 18, npm
- PostgreSQL (production/development) or SQLite (testing)
- Go >= 1.24 (only needed to build the `ui/` terminal client)

## Installation

```bash
git clone git@github.com:mehmetemredogan/eping.git
cd eping

composer install
cp .env.example .env
php artisan key:generate

# Edit the DB_* / DATABASE_URL values in .env
php artisan migrate --seed

npm install
npm run build
```

For a one-command setup (dependencies, .env, migrations, frontend build):

```bash
composer run setup
```

## Execution Modes

ePing provides multiple execution modes for various workflows:

### 1. Web Application (Laravel)

```bash
# Start web server only:
php artisan serve

# Start server, queue worker, and Vite together:
composer run dev
```

The application runs at `http://localhost:8000` by default.

### 2. Terminal Client — Interactive TUI Mode

Browse targets, filter, and run interactive ping and traceroute tests:

```bash
cd ui
go run .
# or with compiled binary:
./pinglab.exe
```

**TUI Shortcuts:**

| Key | Action |
|---|---|
| `/` | Live search and filter |
| `[` `]` | Cycle categories |
| `enter` / `space` | Measure selected target (HTTP TTFB + Traceroute) |
| `a` | Batch measure all filtered targets (shared `session_id`) |
| `n` | **Launch Real-World Network Quality Test (Score & Grade)** |
| `e` | Expand/collapse provider group |
| `i` | Detail panel (p50/p95, DNS/TCP/TLS, hop table, trend vs. history) |
| `l` | Log in to platform (`username` / `password`) |
| `o` | Log out |
| `r` | Refresh targets from API |
| `q` | Quit |

### 3. Terminal Client — CLI Network Quality Test (One-Shot)

Without entering the TUI, quickly probe 12 real web services and output an ANSI summary card:

```bash
cd ui
go run . quality
# or
./pinglab.exe quality
```

**Flags:**
- `--json` : Output JSON for pipelines and automation (`go run . quality --json`).
- `--no-upload` : Local evaluation only, does not upload to API server.
- `--timeout <duration>` : Per-target timeout (default: `6s`).

### 4. Terminal Client — Background Daemon Mode (Headless Monitoring)

For background automated monitoring on servers or personal machines:

```bash
cd ui
go run . daemon
# or with compiled binary:
./pinglab.exe daemon
```

> [!IMPORTANT]
> **Dynamic Measurement Interval:** The test interval cannot be configured by the user. To prevent artificial synchronized traffic spikes and collect realistic network quality samples across various times of day, the interval is **randomly scheduled between a minimum of 15 minutes and a maximum of 60 minutes each cycle** (e.g. 21 min after the first run, 54 min after the next, 17 min afterwards).

**Flags:**
- `--once` : Run a single measurement cycle and exit immediately (useful for cron jobs or container health checks).

---

## OS Autostart on Boot (Boot Service)

To run `eping daemon` automatically on system boot or user logon:

### 🪟 Windows (Task Scheduler or Startup Folder)

#### Option A: Command Line / PowerShell (Recommended)
Open an Administrator PowerShell prompt and create a logon task:

```powershell
# Adjust 'C:\eping\pinglab.exe' to your binary's actual path:
schtasks /create /tn "ePingDaemon" /tr "C:\eping\pinglab.exe daemon" /sc onlogon /rl limited
```

To stop or delete the task:
```powershell
schtasks /delete /tn "ePingDaemon" /f
```

#### Option B: Startup Folder
1. Press `Win + R`, type `shell:startup` and hit Enter.
2. Create a shortcut to `pinglab.exe` inside the opened directory.
3. Right-click the shortcut, select **Properties**.
4. Append ` daemon` to the **Target** field (e.g., `C:\eping\pinglab.exe daemon`).
5. Set **Run** to "Minimized".

---

### 🐧 Linux (systemd User Service)

To run on user logon or system startup:

1. Create user service directory:
   ```bash
   mkdir -p ~/.config/systemd/user
   ```

2. Create `~/.config/systemd/user/eping.service`:
   ```ini
   [Unit]
   Description=ePing Network Quality Daemon
   After=network-online.target
   Wants=network-online.target

   [Service]
   Type=simple
   ExecStart=/usr/local/bin/eping daemon
   Restart=always
   RestartSec=15

   [Install]
   WantedBy=default.target
   ```

3. Enable and start the service:
   ```bash
   systemctl --user daemon-reload
   systemctl --user enable --now eping.service
   ```

4. *(Optional - Servers)* Enable lingering so the service runs even when not logged in:
   ```bash
   loginctl enable-linger $USER
   ```

Check live service logs:
```bash
journalctl --user -u eping.service -f
```

---

### 🍏 macOS (launchd Agent)

To run automatically upon logging into macOS:

1. Create `~/Library/LaunchAgents/tr.mehmetemredogan.eping.plist`:
   ```xml
   <?xml version="1.0" encoding="UTF-8"?>
   <!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
   <plist version="1.0">
   <dict>
       <key>Label</key>
       <string>tr.mehmetemredogan.eping</string>
       <key>ProgramArguments</key>
       <array>
           <string>/usr/local/bin/eping</string>
           <string>daemon</string>
       </array>
       <key>RunAtLoad</key>
       <true/>
       <key>KeepAlive</key>
       <true/>
       <key>StandardOutPath</key>
       <string>/tmp/eping-daemon.log</string>
       <key>StandardErrorPath</key>
       <string>/tmp/eping-daemon.err</string>
   </dict>
   </plist>
   ```

2. Load and start the agent:
   ```bash
   launchctl load ~/Library/LaunchAgents/tr.mehmetemredogan.eping.plist
   ```

To unload:
```bash
launchctl unload ~/Library/LaunchAgents/tr.mehmetemredogan.eping.plist
```

## Testing

```bash
composer run test
# or
php artisan test

# For Go unit tests:
cd ui && go test -v ./...
```

Tests use SQLite (`:memory:`) via `phpunit.xml`, so your real database is never touched.

## Terminal Client Configuration (ui/)

Configuration path: `%AppData%/eping/config.yaml` (Windows) or `~/.config/eping/config.yaml`
(Linux/macOS), or `EPING_API_URL` environment variable.

For details, keyboard shortcuts, and measurement mechanics: [`ui/README.md`](ui/README.md)
([English](ui/README.en.md)).

## Language support

The web UI (login/register, member panel/history) and the admin panel are
fully translated into **Turkish (tr)** and **English (en)**. Language selection:

- Can be switched instantly via the `TR` / `EN` toggle in the top navigation
  (session-based, `session('locale')`).
- The default locale is controlled by `APP_LOCALE` in `.env` (see `.env.example`).
- Translation files live under `lang/tr/` and `lang/en/`, split into `ping.php`
  (general UI) and `admin.php` (admin panel).

To add a new language:

1. Create and translate `lang/<locale>/ping.php` and `lang/<locale>/admin.php`.
2. Add the new locale code to the `in_array`/`validate` lists in
   `app/Http/Middleware/SetLocale.php` and `app/Http/Controllers/LocaleController.php`.
3. Add the new option to the language selector `<select>`/buttons in
   `resources/views/layouts/ping.blade.php` and `layouts/admin.blade.php`.

## CI/CD and builds

There are two GitHub Actions workflows for the Go terminal client:

- **`ui-ci.yml`** — runs `go vet`, `go test`, and `go build` on Windows, Linux, and
  macOS runners for every push/PR touching `ui/**`.
- **`ui-release.yml`** — triggered by pushing a `v*` git tag **or** by running
  it manually from the Actions tab (`workflow_dispatch`). Either way it
  cross-compiles binaries for Windows (amd64/arm64), Linux (amd64/arm64), and
  macOS (amd64/arm64), archives them, and automatically creates a GitHub
  Release with the artifacts attached. The version is read from the tag name,
  or from [`ui/VERSION`](ui/VERSION) when triggered manually with no input —
  see [`docs/BUILD.md`](docs/BUILD.md#versioning).

To publish a new release, bump `ui/VERSION` and push a matching tag:

```bash
git tag v0.1.4
git push origin v0.1.4
```

or trigger it manually via **Actions → UI Release → Run workflow** without
creating a tag first (leaving the version input empty uses `ui/VERSION`,
currently `0.1.4`).

To build for all platforms locally, use `ui/Makefile`, `ui/build.sh`
(Linux/macOS), or `ui/build.ps1` (Windows); see [`docs/BUILD.md`](docs/BUILD.md)
for details.

## Project structure

```
app/                    Laravel application code (Controllers, Models, Services, Middleware)
config/                 Framework and application configuration
database/               Migrations and seeders
lang/                   tr/en translation files
resources/              Blade views, CSS, JS
routes/                 web.php, api.php, auth.php
tests/                  PHPUnit Feature/Unit tests
ui/                     Go-based terminal client (independent module)
docs/                   Architecture, API, and build documentation
.github/workflows/      CI/CD definitions
```

## Documentation

- [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) — System architecture and data flow
- [`docs/API.md`](docs/API.md) — REST API reference (`/api/v1/*`)
- [`docs/BUILD.md`](docs/BUILD.md) — Cross-platform build and release process for the Go client
- [`CONTRIBUTING.md`](CONTRIBUTING.md) — Contribution guide

## Contributing

Contributions are welcome! Please see [`CONTRIBUTING.md`](CONTRIBUTING.md).

## License

This project is licensed under the [MIT license](https://opensource.org/licenses/MIT).
