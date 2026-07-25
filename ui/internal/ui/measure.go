package ui

import (
	"context"
	"encoding/json"
	"fmt"

	"pinglab/ui/internal/api"
	"pinglab/ui/internal/linktype"
	"pinglab/ui/internal/netinfo"
	"pinglab/ui/internal/ping"
	"pinglab/ui/internal/traceroute"
)

const maxTraceRawBytes = 64 * 1024

func measureTarget(client *api.Client, token string, t api.Target, samples int, withTrace bool) rowState {
	st := ping.MeasureHost(context.Background(), t.Host, samples)

	var path *traceroute.Result
	if withTrace {
		tr := traceroute.Trace(context.Background(), t.Host)
		path = &tr
	}
	report := netinfo.Analyze(st, path)
	conn := linktype.Detect()
	report.ConnectionType = string(conn)

	row := rowState{
		target:        t,
		avg:           ping.FormatMs(st.AvgMs),
		min:           ping.FormatMs(st.MinMs),
		max:           ping.FormatMs(st.MaxMs),
		jitter:        ping.FormatMs(st.JitterMs),
		p50:           ping.FormatMs(st.P50Ms),
		p95:           ping.FormatMs(st.P95Ms),
		dnsMs:         ping.FormatMs(st.AvgDNSMs),
		tcpMs:         ping.FormatMs(st.AvgTCPMs),
		tlsMs:         ping.FormatMs(st.AvgTLSMs),
		qual:          string(report.Status),
		status:        netinfo.StatusShort(report.Status),
		pingSummary:   report.PingSummary,
		pingInsights:  report.PingInsights,
		pathSummary:   report.PathSummary,
		traceInsights: report.TraceInsights,
	}
	if path != nil {
		row.hops = path.Hops
	}

	if token == "" {
		return row
	}

	vals := make([]float64, 0, len(st.Raw))
	received := 0
	for _, s := range st.Raw {
		if s.Warmup {
			continue
		}
		if s.OK {
			vals = append(vals, s.Ms)
			received++
		}
	}
	status := "success"
	if report.Status == netinfo.StatusUnreachable || received == 0 {
		status = "failed"
	}
	loss := 0.0
	if st.Samples > 0 {
		loss = 100.0 * float64(st.Samples-received) / float64(st.Samples)
	}
	metric := st.Metric
	if metric == "" {
		metric = ping.MetricHTTPTTFB
	}

	if err := client.StoreResult(t.ID, api.ResultPayload{
		Status:            status,
		MinLatencyMs:      st.MinMs,
		MaxLatencyMs:      st.MaxMs,
		AvgLatencyMs:      st.AvgMs,
		JitterMs:          st.JitterMs,
		PacketLossPercent: &loss,
		PacketsSent:       st.Samples,
		PacketsReceived:   received,
		Samples:           vals,
		Metric:            metric,
		ClientVersion:     "eping/1.0",
		ConnectionType:    string(conn),
		NetworkAnalysis:   reportToMap(report),
	}); err != nil {
		row.uploadErr = err.Error()
		return row
	}

	if trend, err := client.TrendForTarget(t.ID); err == nil && trend != nil {
		row.trendLabel = trendInsight(trend)
		if row.trendLabel != "" {
			row.pingInsights = append(row.pingInsights, row.trendLabel)
		}
	}

	return row
}

// trendInsight turns a target's historical trend into a short Turkish
// insight line, e.g. "Geçmişe göre: %18 daha hızlı (iyileşiyor, 32 ölçüm)".
func trendInsight(t *api.Trend) string {
	switch t.TrendKey {
	case "improving", "degrading":
		pct := 0.0
		if t.DeltaPercent != nil {
			pct = *t.DeltaPercent
		}
		dir := "daha hızlı"
		show := pct
		if pct < 0 {
			show = -pct
		} else {
			dir = "daha yavaş"
		}
		return fmt.Sprintf("Geçmişe göre: %%%.0f %s (%s, %d geçmiş ölçüm)", show, dir, t.TrendLabel, t.BaselineCount)
	case "stable":
		return fmt.Sprintf("Geçmişe göre: stabil (%s, %d geçmiş ölçüm)", t.TrendLabel, t.BaselineCount)
	default:
		return ""
	}
}

func reportToMap(r netinfo.Report) map[string]any {
	if r.Path != nil && len(r.Path.Raw) > maxTraceRawBytes {
		cp := *r.Path
		cp.Raw = cp.Raw[:maxTraceRawBytes] + "\n…[truncated]"
		r.Path = &cp
	}
	b, err := json.Marshal(r)
	if err != nil {
		return nil
	}
	var m map[string]any
	if err := json.Unmarshal(b, &m); err != nil {
		return nil
	}
	return m
}
