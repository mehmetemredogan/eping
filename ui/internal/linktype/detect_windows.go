//go:build windows

package linktype

import (
	"os/exec"
	"strings"
)

func detect() Type {
	if t := detectPowerShell(); t != Unknown {
		return t
	}
	return detectNetsh()
}

func detectPowerShell() Type {
	script := `
$cfg = Get-NetIPConfiguration | Where-Object { $_.IPv4DefaultGateway -ne $null -and $_.NetAdapter.Status -eq 'Up' } | Select-Object -First 1
if (-not $cfg) { exit 0 }
$a = $cfg.NetAdapter
Write-Output ("MEDIA=" + $a.MediaType)
Write-Output ("DESC=" + $a.InterfaceDescription)
Write-Output ("NAME=" + $a.Name)
`
	out, err := exec.Command("powershell", "-NoProfile", "-NonInteractive", "-Command", script).Output()
	if err != nil {
		return Unknown
	}
	var media, desc, name string
	for _, line := range strings.Split(string(out), "\n") {
		line = strings.TrimSpace(line)
		switch {
		case strings.HasPrefix(line, "MEDIA="):
			media = strings.TrimPrefix(line, "MEDIA=")
		case strings.HasPrefix(line, "DESC="):
			desc = strings.TrimPrefix(line, "DESC=")
		case strings.HasPrefix(line, "NAME="):
			name = strings.TrimPrefix(line, "NAME=")
		}
	}
	if t := classifyMedia(media); t != Unknown {
		return t
	}
	if t := classifyName(desc); t != Unknown {
		return t
	}
	return classifyName(name)
}

func detectNetsh() Type {
	out, err := exec.Command("netsh", "wlan", "show", "interfaces").Output()
	if err == nil {
		s := strings.ToLower(string(out))
		if strings.Contains(s, "state") && strings.Contains(s, "connected") {
			return WiFi
		}
	}
	return Unknown
}
