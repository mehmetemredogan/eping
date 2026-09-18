package quality

import (
	"fmt"
	"math"
)

type Evaluation struct {
	Score             int               `json:"score"`
	Grade             string            `json:"grade"`
	Status            string            `json:"status"`
	Summary           string            `json:"summary"`
	AvgDNSMs          *float64          `json:"avg_dns_ms,omitempty"`
	AvgTCPMs          *float64          `json:"avg_tcp_ms,omitempty"`
	AvgTLSMs          *float64          `json:"avg_tls_ms,omitempty"`
	AvgTTFBMs         *float64          `json:"avg_ttfb_ms,omitempty"`
	AvgLatencyMs      *float64          `json:"avg_latency_ms,omitempty"`
	PacketLossPercent float64           `json:"packet_loss_percent"`
	TargetsTotal      int               `json:"targets_total"`
	TargetsSuccess    int               `json:"targets_success"`
	TargetsFailed     int               `json:"targets_failed"`
	Insights          map[string]string `json:"insights"`
}

func Evaluate(results []Result) Evaluation {
	total := len(results)
	if total == 0 {
		return Evaluation{
			Score:   0,
			Grade:   "F",
			Status:  "critical",
			Summary: "Hedef bulunamadı",
		}
	}

	var (
		successCount int
		dnsSum       float64
		dnsCount     int
		tcpSum       float64
		tcpCount     int
		tlsSum       float64
		tlsCount     int
		ttfbSum      float64
		ttfbCount    int
		totalSum     float64
	)

	for _, r := range results {
		if r.OK {
			successCount++
		}
		totalSum += r.TotalMs
		if r.DNSMs != nil && *r.DNSMs > 0 {
			dnsSum += *r.DNSMs
			dnsCount++
		}
		if r.TCPMs != nil && *r.TCPMs > 0 {
			tcpSum += *r.TCPMs
			tcpCount++
		}
		if r.TLSMs != nil && *r.TLSMs > 0 {
			tlsSum += *r.TLSMs
			tlsCount++
		}
		if r.TTFBMs != nil && *r.TTFBMs > 0 {
			ttfbSum += *r.TTFBMs
			ttfbCount++
		}
	}

	failedCount := total - successCount
	lossPercent := round((float64(failedCount) / float64(total)) * 100.0)

	var avgDNS, avgTCP, avgTLS, avgTTFB, avgLatency *float64
	if dnsCount > 0 {
		val := round(dnsSum / float64(dnsCount))
		avgDNS = &val
	}
	if tcpCount > 0 {
		val := round(tcpSum / float64(tcpCount))
		avgTCP = &val
	}
	if tlsCount > 0 {
		val := round(tlsSum / float64(tlsCount))
		avgTLS = &val
	}
	if ttfbCount > 0 {
		val := round(ttfbSum / float64(ttfbCount))
		avgTTFB = &val
	}
	avgLatVal := round(totalSum / float64(total))
	avgLatency = &avgLatVal

	// Calculate Score (0 - 100)
	score := 100.0

	// 1. Packet / reachability penalty (loss of 10% drops 15 pts)
	score -= lossPercent * 1.5

	// 2. TTFB penalty
	if avgTTFB != nil {
		ttfb := *avgTTFB
		if ttfb > 60.0 && ttfb <= 150.0 {
			score -= (ttfb - 60.0) * 0.15
		} else if ttfb > 150.0 && ttfb <= 300.0 {
			score -= 13.5 + (ttfb-150.0)*0.20
		} else if ttfb > 300.0 {
			score -= 43.5 + math.Min(35.0, (ttfb-300.0)*0.10)
		}
	}

	// 3. DNS latency penalty
	if avgDNS != nil && *avgDNS > 40.0 {
		score -= math.Min(15.0, (*avgDNS-40.0)*0.20)
	}

	// 4. TCP latency penalty
	if avgTCP != nil && *avgTCP > 60.0 {
		score -= math.Min(15.0, (*avgTCP-60.0)*0.15)
	}

	finalScore := int(math.Round(score))
	if finalScore < 0 {
		finalScore = 0
	}
	if finalScore > 100 {
		finalScore = 100
	}

	// Grade & Status
	var grade string
	var status string
	switch {
	case finalScore >= 90:
		grade = "A+"
		status = "excellent"
	case finalScore >= 80:
		grade = "A"
		status = "excellent"
	case finalScore >= 70:
		grade = "B"
		status = "good"
	case finalScore >= 55:
		grade = "C"
		status = "fair"
	case finalScore >= 40:
		grade = "D"
		status = "poor"
	default:
		grade = "F"
		status = "critical"
	}

	// Insights & Summary
	insights := make(map[string]string)
	if avgDNS != nil {
		if *avgDNS < 25 {
			insights["dns"] = "Ultra Hızlı DNS"
		} else if *avgDNS < 60 {
			insights["dns"] = "Normal DNS"
		} else {
			insights["dns"] = "Yüksek DNS Gecikmesi"
		}
	}
	if avgTTFB != nil {
		if *avgTTFB < 100 {
			insights["web_response"] = "Hızlı Web Yanıtı"
		} else if *avgTTFB < 250 {
			insights["web_response"] = "Standart Yanıt"
		} else {
			insights["web_response"] = "Yavaş Sunucu Yanıtı"
		}
	}
	if lossPercent == 0 {
		insights["stability"] = "Kesintisiz %100 Erişim"
	} else {
		insights["stability"] = fmt.Sprintf("%%%0.1f Kayıp", lossPercent)
	}

	var summary string
	switch status {
	case "excellent":
		summary = "Mükemmel web ve servis erişim kalitesi, düşük gecikme."
	case "good":
		summary = "İyi bağlantı kalitesi, günlük kullanım ve web erişimi sorunsuz."
	case "fair":
		summary = "Ortalama bağlantı kalitesi, bazı web hedeflerinde hafif gecikmeler mevcut."
	case "poor":
		summary = "Düşük bağlantı kalitesi, yüksek web erişim gecikmesi tespit edildi."
	default:
		summary = "Kritik ağ sorunu veya yüksek paket kaybı/erişim engeli."
	}

	return Evaluation{
		Score:             finalScore,
		Grade:             grade,
		Status:            status,
		Summary:           summary,
		AvgDNSMs:          avgDNS,
		AvgTCPMs:          avgTCP,
		AvgTLSMs:          avgTLS,
		AvgTTFBMs:         avgTTFB,
		AvgLatencyMs:      avgLatency,
		PacketLossPercent: lossPercent,
		TargetsTotal:      total,
		TargetsSuccess:    successCount,
		TargetsFailed:     failedCount,
		Insights:          insights,
	}
}
