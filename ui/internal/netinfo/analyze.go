package netinfo

import (
	"fmt"
	"strings"

	"pinglab/ui/internal/ping"
	"pinglab/ui/internal/traceroute"
)

// Overall network health for a single target measurement.
type Status string

const (
	StatusExcellent   Status = "excellent"
	StatusGood        Status = "good"
	StatusFair        Status = "fair"
	StatusPoor        Status = "poor"
	StatusDegraded    Status = "degraded"
	StatusUnreachable Status = "unreachable"
	StatusUnknown     Status = "unknown"
)

type Report struct {
	Status      Status             `json:"status"`
	StatusLabel string             `json:"status_label"`
	Summary     string             `json:"summary"`
	Insights    []string           `json:"insights"` // combined ping+trace, kept for storage/back-compat
	Latency     LatencyView        `json:"latency"`
	Path        *traceroute.Result `json:"path,omitempty"`

	// Ping (HTTP TTFB) and traceroute are reported separately so callers
	// (the UI) can show them as two distinct sections instead of one
	// merged blob.
	PingSummary   string   `json:"ping_summary"`
	PingInsights  []string `json:"ping_insights"`
	PathSummary   string   `json:"path_summary,omitempty"`
	TraceInsights []string `json:"trace_insights,omitempty"`
}

type LatencyView struct {
	Quality     string   `json:"quality"`
	AvgMs       *float64 `json:"avg_ms,omitempty"`
	MinMs       *float64 `json:"min_ms,omitempty"`
	MaxMs       *float64 `json:"max_ms,omitempty"`
	P50Ms       *float64 `json:"p50_ms,omitempty"`
	P95Ms       *float64 `json:"p95_ms,omitempty"`
	JitterMs    *float64 `json:"jitter_ms,omitempty"`
	AvgDNSMs    *float64 `json:"avg_dns_ms,omitempty"`
	AvgTCPMs    *float64 `json:"avg_tcp_ms,omitempty"`
	AvgTLSMs    *float64 `json:"avg_tls_ms,omitempty"`
	SuccessRate float64  `json:"success_rate"`
	Samples     int      `json:"samples"`
}

// Analyze builds a network report from latency stats and optional traceroute.
func Analyze(lat ping.Stats, path *traceroute.Result) Report {
	lv := LatencyView{
		Quality:     lat.Quality,
		AvgMs:       lat.AvgMs,
		MinMs:       lat.MinMs,
		MaxMs:       lat.MaxMs,
		P50Ms:       lat.P50Ms,
		P95Ms:       lat.P95Ms,
		JitterMs:    lat.JitterMs,
		AvgDNSMs:    lat.AvgDNSMs,
		AvgTCPMs:    lat.AvgTCPMs,
		AvgTLSMs:    lat.AvgTLSMs,
		SuccessRate: lat.SuccessRate,
		Samples:     lat.Samples,
	}

	status := statusFromLatency(lat)

	var tInsights []string
	if path != nil {
		tInsights = pathInsights(path)
		status = mergeStatus(status, statusFromPath(path, lat))
	}
	pInsights := latencyInsights(lat)

	r := Report{
		Status:        status,
		StatusLabel:   statusLabelTR(status),
		Insights:      append(append([]string{}, tInsights...), pInsights...),
		Latency:       lv,
		Path:          path,
		PingSummary:   pingSummaryTR(lat),
		PingInsights:  pInsights,
		TraceInsights: tInsights,
	}
	if path != nil {
		r.PathSummary = pathSummaryTR(path)
	}
	r.Summary = buildSummary(r)
	return r
}

func statusFromLatency(lat ping.Stats) Status {
	if lat.Samples == 0 || lat.SuccessRate <= 0 || lat.AvgMs == nil {
		return StatusUnreachable
	}
	if lat.SuccessRate < 0.5 {
		return StatusUnreachable
	}
	if lat.SuccessRate < 0.75 {
		return StatusDegraded
	}
	avg := *lat.AvgMs
	jit := 0.0
	if lat.JitterMs != nil {
		jit = *lat.JitterMs
	}
	switch {
	case avg < 40 && jit < 15:
		return StatusExcellent
	case avg < 80 && jit < 30:
		return StatusGood
	case avg < 180:
		return StatusFair
	default:
		return StatusPoor
	}
}

func statusFromPath(path *traceroute.Result, lat ping.Stats) Status {
	if path.Error != "" && path.HopCount == 0 {
		return StatusUnknown
	}
	if !path.Reached && lat.SuccessRate <= 0 {
		return StatusUnreachable
	}
	if path.TimeoutHops > 0 && path.TimeoutHops >= path.HopCount/2 && path.HopCount >= 4 {
		return StatusDegraded
	}
	if path.HopCount >= 22 {
		return StatusFair
	}
	return StatusUnknown
}

func mergeStatus(a, b Status) Status {
	rank := map[Status]int{
		StatusExcellent:   1,
		StatusGood:        2,
		StatusFair:        3,
		StatusPoor:        4,
		StatusDegraded:    5,
		StatusUnreachable: 6,
		StatusUnknown:     0,
	}
	if rank[b] == 0 {
		return a
	}
	if rank[a] == 0 {
		return b
	}
	if rank[b] > rank[a] {
		return b
	}
	return a
}

func pathInsights(p *traceroute.Result) []string {
	out := make([]string, 0, 6)
	if p.Error != "" && p.HopCount == 0 {
		out = append(out, fmt.Sprintf("Yol izleme başarısız (%s): %s", p.Tool, p.Error))
		return out
	}

	out = append(out, fmt.Sprintf(
		"Yol: %d hop · yerel/CGNAT: %d · genel: %d · zaman aşımı: %d · araç: %s",
		p.HopCount, p.LocalHops, p.PublicHops, p.TimeoutHops, p.Tool,
	))

	if p.LocalHops > 0 {
		prefix := leadingLocalCount(p)
		if prefix > 0 {
			out = append(out, fmt.Sprintf(
				"İlk %d hop yerel düğüm (LAN / özel IP / CGNAT) — ev/ofis veya operatör NAT üzerinden çıkılıyor",
				prefix,
			))
		} else {
			out = append(out, fmt.Sprintf("%d hop yerel aralıkta (RFC1918 / link-local / CGNAT)", p.LocalHops))
		}
	} else if p.HopCount > 0 {
		out = append(out, "Yerel (private) hop görülmedi — doğrudan genel ağ veya ICMP gizlenmiş olabilir")
	}

	if p.HopCount >= 20 {
		out = append(out, "Yüksek hop sayısı (≥20): uzun veya dolambaçlı rota; gecikme artabilir")
	} else if p.HopCount > 0 && p.HopCount <= 6 && p.Reached {
		out = append(out, "Kısa rota (≤6 hop): hedefe yakın veya aynı bölge/ağ")
	}

	if p.TimeoutHops > 0 {
		out = append(out, fmt.Sprintf(
			"%d hop yanıt vermedi (*): ara yönlendiriciler ICMP’yi filtreliyor olabilir (sık görülen, tek başına hata değil)",
			p.TimeoutHops,
		))
	}

	if !p.Reached {
		out = append(out, "Traceroute hedefe son hop yanıtı alamadı (firewall/ICMP engeli veya süre aşımı)")
	} else if p.ResolvedHint != "" {
		out = append(out, "Hedef çözümlendi: "+p.ResolvedHint)
	}

	if jump := firstPublicRTTJump(p); jump != "" {
		out = append(out, jump)
	}

	if w := worstHop(p); w != nil && w.AvgMs != nil {
		out = append(out, fmt.Sprintf("En yüksek gecikme: hop %d (%s, %.1f ms)", w.TTL, hopLabel(*w), *w.AvgMs))
	}

	return out
}

func hopLabel(h traceroute.Hop) string {
	if h.IP == "" {
		return "?"
	}
	return h.IP
}

// latencyInsights describes the ping (HTTP TTFB) measurement on its own
// terms — it intentionally uses the ping-only quality, not the merged
// ping+trace status, so the "Ping:" section never contradicts itself.
func latencyInsights(lat ping.Stats) []string {
	out := make([]string, 0, 6)
	if lat.AvgMs == nil {
		out = append(out, "HTTP TTFB ölçümü başarısız — hedef yanıt vermedi veya zaman aşımı")
		return out
	}
	out = append(out, fmt.Sprintf(
		"HTTP TTFB: ort %.1f ms · başarı %d%% · kalite %s",
		*lat.AvgMs, int(lat.SuccessRate*100), pingQualityLabelTR(lat.Quality),
	))
	if lat.P50Ms != nil && lat.P95Ms != nil {
		out = append(out, fmt.Sprintf("Dağılım: p50 %.1f ms · p95 %.1f ms · min %s · maks %s",
			*lat.P50Ms, *lat.P95Ms, ping.FormatMs(lat.MinMs), ping.FormatMs(lat.MaxMs)))
	}
	if lat.AvgDNSMs != nil || lat.AvgTCPMs != nil || lat.AvgTLSMs != nil {
		out = append(out, fmt.Sprintf("Bağlantı kırılımı: DNS %s · TCP %s · TLS %s",
			ping.FormatMs(lat.AvgDNSMs), ping.FormatMs(lat.AvgTCPMs), ping.FormatMs(lat.AvgTLSMs)))
	}
	if lat.JitterMs != nil && *lat.JitterMs >= 40 {
		out = append(out, fmt.Sprintf("Yüksek jitter (%.1f ms): kararsız yol veya yük değişkenliği", *lat.JitterMs))
	} else if lat.JitterMs != nil && *lat.JitterMs < 10 && lat.SuccessRate >= 0.9 {
		out = append(out, "Düşük jitter — gecikme görece stabil")
	}
	if lat.SuccessRate > 0 && lat.SuccessRate < 0.9 {
		out = append(out, "Kısmi örnek kaybı — paket/istek kaybı veya kısa kesintiler olabilir")
	}
	return out
}

// leadingLocalCount counts hops in the local/CGNAT prefix at the start of the
// path. A timeout ends the prefix (we can't classify it), it does not simply
// get skipped — otherwise "local → * → local" would over-count as if the
// timeout hop were local too.
func leadingLocalCount(p *traceroute.Result) int {
	n := 0
	for _, h := range p.Hops {
		if h.Timeout {
			break
		}
		if h.Local {
			n++
			continue
		}
		break
	}
	return n
}

// worstHop returns the hop with the highest average RTT (ignoring timeouts).
func worstHop(p *traceroute.Result) *traceroute.Hop {
	var worst *traceroute.Hop
	for i := range p.Hops {
		h := &p.Hops[i]
		if h.Timeout || h.AvgMs == nil {
			continue
		}
		if worst == nil || *h.AvgMs > *worst.AvgMs {
			worst = h
		}
	}
	return worst
}

func firstPublicRTTJump(p *traceroute.Result) string {
	var prev *float64
	for _, h := range p.Hops {
		if h.Timeout || h.AvgMs == nil {
			continue
		}
		if prev != nil && !h.Local && *h.AvgMs > *prev+40 {
			return fmt.Sprintf(
				"Hop %d civarında RTT sıçraması (~%.0f→%.0f ms): muhtemel şehirlerarası/uluslararası çıkış",
				h.TTL, *prev, *h.AvgMs,
			)
		}
		v := *h.AvgMs
		prev = &v
	}
	return ""
}

// pingSummaryTR builds a compact one-line ping (HTTP TTFB) summary from the
// ping-only quality — independent of any traceroute/path result — so the
// "Ping:" section never mixes in path-derived status.
func pingSummaryTR(lat ping.Stats) string {
	if lat.AvgMs == nil {
		return "yanıt yok"
	}
	return fmt.Sprintf("%s · %.1f ms · başarı %d%%", pingQualityLabelTR(lat.Quality), *lat.AvgMs, int(lat.SuccessRate*100))
}

func pingQualityLabelTR(q string) string {
	switch q {
	case "excellent":
		return "mükemmel"
	case "good":
		return "iyi"
	case "fair":
		return "orta"
	case "poor":
		return "zayıf"
	case "degraded":
		return "kararsız"
	case "fail":
		return "erişilemez"
	default:
		return "bilinmiyor"
	}
}

func pathSummaryTR(p *traceroute.Result) string {
	if p.Error != "" && p.HopCount == 0 {
		return "yol izlenemedi"
	}
	reach := "ulaşılmadı"
	if p.Reached {
		reach = "ulaşıldı"
	}
	return fmt.Sprintf("%d hop · %d yerel · %s", p.HopCount, p.LocalHops, reach)
}

func buildSummary(r Report) string {
	parts := []string{r.StatusLabel}
	if r.Latency.AvgMs != nil {
		parts = append(parts, fmt.Sprintf("%.0fms", *r.Latency.AvgMs))
	}
	if r.PathSummary != "" {
		parts = append(parts, r.PathSummary)
	}
	return strings.Join(parts, " · ")
}

func statusLabelTR(s Status) string {
	switch s {
	case StatusExcellent:
		return "mükemmel"
	case StatusGood:
		return "iyi"
	case StatusFair:
		return "orta"
	case StatusPoor:
		return "zayıf"
	case StatusDegraded:
		return "bozuk/kararsız"
	case StatusUnreachable:
		return "erişilemez"
	default:
		return "bilinmiyor"
	}
}

// StatusShort for table column.
func StatusShort(s Status) string {
	switch s {
	case StatusExcellent:
		return "excellent"
	case StatusGood:
		return "good"
	case StatusFair:
		return "fair"
	case StatusPoor:
		return "poor"
	case StatusDegraded:
		return "degraded"
	case StatusUnreachable:
		return "fail"
	default:
		return "—"
	}
}
