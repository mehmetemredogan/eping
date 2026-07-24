#!/usr/bin/env pwsh
<#
.SYNOPSIS
    Cross-compiles the eping terminal client for Windows, Linux and macOS
    (amd64 + arm64) without requiring `make`.

.EXAMPLE
    ./build.ps1
    Builds all platforms into ./dist

.EXAMPLE
    $env:VERSION = "v1.2.3"; ./build.ps1
    Overrides the embedded version string
#>

$ErrorActionPreference = "Stop"
Set-Location -Path $PSScriptRoot

$Binary = "eping"
$Dist = "dist"

# Version resolution order (see docs/BUILD.md#versioning):
#   1. Explicit $env:VERSION.
#   2. The exact git tag pointing at HEAD (e.g. v0.1).
#   3. The ./VERSION file (next planned release) plus a dev/commit suffix.
#   4. "dev" if none of the above are available (e.g. no git, no VERSION file).
function Resolve-Version {
    if ($env:VERSION) { return $env:VERSION }

    $base = "dev"
    if (Test-Path "VERSION") {
        $base = "v" + (Get-Content "VERSION" -Raw).Trim() + "-dev"
    }

    try {
        $tag = git describe --tags --exact-match 2>$null
        if ($LASTEXITCODE -eq 0 -and $tag) { return $tag.Trim() }

        $sha = git rev-parse --short HEAD 2>$null
        if ($LASTEXITCODE -eq 0 -and $sha) {
            $dirty = ""
            git diff --quiet 2>$null
            $unstaged = $LASTEXITCODE -ne 0
            git diff --cached --quiet 2>$null
            $staged = $LASTEXITCODE -ne 0
            if ($unstaged -or $staged) { $dirty = "-dirty" }
            return "$base+$($sha.Trim())$dirty"
        }
    } catch {
        # git not available - fall through to the VERSION-file-only value.
    }

    return $base
}

$Version = Resolve-Version
$Ldflags = "-s -w -X main.version=$Version"

$Platforms = @(
    @{ GOOS = "windows"; GOARCH = "amd64" },
    @{ GOOS = "windows"; GOARCH = "arm64" },
    @{ GOOS = "linux";   GOARCH = "amd64" },
    @{ GOOS = "linux";   GOARCH = "arm64" },
    @{ GOOS = "darwin";  GOARCH = "amd64" },
    @{ GOOS = "darwin";  GOARCH = "arm64" }
)

New-Item -ItemType Directory -Force -Path $Dist | Out-Null

foreach ($p in $Platforms) {
    $ext = if ($p.GOOS -eq "windows") { ".exe" } else { "" }
    $out = Join-Path $Dist "$Binary-$($p.GOOS)-$($p.GOARCH)$ext"
    Write-Host "Building $out (version $Version)"

    $env:CGO_ENABLED = "0"
    $env:GOOS = $p.GOOS
    $env:GOARCH = $p.GOARCH
    try {
        go build -ldflags $Ldflags -o $out .
    } finally {
        Remove-Item Env:\GOOS -ErrorAction SilentlyContinue
        Remove-Item Env:\GOARCH -ErrorAction SilentlyContinue
        Remove-Item Env:\CGO_ENABLED -ErrorAction SilentlyContinue
    }
}

Write-Host "Done. Binaries written to $Dist/"
