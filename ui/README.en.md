<p align="center"><a href="README.md">🇹🇷 Türkçe</a> · <b>🇬🇧 English</b></p>

# ePing (Extended Ping) — Go UI

A terminal client that manages the target list and session against the Laravel API.
Latency: local HTTP **TTFB** (DNS/TCP/TLS breakdown, with p50/p95).
Path: OS `tracert` / `traceroute` / `tracepath` (hop-by-hop analysis).

## Installation

```bash
cd ui
go mod tidy
go run .
```

Configuration: `%AppData%/eping/config.yaml` or the `EPING_API_URL` environment variable.

## Keyboard shortcuts

| Key | Action |
|-----|--------|
| `/` | Search |
| `[` `]` | Category |
| `enter` | Measure + traceroute |
| `a` | Measure all filtered targets |
| `e` | Expand/collapse group |
| `i` | Detail panel (p50/p95, DNS/TCP/TLS, hop table, trend vs. history) |
| `l` | Log in |
| `o` | Log out (session) |
| `r` | Refresh |
| `q` | Quit |

## Measurement details

**Ping (HTTP TTFB)** and **Tracert (traceroute)** results are always shown in
separate, clearly labeled sections and are never merged into a single line:

- In the target list: a separate, differently colored `↳ Tracert: ...` line right
  below the ping line.
- In the footer panel: two separate lines, `Ping: ...` and `Tracert: ...`.
- In the `i` (detail) panel: two separate sections titled `── PING (HTTP TTFB) ──`
  and `── TRACEROUTE ──`.

The ping section shows average/min/max/jitter alongside **p50/p95** percentiles
and the **DNS / TCP / TLS / TTFB** breakdown for each request. When the tracert
section is enabled, it shows per-hop IP, latency, and classification (loopback /
link-local / private / CGNAT / public).

## Historical comparison (logged-in users)

Once logged in, the client fetches the user's historical measurements from the
server's `/api/v1/results/trend` endpoint and:

- Shows the overall network trend (`↑ improving` / `↓ degrading` / `→ stable`) in
  the header bar.
- After each measurement, adds an insight comparing that target's latest result
  to its own historical average (e.g. "vs. history: 18% faster (improving, 32 past
  measurements)").

The comparison averages the last few measurements (recent) and compares them
against the average of the earlier history (baseline); if there isn't enough
historical data, it's marked as "insufficient data".

## Building

For cross-platform (Windows/Linux/macOS) build options and the automated release
process, see [`../docs/BUILD.md`](../docs/BUILD.md). Quick summary:

```bash
# Current platform only
go build -o eping .

# All platforms (Makefile)
make build-all

# On Windows with PowerShell
./build.ps1

# On Linux/macOS with shell
./build.sh
```
