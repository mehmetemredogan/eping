package traceroute

import (
	"context"
	"fmt"
	"net"
	"net/url"
	"os/exec"
	"regexp"
	"runtime"
	"strconv"
	"strings"
	"time"
)

type HopKind string

const (
	KindLoopback  HopKind = "loopback"
	KindLinkLocal HopKind = "link_local"
	KindPrivate   HopKind = "private"
	KindCGNAT     HopKind = "cgnat"
	KindPublic    HopKind = "public"
	KindUnknown   HopKind = "unknown"
	KindTimeout   HopKind = "timeout"
)

type Hop struct {
	TTL     int       `json:"ttl"`
	IP      string    `json:"ip,omitempty"`
	RTTsMs  []float64 `json:"rtts_ms,omitempty"`
	AvgMs   *float64  `json:"avg_ms,omitempty"`
	Timeout bool      `json:"timeout"`
	Local   bool      `json:"local"` // private / loopback / link-local / CGNAT
	Kind    HopKind   `json:"kind"`
}

type Result struct {
	Host         string `json:"host"`
	ResolvedHint string `json:"resolved_hint,omitempty"`
	Tool         string `json:"tool"`
	Command      string `json:"command"`
	HopCount     int    `json:"hop_count"`
	Reached      bool   `json:"reached"`
	LocalHops    int    `json:"local_hops"`
	PublicHops   int    `json:"public_hops"`
	TimeoutHops  int    `json:"timeout_hops"`
	Hops         []Hop  `json:"hops"`
	DurationMs   int64  `json:"duration_ms"`
	Raw          string `json:"raw,omitempty"`
	Error        string `json:"error,omitempty"`
}

var (
	reWinHop  = regexp.MustCompile(`^\s*(\d+)\s+(.+)$`)
	reWinIP   = regexp.MustCompile(`(\d{1,3}(?:\.\d{1,3}){3})`)
	reWinRTT  = regexp.MustCompile(`(<\d+|\d+)\s*ms`)
	reUnixHop = regexp.MustCompile(`^\s*(\d+)\s*:?\s+(.+)$`) // ":" after hop number for tracepath
	reIPv4    = regexp.MustCompile(`(\d{1,3}(?:\.\d{1,3}){3})`)
	reIPv6    = regexp.MustCompile(`([0-9a-fA-F]{0,4}(?::[0-9a-fA-F]{0,4}){2,7})`)
	reUnixRTT = regexp.MustCompile(`(\d+(?:\.\d+)?)\s*ms`)
)

// Trace runs OS traceroute (Windows: tracert, Unix: traceroute/tracepath).
func Trace(ctx context.Context, host string) Result {
	host = normalizeHost(host)
	start := time.Now()
	tool, args, parser := pickCommand(host)
	cmdLine := tool + " " + strings.Join(args, " ")

	out, err := run(ctx, tool, args)
	res := Result{
		Host:       host,
		Tool:       tool,
		Command:    cmdLine,
		Raw:        out,
		DurationMs: time.Since(start).Milliseconds(),
	}
	if err != nil && strings.TrimSpace(out) == "" {
		res.Error = err.Error()
		return res
	}

	hops, hint := parser(out)
	res.Hops = hops
	res.ResolvedHint = hint
	res.summarize()
	if err != nil && !res.Reached && len(hops) == 0 {
		res.Error = err.Error()
	}
	return res
}

// Recompute recalculates hop-count/reached/local/public/timeout summary
// fields from Hops. Exported so tests (and callers that build/mutate a
// Result directly) can trigger the same derivation logic used by Trace.
func (r *Result) Recompute() {
	r.summarize()
}

func (r *Result) summarize() {
	r.HopCount = 0
	r.LocalHops = 0
	r.PublicHops = 0
	r.TimeoutHops = 0
	r.Reached = false
	for _, h := range r.Hops {
		r.HopCount++
		if h.Timeout {
			r.TimeoutHops++
			continue
		}
		if h.Local {
			r.LocalHops++
		}
		if h.Kind == KindPublic {
			r.PublicHops++
		}
	}
	// Reached only if the very last hop replied — an earlier reply followed by
	// trailing timeouts means the destination itself never answered.
	if n := len(r.Hops); n > 0 {
		last := r.Hops[n-1]
		if !last.Timeout && last.IP != "" {
			if r.ResolvedHint == "" || last.IP == r.ResolvedHint {
				r.Reached = true
			}
		}
	}
}

func pickCommand(host string) (tool string, args []string, parser func(string) ([]Hop, string)) {
	if runtime.GOOS == "windows" {
		return "tracert", []string{"-d", "-h", "30", "-w", "1000", host}, ParseWindows
	}
	if _, err := exec.LookPath("traceroute"); err == nil {
		return "traceroute", []string{"-n", "-m", "30", "-w", "1", "-q", "3", host}, ParseUnix
	}
	if _, err := exec.LookPath("tracepath"); err == nil {
		return "tracepath", []string{"-n", host}, ParseUnix
	}
	return "traceroute", []string{"-n", "-m", "30", host}, ParseUnix
}

func run(ctx context.Context, tool string, args []string) (string, error) {
	if _, err := exec.LookPath(tool); err != nil {
		return "", fmt.Errorf("%s bulunamadı: %w", tool, err)
	}
	cctx, cancel := context.WithTimeout(ctx, 90*time.Second)
	defer cancel()
	cmd := exec.CommandContext(cctx, tool, args...)
	out, err := cmd.CombinedOutput()
	return string(out), err
}

func ParseWindows(raw string) ([]Hop, string) {
	hint := ""
	if m := regexp.MustCompile(`\[(\d{1,3}(?:\.\d{1,3}){3})\]`).FindStringSubmatch(raw); len(m) > 1 {
		hint = m[1]
	}
	var hops []Hop
	for _, line := range strings.Split(raw, "\n") {
		line = strings.TrimRight(line, "\r")
		m := reWinHop.FindStringSubmatch(line)
		if m == nil {
			continue
		}
		ttl, _ := strconv.Atoi(m[1])
		rest := m[2]
		h := Hop{TTL: ttl, Kind: KindUnknown}
		if strings.Contains(strings.ToLower(rest), "request timed out") || strings.Count(rest, "*") >= 3 {
			h.Timeout = true
			h.Kind = KindTimeout
			hops = append(hops, h)
			continue
		}
		rtts := reWinRTT.FindAllStringSubmatch(rest, -1)
		for _, rm := range rtts {
			v := strings.TrimPrefix(rm[1], "<")
			if f, err := strconv.ParseFloat(v, 64); err == nil {
				if strings.HasPrefix(rm[1], "<") && f == 1 {
					f = 0.5
				}
				h.RTTsMs = append(h.RTTsMs, f)
			}
		}
		ip := reWinIP.FindString(rest)
		if ip == "" {
			if v6 := reIPv6.FindString(rest); v6 != "" && strings.Contains(v6, ":") {
				ip = v6
			}
		}
		if ip != "" {
			h.IP = ip
			classifyHop(&h)
		} else if len(h.RTTsMs) == 0 {
			h.Timeout = true
			h.Kind = KindTimeout
		}
		if len(h.RTTsMs) > 0 {
			avg := mean(h.RTTsMs)
			h.AvgMs = &avg
		}
		hops = append(hops, h)
	}
	return hops, hint
}

func ParseUnix(raw string) ([]Hop, string) {
	hint := ""
	if m := regexp.MustCompile(`\((\d{1,3}(?:\.\d{1,3}){3})\)`).FindStringSubmatch(raw); len(m) > 1 {
		hint = m[1]
	}
	var hops []Hop
	for _, line := range strings.Split(raw, "\n") {
		line = strings.TrimSpace(line)
		m := reUnixHop.FindStringSubmatch(line)
		if m == nil {
			continue
		}
		ttl, _ := strconv.Atoi(m[1])
		rest := m[2]
		h := Hop{TTL: ttl, Kind: KindUnknown}
		stars := strings.Count(rest, "*")
		rtts := reUnixRTT.FindAllStringSubmatch(rest, -1)
		for _, rm := range rtts {
			if f, err := strconv.ParseFloat(rm[1], 64); err == nil {
				h.RTTsMs = append(h.RTTsMs, f)
			}
		}
		ip := reIPv4.FindString(rest)
		if ip == "" {
			if v6 := reIPv6.FindString(rest); v6 != "" && strings.Contains(v6, ":") {
				ip = v6
			}
		}
		if ip != "" {
			h.IP = ip
			classifyHop(&h)
		}
		noReply := strings.Contains(strings.ToLower(rest), "no reply")
		if ip == "" && len(h.RTTsMs) == 0 && (stars >= 1 || noReply) {
			h.Timeout = true
			h.Kind = KindTimeout
		}
		if len(h.RTTsMs) > 0 {
			avg := mean(h.RTTsMs)
			h.AvgMs = &avg
		}
		hops = append(hops, h)
	}
	return hops, hint
}

func classifyHop(h *Hop) {
	ip := net.ParseIP(h.IP)
	if ip == nil {
		h.Kind = KindUnknown
		return
	}
	switch {
	case ip.IsLoopback():
		h.Kind = KindLoopback
		h.Local = true
	case ip.IsLinkLocalUnicast() || ip.IsLinkLocalMulticast():
		h.Kind = KindLinkLocal
		h.Local = true
	case ip.IsPrivate():
		h.Kind = KindPrivate
		h.Local = true
	case isCGNAT(ip):
		h.Kind = KindCGNAT
		h.Local = true
	default:
		h.Kind = KindPublic
		h.Local = false
	}
}

func isCGNAT(ip net.IP) bool {
	ip4 := ip.To4()
	if ip4 == nil {
		return false
	}
	// 100.64.0.0/10
	return ip4[0] == 100 && ip4[1] >= 64 && ip4[1] <= 127
}

func normalizeHost(host string) string {
	h := strings.TrimSpace(host)
	if h == "" {
		return h
	}
	if strings.Contains(h, "://") {
		if u, err := url.Parse(h); err == nil && u.Host != "" {
			h = u.Host
		}
	}
	if i := strings.Index(h, "/"); i >= 0 {
		h = h[:i]
	}
	if hostOnly, _, err := net.SplitHostPort(h); err == nil {
		h = hostOnly
	}
	return strings.Trim(h, "[]")
}

func mean(vals []float64) float64 {
	if len(vals) == 0 {
		return 0
	}
	s := 0.0
	for _, v := range vals {
		s += v
	}
	return s / float64(len(vals))
}

// ClassifyIP exports hop kind for tests / UI.
func ClassifyIP(ipStr string) (HopKind, bool) {
	h := Hop{IP: ipStr}
	classifyHop(&h)
	return h.Kind, h.Local
}
