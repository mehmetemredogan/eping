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

$Version = $env:VERSION
if (-not $Version) {
    try {
        $Version = (git describe --tags --always --dirty 2>$null)
    } catch {
        $Version = $null
    }
}
if (-not $Version) { $Version = "dev" }

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
