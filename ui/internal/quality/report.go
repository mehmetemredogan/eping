package quality

import (
	"encoding/json"
	"fmt"
	"io"
	"os"
	"strings"
	"time"
)

type Report struct {
	TestedAt   time.Time  `json:"tested_at"`
	Evaluation Evaluation `json:"evaluation"`
	Results    []Result   `json:"results"`
}

func FormatTerminal(r Report) string {
	var b strings.Builder

	// Header
	b.WriteString("\n")
	b.WriteString("  \033[1;36m========================================================================\033[0m\n")
	b.WriteString("  \033[1;37m   EPING NETWORK QUALITY TEST — GERÇEK WEB ERİŞİM RAPORU\033[0m\n")
	b.WriteString("  \033[1;36m========================================================================\033[0m\n\n")

	// Grade color
	gradeColor := "\033[1;32m" // green
	if r.Evaluation.Grade == "B" {
		gradeColor = "\033[1;34m" // blue
	} else if r.Evaluation.Grade == "C" {
		gradeColor = "\033[1;33m" // yellow
	} else if r.Evaluation.Grade == "D" || r.Evaluation.Grade == "F" {
		gradeColor = "\033[1;31m" // red
	}

	b.WriteString(fmt.Sprintf("  Puan: %s%3d/100\033[0m   Derece: %s[%s]\033[0m   Durum: \033[1;37m%s\033[0m   Test Zamanı: \033[0;37m%s\033[0m\n",
		gradeColor, r.Evaluation.Score,
		gradeColor, r.Evaluation.Grade,
		strings.ToUpper(r.Evaluation.Status),
		r.TestedAt.Format("15:04:05 02/01/2006"),
	))
	b.WriteString(fmt.Sprintf("  Özet: \033[0;36m%s\033[0m\n\n", r.Evaluation.Summary))

	// Aggregates overview
	dnsStr := "—"
	if r.Evaluation.AvgDNSMs != nil {
		dnsStr = fmt.Sprintf("%0.1f ms", *r.Evaluation.AvgDNSMs)
	}
	tcpStr := "—"
	if r.Evaluation.AvgTCPMs != nil {
		tcpStr = fmt.Sprintf("%0.1f ms", *r.Evaluation.AvgTCPMs)
	}
	tlsStr := "—"
	if r.Evaluation.AvgTLSMs != nil {
		tlsStr = fmt.Sprintf("%0.1f ms", *r.Evaluation.AvgTLSMs)
	}
	ttfbStr := "—"
	if r.Evaluation.AvgTTFBMs != nil {
		ttfbStr = fmt.Sprintf("%0.1f ms", *r.Evaluation.AvgTTFBMs)
	}
	latStr := "—"
	if r.Evaluation.AvgLatencyMs != nil {
		latStr = fmt.Sprintf("%0.1f ms", *r.Evaluation.AvgLatencyMs)
	}

	b.WriteString("  \033[1mORTALAMA METRİKLER:\033[0m\n")
	b.WriteString(fmt.Sprintf("    DNS Çözümleme:   \033[1;37m%-10s\033[0m  TCP Bağlantı:    \033[1;37m%-10s\033[0m\n", dnsStr, tcpStr))
	b.WriteString(fmt.Sprintf("    TLS El Sıkışma:  \033[1;37m%-10s\033[0m  İlk Bayt (TTFB): \033[1;37m%-10s\033[0m\n", tlsStr, ttfbStr))
	b.WriteString(fmt.Sprintf("    Toplam Gecikme:  \033[1;37m%-10s\033[0m  Başarı Oranı:    \033[1;32m%d/%d (%0.0f%%)\033[0m\n\n",
		latStr, r.Evaluation.TargetsSuccess, r.Evaluation.TargetsTotal, 100.0-r.Evaluation.PacketLossPercent))

	// Table header
	b.WriteString(fmt.Sprintf("  \033[4;37m%-18s %-7s %-9s %-9s %-9s %-9s %-9s\033[0m\n",
		"HEDEF SERVIS", "DURUM", "DNS", "TCP", "TLS", "TTFB", "TOPLAM"))

	// Table rows
	for _, res := range r.Results {
		statusStr := fmt.Sprintf("%d", res.StatusCode)
		statusColor := "\033[0;32m"
		if !res.OK {
			statusStr = "ERR"
			statusColor = "\033[0;31m"
		}

		dns := "—"
		if res.DNSMs != nil {
			dns = fmt.Sprintf("%0.1fms", *res.DNSMs)
		}
		tcp := "—"
		if res.TCPMs != nil {
			tcp = fmt.Sprintf("%0.1fms", *res.TCPMs)
		}
		tls := "—"
		if res.TLSMs != nil {
			tls = fmt.Sprintf("%0.1fms", *res.TLSMs)
		}
		ttfb := "—"
		if res.TTFBMs != nil {
			ttfb = fmt.Sprintf("%0.1fms", *res.TTFBMs)
		}
		total := fmt.Sprintf("%0.1fms", res.TotalMs)

		name := res.Name
		if len(name) > 18 {
			name = name[:18]
		}

		b.WriteString(fmt.Sprintf("  %-18s %s%-7s\033[0m %-9s %-9s %-9s %-9s %-9s\n",
			name, statusColor, statusStr, dns, tcp, tls, ttfb, total))
	}

	b.WriteString("\n  \033[1;36m========================================================================\033[0m\n\n")
	return b.String()
}

func PrintTerminal(w io.Writer, r Report) {
	if w == nil {
		w = os.Stdout
	}
	_, _ = fmt.Fprint(w, FormatTerminal(r))
}

func PrintJSON(w io.Writer, r Report) error {
	if w == nil {
		w = os.Stdout
	}
	enc := json.NewEncoder(w)
	enc.SetIndent("", "  ")
	return enc.Encode(r)
}
