package linktype

import "strings"

// Type is the client's active uplink medium used for the measurement.
type Type string

const (
	WiFi     Type = "wifi"
	Ethernet Type = "ethernet"
	Unknown  Type = "unknown"
)

// Detect returns wifi, ethernet, or unknown for the default egress interface.
func Detect() Type {
	return detect()
}

func classifyName(name string) Type {
	n := strings.ToLower(name)
	switch {
	case containsAny(n, "wi-fi", "wifi", "wlan", "wireless", "802.11", "airport"):
		return WiFi
	case containsAny(n, "ethernet", "eth0", "eth1", "en0", "en1", "lan", "realtek", "gigabit", "usblan", "wired"):
		return Ethernet
	default:
		return Unknown
	}
}

func classifyMedia(media string) Type {
	m := strings.ToLower(media)
	switch {
	case containsAny(m, "802.11", "native 802.11", "wireless", "wifi"):
		return WiFi
	case containsAny(m, "802.3", "ethernet", "wired"):
		return Ethernet
	default:
		return Unknown
	}
}

func containsAny(s string, needles ...string) bool {
	for _, n := range needles {
		if n != "" && strings.Contains(s, n) {
			return true
		}
	}
	return false
}
