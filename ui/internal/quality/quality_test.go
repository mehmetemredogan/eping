package quality

import (
	"bytes"
	"context"
	"net/http"
	"net/http/httptest"
	"strings"
	"testing"
	"time"
)

func TestTargetValidation(t *testing.T) {
	targets := []Target{
		{Name: "Google", URL: "https://www.google.com", Domain: "www.google.com", Category: "search"},
		{Name: "Cloudflare", URL: "https://cloudflare.com", Domain: "cloudflare.com", Category: "cdn"},
	}
	for _, tgt := range targets {
		if tgt.Name == "" || tgt.URL == "" || tgt.Domain == "" {
			t.Errorf("invalid target definition: %+v", tgt)
		}
	}
}

func TestEvaluate_PerfectResults(t *testing.T) {
	dns := 10.0
	tcp := 20.0
	tls := 25.0
	ttfb := 40.0

	results := []Result{
		{
			Name:       "Google",
			URL:        "https://www.google.com",
			Domain:     "www.google.com",
			Category:   "search",
			DNSMs:      &dns,
			TCPMs:      &tcp,
			TLSMs:      &tls,
			TTFBMs:     &ttfb,
			TotalMs:    55.0,
			StatusCode: 200,
			OK:         true,
		},
		{
			Name:       "Cloudflare",
			URL:        "https://cloudflare.com",
			Domain:     "cloudflare.com",
			Category:   "cdn",
			DNSMs:      &dns,
			TCPMs:      &tcp,
			TLSMs:      &tls,
			TTFBMs:     &ttfb,
			TotalMs:    50.0,
			StatusCode: 200,
			OK:         true,
		},
	}

	eval := Evaluate(results)
	if eval.Score < 90 {
		t.Errorf("expected score >= 90 for fast results, got %d", eval.Score)
	}
	if eval.Grade != "A+" {
		t.Errorf("expected grade A+, got %s", eval.Grade)
	}
	if eval.Status != "excellent" {
		t.Errorf("expected status excellent, got %s", eval.Status)
	}
	if eval.PacketLossPercent != 0 {
		t.Errorf("expected 0 packet loss, got %f", eval.PacketLossPercent)
	}
}

func TestEvaluate_HighLossResults(t *testing.T) {
	results := []Result{
		{
			Name:    "Broken 1",
			URL:     "https://bad1.example",
			Domain:  "bad1.example",
			TotalMs: 5000.0,
			OK:      false,
			Error:   "timeout",
		},
		{
			Name:    "Broken 2",
			URL:     "https://bad2.example",
			Domain:  "bad2.example",
			TotalMs: 5000.0,
			OK:      false,
			Error:   "timeout",
		},
	}

	eval := Evaluate(results)
	if eval.Score > 20 {
		t.Errorf("expected low score for all failed targets, got %d", eval.Score)
	}
	if eval.Grade != "F" {
		t.Errorf("expected grade F, got %s", eval.Grade)
	}
	if eval.PacketLossPercent != 100 {
		t.Errorf("expected 100%% packet loss, got %f", eval.PacketLossPercent)
	}
}

func TestReport_FormatTerminalAndJSON(t *testing.T) {
	dns := 15.0
	tcp := 25.0
	results := []Result{
		{
			Name:       "Cloudflare",
			URL:        "https://cloudflare.com",
			Domain:     "cloudflare.com",
			Category:   "cdn",
			DNSMs:      &dns,
			TCPMs:      &tcp,
			TotalMs:    45.0,
			StatusCode: 200,
			OK:         true,
		},
	}
	eval := Evaluate(results)
	rep := Report{
		TestedAt:   time.Now(),
		Evaluation: eval,
		Results:    results,
	}

	var buf bytes.Buffer
	PrintTerminal(&buf, rep)
	output := buf.String()
	if !strings.Contains(output, "EPING NETWORK QUALITY TEST") {
		t.Errorf("terminal report missing expected header: %s", output)
	}

	var jsonBuf bytes.Buffer
	if err := PrintJSON(&jsonBuf, rep); err != nil {
		t.Fatalf("PrintJSON error: %v", err)
	}
	if !strings.Contains(jsonBuf.String(), `"score"`) {
		t.Errorf("json report missing score: %s", jsonBuf.String())
	}
}

func TestMeasureTarget_RedirectFollowAndChromeUserAgent(t *testing.T) {
	var capturedUserAgent string
	var redirectHits int

	// Final destination handler
	destServer := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		capturedUserAgent = r.Header.Get("User-Agent")
		w.WriteHeader(http.StatusOK)
		_, _ = w.Write([]byte("final destination ok"))
	}))
	defer destServer.Close()

	// Redirecting handler (302 Found)
	redirectServer := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		redirectHits++
		http.Redirect(w, r, destServer.URL, http.StatusFound)
	}))
	defer redirectServer.Close()

	target := Target{
		Name:     "Redirect Target",
		URL:      redirectServer.URL,
		Domain:   "redirect.test",
		Category: "test",
	}

	res := MeasureTarget(context.Background(), target, 3*time.Second)

	if redirectHits != 1 {
		t.Errorf("expected 1 redirect hit, got %d", redirectHits)
	}
	if !res.OK {
		t.Errorf("expected res.OK to be true after following redirect, got false (error: %s)", res.Error)
	}
	if res.StatusCode != http.StatusOK {
		t.Errorf("expected final StatusCode to be 200, got %d", res.StatusCode)
	}
	if !strings.Contains(capturedUserAgent, "Chrome/133") {
		t.Errorf("expected modern Chrome User-Agent, got: %s", capturedUserAgent)
	}
}

func TestMeasureTarget_StatusFailure(t *testing.T) {
	tests := []struct {
		name       string
		statusCode int
		shouldOK   bool
	}{
		{"Client Error 400", http.StatusBadRequest, false},
		{"Client Error 401", http.StatusUnauthorized, false},
		{"Client Error 403", http.StatusForbidden, false},
		{"Client Error 404", http.StatusNotFound, false},
		{"Server Error 500", http.StatusInternalServerError, false},
		{"Server Error 502", http.StatusBadGateway, false},
		{"Server Error 503", http.StatusServiceUnavailable, false},
		{"Success 200", http.StatusOK, true},
		{"Success 204", http.StatusNoContent, true},
	}

	for _, tc := range tests {
		t.Run(tc.name, func(t *testing.T) {
			server := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
				w.WriteHeader(tc.statusCode)
			}))
			defer server.Close()

			target := Target{
				Name:     tc.name,
				URL:      server.URL,
				Domain:   "status.test",
				Category: "test",
			}

			res := MeasureTarget(context.Background(), target, 2*time.Second)
			if res.OK != tc.shouldOK {
				t.Errorf("status %d: expected OK=%v, got OK=%v (error: %s)", tc.statusCode, tc.shouldOK, res.OK, res.Error)
			}
			if res.StatusCode != tc.statusCode {
				t.Errorf("expected StatusCode=%d, got %d", tc.statusCode, res.StatusCode)
			}
		})
	}
}
