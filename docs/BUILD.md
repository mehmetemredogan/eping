# Building & releasing the terminal client

The Laravel web app doesn't need a compiled "build" beyond `npm run build`
(Vite assets); this document is about building and releasing the **Go terminal
client** in [`ui/`](../ui/) for Windows, Linux, and macOS.

## Local builds

### Build for your current platform only

```bash
cd ui
go build -o eping .        # eping.exe is produced automatically on Windows
./eping                    # or eping.exe on Windows
```

### Cross-compile for all platforms at once

Three equivalent options are provided so you can pick whichever fits your
environment — none of them require installing anything beyond a Go toolchain
(Go's cross-compilation is native, no C toolchain or Docker required, since
the project has no cgo dependencies).

**Option 1 — `make` (Linux/macOS/WSL, or Windows with `make` installed):**

```bash
cd ui
make build-all
# binaries written to ui/dist/
```

Other targets: `make build` (current platform), `make test`, `make vet`,
`make clean`.

**Option 2 — Bash script (Linux/macOS, Git Bash, WSL):**

```bash
cd ui
./build.sh
```

**Option 3 — PowerShell script (Windows):**

```powershell
cd ui
./build.ps1
```

All three produce the same six binaries:

| OS | Architecture | Output file |
|---|---|---|
| Windows | amd64 | `dist/eping-windows-amd64.exe` |
| Windows | arm64 | `dist/eping-windows-arm64.exe` |
| Linux | amd64 | `dist/eping-linux-amd64` |
| Linux | arm64 | `dist/eping-linux-arm64` |
| macOS | amd64 (Intel) | `dist/eping-darwin-amd64` |
| macOS | arm64 (Apple Silicon) | `dist/eping-darwin-arm64` |

You can override the embedded version string with the `VERSION` environment
variable, e.g. `VERSION=v1.2.3 ./build.sh` or
`$env:VERSION = "v1.2.3"; ./build.ps1`. The binary reports its version via
`eping --version`.

## Continuous Integration

[`.github/workflows/ui-ci.yml`](../.github/workflows/ui-ci.yml) runs on every
push to `main` and every pull request that touches `ui/**`:

- Runs on a matrix of `ubuntu-latest`, `windows-latest`, and `macos-latest`.
- Verifies `go.mod`/`go.sum` are tidy.
- Runs `go vet` and `go test ./...` on each OS.
- Builds a native binary as a smoke test.

This gives confidence that the client behaves correctly on all three target
operating systems before anything is released.

## Automated releases

[`.github/workflows/ui-release.yml`](../.github/workflows/ui-release.yml)
triggers when a tag matching `v*` (e.g. `v1.0.0`) is pushed:

1. Runs the Go test suite once on `ubuntu-latest`.
2. Cross-compiles the six OS/architecture binaries listed above (from a single
   Linux runner, using `GOOS`/`GOARCH`; no per-OS runner is needed since Go
   cross-compiles natively).
3. Packages each binary together with `ui/README.md`, `ui/README.en.md`, and
   `ui/config.yaml.example` into a `.zip` (Windows) or `.tar.gz`
   (Linux/macOS) archive.
4. Publishes a GitHub Release for the tag with all six archives attached and
   auto-generated release notes.

### Publishing a new release

```bash
git tag v1.0.0
git push origin v1.0.0
```

Within a few minutes, a new release with the six platform archives will
appear under the repository's **Releases** page.

You can also trigger a one-off dev build without pushing a tag via
**Actions → UI Release → Run workflow** (`workflow_dispatch`); it will build
and upload artifacts (but only publishes a GitHub Release when run from an
actual `v*` tag).

## Versioning

The binary embeds a version string at build time via
`-ldflags "-X main.version=..."`:

- Local builds (`make`, `build.sh`, `build.ps1`) default to
  `git describe --tags --always --dirty`, or `dev` if not in a git repo.
- CI release builds use the pushed tag name (e.g. `v1.0.0`).

Check the running binary's version with:

```bash
eping --version
```
