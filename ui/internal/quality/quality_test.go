package quality

import (
	"bytes"
	"strings"
	"testing"
	"time"
)

func TestDefaultTargets(t *testing.T) {
	targets := DefaultTargets()
	if len(targets) == 0 {
		t.Fatal("expected at least 1 default target")
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
