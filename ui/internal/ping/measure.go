package ping

import (
	"context"
	"crypto/tls"
	"fmt"
	"io"
	"math"
	"net"
	"net/http"
	"net/http/httptrace"
	"sort"
	"strings"
	"time"
)

type Sample struct {
	OK     bool    `json:"ok"`
	Ms     float64 `json:"ms,omitempty"`
	DNSMs  float64 `json:"dns_ms,omitempty"`
	TCPMs  float64 `json:"tcp_ms,omitempty"`
	TLSMs  float64 `json:"tls_ms,omitempty"`
	TTFBMs float64 `json:"ttfb_ms,omitempty"`
	Status int     `json:"status,omitempty"`
	Error  string  `json:"error,omitempty"`
	Warmup bool    `json:"warmup,omitempty"`
}

type Stats struct {
	AvgMs       *float64
	MinMs       *float64
	MaxMs       *float64
	P50Ms       *float64
	P95Ms       *float64
	JitterMs    *float64
	AvgDNSMs    *float64
	AvgTCPMs    *float64
	AvgTLSMs    *float64
	Samples     int
	SuccessRate float64
	Quality     string
	Metric      string
	Raw         []Sample
}

const (
	MetricHTTPTTFB    = "http_ttfb"
	MetricTCPConnect  = "tcp_connect"
)

// MeasureHost picks a probe strategy from the host shape. Bare IP addresses
// (typical for DNS resolvers) use TCP connect timing because HTTPS to the
// literal IP often fails TLS even when the network path is healthy.
func MeasureHost(ctx context.Context, rawHost string, samples int) Stats {
	host, isIP := parseProbeHost(rawHost)
	if isIP {
		return measureTCPHost(ctx, host, samples)
	}
	st := MeasureHTTP(ctx, rawHost, samples)
	if st.Metric == "" {
		st.Metric = MetricHTTPTTFB
	}
	return st
}

func parseProbeHost(raw string) (host string, isIP bool) {
	h := strings.TrimSpace(raw)
	h = strings.TrimPrefix(h, "https://")
	h = strings.TrimPrefix(h, "http://")
	if i := strings.IndexAny(h, "/?#"); i >= 0 {
		h = h[:i]
	}
	if parsed, port, err := net.SplitHostPort(h); err == nil {
		h = parsed
		_ = port
	}
	return h, net.ParseIP(h) != nil
}

// ParseProbeHostForTest exposes host parsing for unit tests.
func ParseProbeHostForTest(raw string) (host string, isIP bool) {
	return parseProbeHost(raw)
}

func measureTCPHost(ctx context.Context, host string, samples int) Stats {
	ports := []int{443, 53, 80}
	var last Stats
	for _, port := range ports {
		st := measureTCPPort(ctx, host, port, samples)
		st.Metric = MetricTCPConnect
		last = st
		if st.SuccessRate >= 0.5 {
			return st
		}
	}
	return last
}

func measureTCPPort(ctx context.Context, host string, port int, samples int) Stats {
	if samples < 1 {
		samples = 4
	}
	all := make([]Sample, 0, samples+1)
	all = append(all, probeTCP(ctx, host, port, true))
	for i := 0; i < samples; i++ {
		all = append(all, probeTCP(ctx, host, port, false))
	}
	return summarize(all)
}

// MeasureTCPPortForTest exposes TCP measurement on a specific port for tests.
func MeasureTCPPortForTest(ctx context.Context, host string, port int, samples int) Stats {
	st := measureTCPPort(ctx, host, port, samples)
	st.Metric = MetricTCPConnect
	return st
}

func probeTCP(ctx context.Context, host string, port int, warmup bool) Sample {
	addr := net.JoinHostPort(host, fmt.Sprintf("%d", port))
	start := time.Now()
	dialer := &net.Dialer{Timeout: 5 * time.Second}
	conn, err := dialer.DialContext(ctx, "tcp", addr)
	if err != nil {
		return Sample{OK: false, Error: err.Error(), Warmup: warmup}
	}
	_ = conn.Close()
	ms := round1(msSince(start))
	return Sample{
		OK:     true,
		Ms:     ms,
		TCPMs:  ms,
		Warmup: warmup,
	}
}

// MeasureHTTP runs one warm-up (discarded) then N timed probes.
// Latency is TTFB: time until the first response byte (Waiting equivalent).
func MeasureHTTP(ctx context.Context, rawURL string, samples int) Stats {
	if samples < 1 {
		samples = 4
	}
	url := normalizeURL(rawURL)

	all := make([]Sample, 0, samples+1)
	// Warm-up: absorbs DNS-cache/OS-level first-hit effects; excluded from stats.
	all = append(all, probe(ctx, url, true))
	for i := 0; i < samples; i++ {
		all = append(all, probe(ctx, url, false))
	}
	return summarize(all)
}

// probe issues one fully independent HTTP request (fresh connection, no
// keep-alive reuse) so DNS/TCP/TLS/TTFB timings are comparable across runs.
func probe(ctx context.Context, url string, warmup bool) Sample {
	client := &http.Client{
		Timeout: 8 * time.Second,
		Transport: &http.Transport{
			Proxy:                 http.ProxyFromEnvironment,
			DialContext:           (&net.Dialer{Timeout: 5 * time.Second}).DialContext,
			TLSHandshakeTimeout:   5 * time.Second,
			ResponseHeaderTimeout: 5 * time.Second,
			ForceAttemptHTTP2:     true,
			// Fresh connection per probe so every sample includes DNS+TCP+TLS,
			// making samples comparable and giving a real timing breakdown.
			DisableKeepAlives: true,
		},
		CheckRedirect: func(req *http.Request, via []*http.Request) error {
			if len(via) >= 2 {
				return http.ErrUseLastResponse
			}
			return nil
		},
	}
	defer client.CloseIdleConnections()

	var (
		dnsStart, connectStart, tlsStart time.Time
		gotConn, firstByte               time.Time
		dnsMs, tcpMs, tlsMs, ttfbMs      float64
	)

	trace := &httptrace.ClientTrace{
		DNSStart: func(httptrace.DNSStartInfo) { dnsStart = time.Now() },
		DNSDone: func(httptrace.DNSDoneInfo) {
			if !dnsStart.IsZero() {
				dnsMs = msSince(dnsStart)
			}
		},
		ConnectStart: func(_, _ string) {
			connectStart = time.Now()
		},
		ConnectDone: func(_, _ string, err error) {
			if err == nil && !connectStart.IsZero() {
				tcpMs = msSince(connectStart)
			}
		},
		TLSHandshakeStart: func() { tlsStart = time.Now() },
		TLSHandshakeDone: func(tls.ConnectionState, error) {
			if !tlsStart.IsZero() {
				tlsMs = msSince(tlsStart)
			}
		},
		GotConn: func(httptrace.GotConnInfo) {
			gotConn = time.Now()
		},
		GotFirstResponseByte: func() {
			firstByte = time.Now()
			if !gotConn.IsZero() {
				ttfbMs = float64(firstByte.Sub(gotConn).Microseconds()) / 1000.0
			} else if !connectStart.IsZero() {
				ttfbMs = msSince(connectStart)
			}
		},
	}

	req, err := http.NewRequestWithContext(httptrace.WithClientTrace(ctx, trace), http.MethodGet, url, nil)
	if err != nil {
		return Sample{OK: false, Error: err.Error(), Warmup: warmup}
	}
	req.Header.Set("User-Agent", "ePing/1.0")
	req.Header.Set("Cache-Control", "no-cache")
	req.Header.Set("Pragma", "no-cache")

	start := time.Now()
	res, err := client.Do(req)
	if err != nil {
		return Sample{OK: false, Error: err.Error(), Warmup: warmup, DNSMs: round1(dnsMs), TCPMs: round1(tcpMs)}
	}
	defer res.Body.Close()
	_, _ = io.Copy(io.Discard, io.LimitReader(res.Body, 64<<10))

	ms := ttfbMs
	if ms <= 0 {
		// Fallback: total until headers if trace missed first byte.
		ms = msSince(start)
	}

	return Sample{
		OK:     res.StatusCode > 0 && res.StatusCode < 600,
		Ms:     round1(ms),
		DNSMs:  round1(dnsMs),
		TCPMs:  round1(tcpMs),
		TLSMs:  round1(tlsMs),
		TTFBMs: round1(ttfbMs),
		Status: res.StatusCode,
		Warmup: warmup,
	}
}

func msSince(t time.Time) float64 {
	return float64(time.Since(t).Microseconds()) / 1000.0
}

func summarize(all []Sample) Stats {
	timed := make([]Sample, 0, len(all))
	for _, s := range all {
		if s.Warmup {
			continue
		}
		timed = append(timed, s)
	}
	okVals := make([]float64, 0, len(timed))
	dnsVals := make([]float64, 0, len(timed))
	tcpVals := make([]float64, 0, len(timed))
	tlsVals := make([]float64, 0, len(timed))
	for _, s := range timed {
		// A successful probe can legitimately time in at 0.0ms (sub-millisecond,
		// e.g. localhost/LAN); only the OK flag determines success, not Ms > 0.
		if s.OK {
			okVals = append(okVals, s.Ms)
		}
		if s.DNSMs > 0 {
			dnsVals = append(dnsVals, s.DNSMs)
		}
		if s.TCPMs > 0 {
			tcpVals = append(tcpVals, s.TCPMs)
		}
		if s.TLSMs > 0 {
			tlsVals = append(tlsVals, s.TLSMs)
		}
	}
	st := Stats{
		Samples:     len(timed),
		SuccessRate: 0,
		Quality:     "fail",
		Metric:      MetricHTTPTTFB,
		Raw:         all,
	}
	if len(timed) == 0 {
		return st
	}
	st.SuccessRate = float64(len(okVals)) / float64(len(timed))
	if v := meanOrNil(dnsVals); v != nil {
		st.AvgDNSMs = v
	}
	if v := meanOrNil(tcpVals); v != nil {
		st.AvgTCPMs = v
	}
	if v := meanOrNil(tlsVals); v != nil {
		st.AvgTLSMs = v
	}
	if len(okVals) == 0 {
		return st
	}
	sort.Float64s(okVals)
	minV := okVals[0]
	maxV := okVals[len(okVals)-1]
	sum := 0.0
	for _, v := range okVals {
		sum += v
	}
	avg := sum / float64(len(okVals))
	var jit float64
	if len(okVals) > 1 {
		diff := 0.0
		for i := 1; i < len(okVals); i++ {
			diff += math.Abs(okVals[i] - okVals[i-1])
		}
		jit = diff / float64(len(okVals)-1)
	}
	st.AvgMs = ptr(round1(avg))
	st.MinMs = ptr(round1(minV))
	st.MaxMs = ptr(round1(maxV))
	st.JitterMs = ptr(round1(jit))
	st.P50Ms = ptr(round1(percentile(okVals, 50)))
	st.P95Ms = ptr(round1(percentile(okVals, 95)))
	st.Quality = quality(*st.AvgMs, st.SuccessRate)
	return st
}

// percentile expects vals sorted ascending.
func percentile(vals []float64, p float64) float64 {
	if len(vals) == 0 {
		return 0
	}
	if len(vals) == 1 {
		return vals[0]
	}
	rank := (p / 100.0) * float64(len(vals)-1)
	lo := int(math.Floor(rank))
	hi := int(math.Ceil(rank))
	if lo == hi {
		return vals[lo]
	}
	frac := rank - float64(lo)
	return vals[lo] + (vals[hi]-vals[lo])*frac
}

func meanOrNil(vals []float64) *float64 {
	if len(vals) == 0 {
		return nil
	}
	sum := 0.0
	for _, v := range vals {
		sum += v
	}
	return ptr(round1(sum / float64(len(vals))))
}

func quality(avg, success float64) string {
	if success < 0.5 {
		return "fail"
	}
	if success < 0.75 {
		return "degraded"
	}
	if avg < 40 {
		return "excellent"
	}
	if avg < 80 {
		return "good"
	}
	if avg < 180 {
		return "fair"
	}
	return "poor"
}

func normalizeURL(host string) string {
	h := strings.TrimSpace(host)
	if h == "" {
		return h
	}
	if strings.HasPrefix(h, "http://") || strings.HasPrefix(h, "https://") {
		return h
	}
	return "https://" + h
}

func ptr(v float64) *float64 { return &v }

func round1(v float64) float64 {
	return math.Round(v*10) / 10
}

func FormatMs(v *float64) string {
	if v == nil {
		return "—"
	}
	return fmt.Sprintf("%.1f ms", *v)
}
