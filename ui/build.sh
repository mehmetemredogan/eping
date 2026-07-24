#!/usr/bin/env bash
# Cross-compiles the eping terminal client for Windows, Linux and macOS
# (amd64 + arm64) without requiring `make`. Run from anywhere; the script
# changes into its own directory first.
#
# Usage:
#   ./build.sh            Build all platforms into ./dist
#   VERSION=v1.2.3 ./build.sh   Override the embedded version string
set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")"

BINARY="eping"
DIST="dist"

# Version resolution order (see docs/BUILD.md#versioning):
#   1. Explicit VERSION env var.
#   2. The exact git tag pointing at HEAD (e.g. v0.1).
#   3. The ./VERSION file (next planned release) plus a dev/commit suffix.
#   4. "dev" if none of the above are available (e.g. no git, no VERSION file).
resolve_version() {
  if [ -n "${VERSION:-}" ]; then
    echo "$VERSION"
    return
  fi
  if tag=$(git describe --tags --exact-match 2>/dev/null); then
    echo "$tag"
    return
  fi
  base="dev"
  if [ -f VERSION ]; then
    base="v$(tr -d '[:space:]' < VERSION)-dev"
  fi
  if sha=$(git rev-parse --short HEAD 2>/dev/null); then
    dirty=""
    if ! git diff --quiet 2>/dev/null || ! git diff --cached --quiet 2>/dev/null; then
      dirty="-dirty"
    fi
    echo "${base}+${sha}${dirty}"
  else
    echo "$base"
  fi
}

VERSION="$(resolve_version)"
LDFLAGS="-s -w -X main.version=${VERSION}"

PLATFORMS=(
  "windows/amd64"
  "windows/arm64"
  "linux/amd64"
  "linux/arm64"
  "darwin/amd64"
  "darwin/arm64"
)

mkdir -p "$DIST"

for platform in "${PLATFORMS[@]}"; do
  GOOS="${platform%/*}"
  GOARCH="${platform#*/}"
  ext=""
  [ "$GOOS" = "windows" ] && ext=".exe"
  out="$DIST/${BINARY}-${GOOS}-${GOARCH}${ext}"
  echo "Building ${out} (version ${VERSION})"
  CGO_ENABLED=0 GOOS="$GOOS" GOARCH="$GOARCH" go build -ldflags "$LDFLAGS" -o "$out" .
done

echo "Done. Binaries written to ${DIST}/"
