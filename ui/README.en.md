<p align="center"><a href="README.md">🇹🇷 Türkçe</a> · <b>🇬🇧 English</b></p>

# ePing (Extended Ping) — Go Terminal Client

The ePing Go client is a multi-modal network performance and latency testing tool featuring high-precision HTTP TTFB, traceroute hop analysis, and a real-world **Network Quality Test** probing global services.

## Execution Modes

The client can be run in three distinct modes:

```text
USAGE:
  eping                    Launch the interactive terminal UI (TUI)
  eping quality [flags]    Probe network quality across real-world web targets
  eping daemon [flags]     Run background headless daemon for periodic monitoring
  eping version            Show version information
```

---

### 1. Interactive TUI Mode (`eping`)

Browse targets, filter by category/provider, and run on-demand ping and traceroute measurements.

```bash
cd ui
go mod tidy
go run .
# or with compiled binary:
./pinglab.exe
```

#### Keyboard Shortcuts

| Key | Action |
|---|---|
| `/` | Live search and filtering |
| `[` `]` | Cycle categories (AWS, Azure, CDN, Gaming, etc.) |
| `enter` / `space` | Measure selected target (HTTP TTFB + Traceroute) |
| `a` | Measure all filtered targets in batch (shared `session_id`) |
| `n` | **Launch Real-World Network Quality Test (calculates Score & Grade)** |
| `e` | Expand/collapse provider group |
| `i` | Detail panel (p50/p95, DNS/TCP/TLS, hop table, trend vs. history) |
| `l` | Log in to platform (`username` / `password`) |
| `o` | Log out |
| `r` | Refresh targets from API |
| `q` | Quit |

---

### 2. CLI Network Quality Test (`eping quality`)

Runs a headless, comprehensive test probing 12 independent major global and regional web services (Google, Cloudflare, Microsoft, Apple, GitHub, AWS, Wikipedia, YouTube, Netflix, e-Devlet, Trendyol, Hetzner) using `httptrace`.

```bash
# Run probe and display ANSI terminal summary card:
go run . quality

# Output JSON (for CI/CD pipelines and scripts):
go run . quality --json

# Run local test without uploading to the API server:
go run . quality --no-upload

# Specify custom per-target timeout:
go run . quality --timeout 8s
```

#### Metrics & Score Card:
- **DNS Lookup Time**
- **TCP Connection Handshake**
- **TLS Secure Handshake**
- **Time To First Byte (TTFB)**
- **Total Request Duration & HTTP Status Code (200, 301, etc.)**
- **0–100 Quality Score & Letter Grade (A+, A, B, C, D, F)**
- **Overall Network Status (`excellent`, `good`, `fair`, `poor`, `critical`)**

---

### 3. Background Daemon Mode (`eping daemon`)

Designed for headless servers, home labs, or background monitoring. Periodically performs network quality tests, logs summary metrics, and reports results to the ePing platform if authenticated.

```bash
# Start daemon mode:
go run . daemon
# or with compiled binary:
./pinglab.exe daemon

# Run once and exit immediately (cron jobs / container health checks):
go run . daemon --once
```

> [!IMPORTANT]
> **Dynamic & Randomized Measurement Interval:** The test interval cannot be configured by the user. Tests are **randomly scheduled between 15 minutes and 60 minutes each time** to eliminate artificial traffic spikes and ensure natural, realistic sampling across different hours of the day.

#### OS Autostart on Boot
- **Windows (PowerShell Task):**
  `schtasks /create /tn "ePingDaemon" /tr "C:\eping\pinglab.exe daemon" /sc onlogon /rl limited`
- **Linux (systemd User Service):**
  Create `~/.config/systemd/user/eping.service` and run `systemctl --user enable --now eping.service`.
- **macOS (launchd Agent):**
  Create `~/Library/LaunchAgents/tr.mehmetemredogan.eping.plist` and run `launchctl load ...`.
(See the main [README.en.md](../README.en.md#os-autostart-on-boot-boot-service) for complete service configurations.)

---

## Configuration

Configuration resolution order:
1. Environment variables: `EPING_API_URL` or `PINGLAB_API_URL`
2. Configuration file: `%AppData%/eping/config.yaml` (Windows) / `~/.config/eping/config.yaml` (Linux/macOS)
3. Baked-in default: `https://ping.mehmetemredogan.tr`

Example `config.yaml`:
```yaml
api_url: https://ping.mehmetemredogan.tr
samples: 4
concurrency: 6
token: "1|abcdef..."
username: "med"
trace_on_measure: true
trace_on_all: false
```

---

## Measurement Details

**Ping (HTTP TTFB)** and **Tracert (traceroute)** results are always separated:
- In the target list: a separate, colored `↳ Tracert: ...` line below the ping line.
- In the footer: two separate lines, `Ping: ...` and `Tracert: ...`.
- In the `i` (detail) panel: two distinct sections titled `── PING (HTTP TTFB) ──` and `── TRACEROUTE ──`.

---

## Building

For multi-platform build options, see [`../docs/BUILD.md`](../docs/BUILD.md). Quick summary:

```bash
# Build for current host platform
go build -o eping .

# Build for all platforms (via Makefile)
make build-all

# Windows (PowerShell)
./build.ps1

# Linux / macOS (Bash)
./build.sh
```
