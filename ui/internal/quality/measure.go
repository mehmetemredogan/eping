package quality

import (
	"context"
	"crypto/tls"
	"fmt"
	"io"
	"net/http"
	"net/http/httptrace"
	"sync"
	"time"
)

type Result struct {
	Name       string   `json:"name"`
	URL        string   `json:"url"`
	Domain     string   `json:"domain"`
	Category   string   `json:"category"`
	DNSMs      *float64 `json:"dns_ms,omitempty"`
	TCPMs      *float64 `json:"tcp_ms,omitempty"`
	TLSMs      *float64 `json:"tls_ms,omitempty"`
	TTFBMs     *float64 `json:"ttfb_ms,omitempty"`
	TotalMs    float64  `json:"total_ms"`
	StatusCode int      `json:"status_code"`
	OK         bool     `json:"ok"`
	Error      string   `json:"error,omitempty"`
}

type MeasureOptions struct {
	Timeout     time.Duration
	Concurrency int
}

func MeasureTarget(ctx context.Context, target Target, timeout time.Duration) Result {
	if timeout <= 0 {
		timeout = 6 * time.Second
	}

	reqCtx, cancel := context.WithTimeout(ctx, timeout)
	defer cancel()

	var (
		dnsStart, dnsDone         time.Time
		connectStart, connectDone time.Time
		tlsStart, tlsDone         time.Time
		gotConn, gotFirstByte     time.Time
	)

	trace := &httptrace.ClientTrace{
		DNSStart: func(i httptrace.DNSStartInfo) {
			dnsStart = time.Now()
		},
		DNSDone: func(i httptrace.DNSDoneInfo) {
			dnsDone = time.Now()
		},
		ConnectStart: func(network, addr string) {
			if connectStart.IsZero() {
				connectStart = time.Now()
			}
		},
		ConnectDone: func(network, addr string, err error) {
			if connectDone.IsZero() {
				connectDone = time.Now()
			}
		},
		TLSHandshakeStart: func() {
			tlsStart = time.Now()
		},
		TLSHandshakeDone: func(cs tls.ConnectionState, err error) {
			tlsDone = time.Now()
		},
		GotConn: func(ci httptrace.GotConnInfo) {
			gotConn = time.Now()
		},
		GotFirstResponseByte: func() {
			gotFirstByte = time.Now()
		},
	}

	req, err := http.NewRequestWithContext(httptrace.WithClientTrace(reqCtx, trace), "GET", target.URL, nil)
	if err != nil {
		return Result{
			Name:     target.Name,
			URL:      target.URL,
			Domain:   target.Domain,
			Category: target.Category,
			OK:       false,
			Error:    err.Error(),
		}
	}

	const (
		chromeUserAgent = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36"
		chromeAccept    = "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7"
		chromeLang      = "en-US,en;q=0.9,tr;q=0.8"
		chromeSecChUa   = `"Not(A:Brand";v="99", "Google Chrome";v="133", "Chromium";v="133"`
	)

	req.Header.Set("User-Agent", chromeUserAgent)
	req.Header.Set("Accept", chromeAccept)
	req.Header.Set("Accept-Language", chromeLang)
	req.Header.Set("Sec-Ch-Ua", chromeSecChUa)
	req.Header.Set("Sec-Ch-Ua-Mobile", "?0")
	req.Header.Set("Sec-Ch-Ua-Platform", `"Windows"`)

	transport := &http.Transport{
		DisableKeepAlives: true, // fresh connection to measure DNS + TCP + TLS accurately
		TLSClientConfig:   &tls.Config{InsecureSkipVerify: false},
	}

	client := &http.Client{
		Transport: transport,
		CheckRedirect: func(req *http.Request, via []*http.Request) error {
			if len(via) >= 10 {
				return fmt.Errorf("stopped after 10 redirects: %w", http.ErrUseLastResponse)
			}
			// Maintain Chrome headers across 3xx redirects
			req.Header.Set("User-Agent", chromeUserAgent)
			req.Header.Set("Accept", chromeAccept)
			req.Header.Set("Accept-Language", chromeLang)
			req.Header.Set("Sec-Ch-Ua", chromeSecChUa)
			req.Header.Set("Sec-Ch-Ua-Mobile", "?0")
			req.Header.Set("Sec-Ch-Ua-Platform", `"Windows"`)
			return nil
		},
	}

	start := time.Now()
	resp, err := client.Do(req)
	totalDuration := time.Since(start)

	res := Result{
		Name:     target.Name,
		URL:      target.URL,
		Domain:   target.Domain,
		Category: target.Category,
		TotalMs:  round(float64(totalDuration.Microseconds()) / 1000.0),
	}

	if !dnsStart.IsZero() && !dnsDone.IsZero() && dnsDone.After(dnsStart) {
		val := round(float64(dnsDone.Sub(dnsStart).Microseconds()) / 1000.0)
		res.DNSMs = &val
	}
	if !connectStart.IsZero() && !connectDone.IsZero() && connectDone.After(connectStart) {
		val := round(float64(connectDone.Sub(connectStart).Microseconds()) / 1000.0)
		res.TCPMs = &val
	}
	if !tlsStart.IsZero() && !tlsDone.IsZero() && tlsDone.After(tlsStart) {
		val := round(float64(tlsDone.Sub(tlsStart).Microseconds()) / 1000.0)
		res.TLSMs = &val
	}
	if !gotConn.IsZero() && !gotFirstByte.IsZero() && gotFirstByte.After(gotConn) {
		val := round(float64(gotFirstByte.Sub(gotConn).Microseconds()) / 1000.0)
		res.TTFBMs = &val
	}

	if err != nil {
		res.OK = false
		res.Error = err.Error()
		return res
	}
	defer resp.Body.Close()

	// Read small portion of body to complete HTTP transaction cleanly
	_, _ = io.CopyN(io.Discard, resp.Body, 8192)

	res.StatusCode = resp.StatusCode
	// 4xx (client errors) and 5xx (server errors) are failures.
	// Only 2xx (and 3xx if any redirect terminates without error) are successful.
	if resp.StatusCode >= 400 {
		res.OK = false
		res.Error = fmt.Sprintf("HTTP %d %s", resp.StatusCode, http.StatusText(resp.StatusCode))
	} else if resp.StatusCode >= 200 && resp.StatusCode < 400 {
		res.OK = true
	} else {
		res.OK = false
		res.Error = fmt.Sprintf("HTTP %d", resp.StatusCode)
	}

	return res
}

// MeasureAll probes all targets concurrently with bounded workers.
func MeasureAll(ctx context.Context, targets []Target, opts MeasureOptions) []Result {
	if opts.Concurrency <= 0 {
		opts.Concurrency = 4
	}
	if opts.Timeout <= 0 {
		opts.Timeout = 6 * time.Second
	}

	results := make([]Result, len(targets))
	sem := make(chan struct{}, opts.Concurrency)
	var wg sync.WaitGroup

	for i, t := range targets {
		wg.Add(1)
		go func(idx int, tgt Target) {
			defer wg.Done()
			sem <- struct{}{}
			defer func() { <-sem }()

			results[idx] = MeasureTarget(ctx, tgt, opts.Timeout)
		}(i, t)
	}

	wg.Wait()
	return results
}

func round(val float64) float64 {
	return float64(int(val*100+0.5)) / 100
}
