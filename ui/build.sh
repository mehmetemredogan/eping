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
VERSION="${VERSION:-$(git describe --tags --always --dirty 2>/dev/null || echo dev)}"
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
