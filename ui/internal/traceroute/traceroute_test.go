package traceroute_test

import (
	"testing"

	"pinglab/ui/internal/traceroute"
)

func TestClassifyLocal(t *testing.T) {
	cases := []struct {
		ip    string
		local bool
		kind  traceroute.HopKind
	}{
		{"127.0.0.1", true, traceroute.KindLoopback},
		{"192.168.1.1", true, traceroute.KindPrivate},
		{"10.0.0.1", true, traceroute.KindPrivate},
		{"172.16.5.1", true, traceroute.KindPrivate},
		{"100.64.1.2", true, traceroute.KindCGNAT},
		{"8.8.8.8", false, traceroute.KindPublic},
		{"169.254.10.1", true, traceroute.KindLinkLocal},
	}
	for _, c := range cases {
		kind, local := traceroute.ClassifyIP(c.ip)
		if kind != c.kind || local != c.local {
			t.Fatalf("%s: got kind=%s local=%v want kind=%s local=%v", c.ip, kind, local, c.kind, c.local)
		}
	}
}

func TestParseWindows(t *testing.T) {
	raw := `
Tracing route to example.com [93.184.216.34]
over a maximum of 30 hops:

  1    <1 ms    <1 ms    <1 ms  192.168.1.1
  2     4 ms     5 ms     4 ms  10.20.0.1
  3     *        *        *     Request timed out.
  4    12 ms    11 ms    13 ms  93.184.216.34

Trace complete.
`
	hops, hint := traceroute.ParseWindows(raw)
	if hint != "93.184.216.34" {
		t.Fatalf("hint=%q", hint)
	}
	if len(hops) != 4 {
		t.Fatalf("hops=%d", len(hops))
	}
	if !hops[0].Local || hops[0].IP != "192.168.1.1" {
		t.Fatalf("hop1=%+v", hops[0])
	}
	if !hops[2].Timeout {
		t.Fatal("hop3 should timeout")
	}
	if hops[3].Kind != traceroute.KindPublic {
		t.Fatalf("hop4 kind=%s", hops[3].Kind)
	}
}

func TestParseUnix(t *testing.T) {
	raw := `
traceroute to example.com (93.184.216.34), 30 hops max, 60 byte packets
 1  192.168.1.1  1.2 ms  1.1 ms  1.0 ms
 2  * * *
 3  93.184.216.34  12.1 ms  11.9 ms  12.0 ms
`
	hops, hint := traceroute.ParseUnix(raw)
	if hint != "93.184.216.34" {
		t.Fatalf("hint=%q", hint)
	}
	if len(hops) != 3 {
		t.Fatalf("hops=%d", len(hops))
	}
	if !hops[0].Local || !hops[1].Timeout || hops[2].Kind != traceroute.KindPublic {
		t.Fatalf("unexpected: %+v", hops)
	}
}

func TestParseUnixTracepath(t *testing.T) {
	// tracepath uses "N:" (colon, no space) before the hop body.
	raw := `
 1:  192.168.1.1                                           0.363ms
 1:  192.168.1.1                                           0.157ms
 2:  10.20.0.1                                              4.5ms
 3:  no reply
 4:  93.184.216.34                                         12.0ms reached
     Resume: pmtu 1500 hops 4 back 4
`
	hops, _ := traceroute.ParseUnix(raw)
	if len(hops) != 5 {
		t.Fatalf("hops=%d (%+v)", len(hops), hops)
	}
	if !hops[0].Local || hops[0].IP != "192.168.1.1" {
		t.Fatalf("hop1=%+v", hops[0])
	}
	if !hops[3].Timeout {
		t.Fatalf("hop3 (no reply) should be timeout: %+v", hops[3])
	}
	if hops[4].Kind != traceroute.KindPublic {
		t.Fatalf("hop5 kind=%s", hops[4].Kind)
	}
}

func TestParseWindowsIPv6(t *testing.T) {
	raw := `
Tracing route to example.com [2001:db8::1]
over a maximum of 30 hops:

  1    <1 ms    <1 ms    <1 ms  fe80::1
  2     4 ms     5 ms     4 ms  2001:db8::1

Trace complete.
`
	hops, _ := traceroute.ParseWindows(raw)
	if len(hops) != 2 {
		t.Fatalf("hops=%d", len(hops))
	}
	if hops[0].IP != "fe80::1" || hops[0].Kind != traceroute.KindLinkLocal {
		t.Fatalf("hop1=%+v", hops[0])
	}
	if hops[1].IP != "2001:db8::1" || hops[1].Kind != traceroute.KindPublic {
		t.Fatalf("hop2=%+v", hops[1])
	}
}

func TestReachedRequiresLastHopReply(t *testing.T) {
	raw := `
traceroute to example.com (93.184.216.34), 30 hops max, 60 byte packets
 1  192.168.1.1  1.2 ms  1.1 ms  1.0 ms
 2  93.184.216.34  12.1 ms  11.9 ms  12.0 ms
 3  * * *
`
	hops, hint := traceroute.ParseUnix(raw)
	res := traceroute.Result{Hops: hops, ResolvedHint: hint}
	res.Recompute()
	if res.Reached {
		t.Fatal("should not be reached when last hop times out, even if an earlier hop replied")
	}
}
