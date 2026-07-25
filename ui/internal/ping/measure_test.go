package ping_test

import (
	"context"
	"net"
	"pinglab/ui/internal/ping"
	"strconv"
	"testing"
)

func TestMeasureHTTPSmoke(t *testing.T) {
	st := ping.MeasureHTTP(context.Background(), "https://www.cloudflare.com", 2)
	if st.Samples != 2 {
		t.Fatalf("samples=%d", st.Samples)
	}
	if st.SuccessRate <= 0 {
		t.Fatalf("expected some success, got %.2f", st.SuccessRate)
	}
	t.Logf("avg=%s quality=%s success=%.2f", ping.FormatMs(st.AvgMs), st.Quality, st.SuccessRate)
}

func TestParseProbeHost(t *testing.T) {
	tests := []struct {
		in   string
		host string
		ip   bool
	}{
		{"8.8.8.8", "8.8.8.8", true},
		{"https://8.8.8.8", "8.8.8.8", true},
		{"5.11.11.5", "5.11.11.5", true},
		{"dns.google", "dns.google", false},
		{"https://store.steampowered.com/path", "store.steampowered.com", false},
	}
	for _, tc := range tests {
		host, isIP := ping.ParseProbeHostForTest(tc.in)
		if host != tc.host || isIP != tc.ip {
			t.Fatalf("%q => host=%q ip=%v want host=%q ip=%v", tc.in, host, isIP, tc.host, tc.ip)
		}
	}
}

func TestMeasureHostBareIPUsesTCP(t *testing.T) {
	ln, err := net.Listen("tcp", "127.0.0.1:0")
	if err != nil {
		t.Fatal(err)
	}
	defer ln.Close()

	go func() {
		for {
			conn, err := ln.Accept()
			if err != nil {
				return
			}
			_ = conn.Close()
		}
	}()

	host, portStr, err := net.SplitHostPort(ln.Addr().String())
	if err != nil {
		t.Fatal(err)
	}
	port, err := strconv.Atoi(portStr)
	if err != nil {
		t.Fatal(err)
	}

	st := ping.MeasureTCPPortForTest(context.Background(), host, port, 2)
	if st.Metric != ping.MetricTCPConnect {
		t.Fatalf("metric=%q", st.Metric)
	}
	if st.SuccessRate < 0.5 {
		t.Fatalf("success=%.2f", st.SuccessRate)
	}
	if st.AvgMs == nil {
		t.Fatal("expected avg latency")
	}
}
