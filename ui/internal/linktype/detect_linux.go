//go:build linux

package linktype

import (
	"net"
	"os"
	"os/exec"
	"path/filepath"
	"strings"
)

func detect() Type {
	iface := defaultIface()
	if iface == "" {
		return Unknown
	}
	if t := classifySysfs(iface); t != Unknown {
		return t
	}
	return classifyName(iface)
}

func defaultIface() string {
	out, err := exec.Command("ip", "route", "show", "default").Output()
	if err != nil {
		return ""
	}
	fields := strings.Fields(string(out))
	for i := 0; i < len(fields)-1; i++ {
		if fields[i] == "dev" {
			return fields[i+1]
		}
	}
	return ""
}

func classifySysfs(iface string) Type {
	wireless := filepath.Join("/sys/class/net", iface, "wireless")
	if st, err := os.Stat(wireless); err == nil && st.IsDir() {
		return WiFi
	}
	typePath := filepath.Join("/sys/class/net", iface, "type")
	b, err := os.ReadFile(typePath)
	if err != nil {
		return Unknown
	}
	// ARPHRD_ETHER = 1; many wifi devices also report 1, so wireless dir is preferred.
	if strings.TrimSpace(string(b)) == "1" {
		ni, err := net.InterfaceByName(iface)
		if err == nil && ni.Flags&net.FlagUp != 0 {
			return Ethernet
		}
	}
	return Unknown
}
