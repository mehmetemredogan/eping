package netinfo_test

import (
	"testing"

	"pinglab/ui/internal/netinfo"
	"pinglab/ui/internal/ping"
	"pinglab/ui/internal/traceroute"
)

func TestAnalyzeWithPath(t *testing.T) {
	avg := 55.0
	jit := 5.0
	lat := ping.Stats{
		AvgMs:       &avg,
		JitterMs:    &jit,
		SuccessRate: 1,
		Samples:     4,
		Quality:     "good",
	}
	hops, _ := traceroute.ParseWindows(`
Tracing route to x [8.8.8.8]

  1    <1 ms    <1 ms    <1 ms  192.168.1.1
  2     5 ms     5 ms     5 ms  8.8.8.8

Trace complete.
`)
	path := &traceroute.Result{Tool: "tracert", Hops: hops, Reached: true}
	for _, h := range hops {
		path.HopCount++
		if h.Timeout {
			path.TimeoutHops++
		} else if h.Local {
			path.LocalHops++
		} else if h.Kind == traceroute.KindPublic {
			path.PublicHops++
		}
	}

	r := netinfo.Analyze(lat, path)
	if r.Status != netinfo.StatusGood && r.Status != netinfo.StatusExcellent {
		t.Fatalf("status=%s", r.Status)
	}
	if len(r.Insights) < 2 {
		t.Fatalf("insights=%v", r.Insights)
	}
	if r.PathSummary == "" {
		t.Fatal("empty path summary")
	}
}
