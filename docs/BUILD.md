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

**Option 1 — `make` (Linux/macOS/WSL, or Git Bash on Windows):**

```bash
cd ui
make build-all
# binaries written to ui/dist/
```

Other targets: `make build` (current platform), `make test`, `make vet`,
`make clean`.

> On Windows, run this from **Git Bash** or **WSL**, not from PowerShell or
> `cmd.exe`. GNU Make for native Windows falls back to `cmd.exe` as its shell
> when it can't find `sh.exe` on `PATH`, which breaks the POSIX shell syntax
> used in the `Makefile`; Git Bash/WSL put a real `sh.exe` on `PATH` so this
> isn't an issue there. If you're in PowerShell, use `build.ps1` instead
> (Option 3 below) — it's the native equivalent.

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

[`.github/workflows/ui-release.yml`](../.github/workflows/ui-release.yml) has
a `prepare` job that resolves a single version string used by every other
job, then:

1. Runs the Go test suite once on `ubuntu-latest`.
2. Cross-compiles the six OS/architecture binaries listed above (from a single
   Linux runner, using `GOOS`/`GOARCH`; no per-OS runner is needed since Go
   cross-compiles natively).
3. Packages each binary together with `ui/README.md`, `ui/README.en.md`, and
   `ui/config.yaml.example` into a `.zip` (Windows) or `.tar.gz`
   (Linux/macOS) archive.
4. Publishes a GitHub Release for the resolved version with all six archives
   attached and auto-generated release notes. The release's git tag is
   created automatically if it doesn't exist yet.

The workflow can be triggered two ways, and **both produce a real release**:

### 1. Tag push (normal flow)

```bash
git tag v0.2
git push origin v0.2
```

The version is taken directly from the tag name.

### 2. Manual dispatch

Go to **Actions → UI Release → Run workflow**:

- Leave the **version** input empty to release whatever is currently in
  [`ui/VERSION`](../ui/VERSION) — this is the easiest way to (re-)publish the
  "current" version without creating a tag by hand first.
- Or fill in **version** (e.g. `v0.2-rc1`) to cut an ad-hoc release.

> **Previously**, manual dispatch silently built artifacts but never
> published a release, because the publish step was gated behind
> `if: startsWith(github.ref, 'refs/tags/v')` — which is never true for a
> `workflow_dispatch` run (the ref is a branch, not a tag). The release job
> no longer has that gate: the version is resolved once up front (from the
> tag, the manual input, or `ui/VERSION`) and is always used to publish the
> release, regardless of trigger type.

Within a few minutes of either trigger, a new release with the six platform
archives will appear under the repository's **Releases** page.

## Versioning

[`ui/VERSION`](../ui/VERSION) is the single source of truth for the
project's **next planned release** (currently `0.1.3`). It follows a plain
`MAJOR.MINOR` (or `MAJOR.MINOR.PATCH`) scheme — no leading `v`, no trailing
newline content beyond the number itself.

To cut a new release:

1. Bump `ui/VERSION` (e.g. `0.1.2` → `0.1.3`) in a commit/PR.
2. Either push a matching tag (`git tag v0.1.3 && git push origin v0.1.3`), or
   trigger **UI Release** manually with the version input left empty — both
   read the same value.

The binary embeds the resolved version at build time via
`-ldflags "-X main.version=..."`. Resolution order (identical across
`make`, `build.sh`, `build.ps1`, and CI):

1. An explicit override (`VERSION=v0.2 ./build.sh`, `make VERSION=v0.2`, or
   the CI `version` input).
2. The exact git tag pointing at the current commit (e.g. `v0.1`).
3. The `ui/VERSION` file, suffixed with `-dev+<short-sha>` for local/dev
   builds (e.g. `v0.1-dev+a1b2c3d`, with a `-dirty` suffix if the working
   tree has uncommitted changes).
4. `dev` as a last resort (no git, no `VERSION` file).

Versions below `v1.0.0` (i.e. `v0.x`) are automatically marked as a
**pre-release** on GitHub.

Check the running binary's version with:

```bash
eping --version
```

### First release

The project's first tagged release is **`v0.1`**, matching the initial value
of `ui/VERSION`. Subsequent releases (e.g. `v0.1.1`) follow the same process.
