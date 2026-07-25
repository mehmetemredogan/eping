//go:build darwin

package linktype

import (
	"os/exec"
	"strings"
)

func detect() Type {
	out, err := exec.Command("route", "-n", "get", "default").Output()
	if err != nil {
		return Unknown
	}
	iface := ""
	for _, line := range strings.Split(string(out), "\n") {
		line = strings.TrimSpace(line)
		if strings.HasPrefix(line, "interface:") {
			iface = strings.TrimSpace(strings.TrimPrefix(line, "interface:"))
			break
		}
	}
	if iface == "" {
		return Unknown
	}
	hw, err := exec.Command("networksetup", "-listallhardwareports").Output()
	if err != nil {
		return classifyName(iface)
	}
	blocks := strings.Split(string(hw), "\n\n")
	for _, block := range blocks {
		if !strings.Contains(block, "Device: "+iface) {
			continue
		}
		for _, line := range strings.Split(block, "\n") {
			line = strings.TrimSpace(line)
			if strings.HasPrefix(line, "Hardware Port:") {
				port := strings.TrimSpace(strings.TrimPrefix(line, "Hardware Port:"))
				if t := classifyName(port); t != Unknown {
					return t
				}
			}
		}
	}
	return classifyName(iface)
}
