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
- [Terminal client (ui/)](#terminal-client-ui)
- [Language support](#language-support)
- [CI/CD and builds](#cicd-and-builds)
- [Project structure](#project-structure)
- [Documentation](#documentation)
- [Contributing](#contributing)
- [License](#license)

## Features

- **Global target list** — AWS, Azure, GCP, Cloudflare, DigitalOcean, Oracle,
  Hetzner, Vultr, OVH, game servers and more, grouped by category and provider
  (tested via the terminal client).
- **Terminal client** — HTTP TTFB (DNS/TCP/TLS breakdown, p50/p95) plus
  OS `tracert`/`traceroute`-based hop analysis; the sole measurement tool.
- **Member panel** — Lists the history of tests you ran with the terminal
  client, grouped by date.
- **Historical comparison** — For logged-in users, shows an improving/degrading
  trend compared to their measurement history (via the API, `/api/v1/results/trend`).
- **Admin panel** — Manage targets, providers, and test logs; dashboard statistics.
- **Minimal authentication** — Username + password only (no email/real name
  required), with Sanctum tokens on the API side.
- **Multilingual UI** — Instant switching between Turkish and English (see
  [Language support](#language-support)).

## Architecture

The backend stores the target list and test results in PostgreSQL (or SQLite for
tests). Ping measurement is only performed by the Go terminal client and submitted
via the API; the web app displays those results in the member panel and admin panel.

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

Start the local server:

```bash
php artisan serve
```

The app runs at `http://localhost:8000` by default.

## Development

Runs the server, queue listener, log tailer, and Vite together in a single command:

```bash
composer run dev
```

## Testing

```bash
composer run test
# or
php artisan test
```

Tests use SQLite (`:memory:`) via `phpunit.xml`, so your real database is never touched.

## Terminal client (ui/)

The Go application in `ui/` fetches the target list from the API and displays
ping/traceroute measurements in a terminal UI.

```bash
cd ui
go mod tidy
go run .
```

Configuration: `%AppData%/eping/config.yaml` (Windows) or
`~/.config/eping/config.yaml` (Linux/macOS), or the `EPING_API_URL` environment
variable.

For details, keyboard shortcuts, and measurement logic, see
[`ui/README.en.md`](ui/README.en.md) ([Türkçe](ui/README.md)).

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
