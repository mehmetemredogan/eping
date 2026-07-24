package ui

import (
	"fmt"
	"strings"

	"github.com/charmbracelet/lipgloss"

	"pinglab/ui/internal/api"
	"pinglab/ui/internal/traceroute"
)

const (
	AppName    = "ePing"
	AppTagline = "Extended Ping"
)

func (m Model) View() string {
	w := m.contentWidth()
	h := m.termHeight()

	top := m.renderTop(w)
	bottom := m.renderBottom(w)

	topH := lipgloss.Height(top)
	bottomH := lipgloss.Height(bottom)
	listH := h - topH - bottomH
	if listH < 5 {
		listH = 5
	}

	list := m.renderList(w, listH)

	out := lipgloss.JoinVertical(lipgloss.Left, top, list, bottom)
	// Exact frame: never taller than the terminal.
	lines := strings.Split(out, "\n")
	if len(lines) > h {
		lines = lines[:h]
		out = strings.Join(lines, "\n")
	}
	return out
}

func (m Model) contentWidth() int {
	if m.width >= 40 {
		return m.width
	}
	return 80
}

func (m Model) termHeight() int {
	if m.height >= 16 {
		return m.height
	}
	return 30
}

func (m Model) renderTop(w int) string {
	// Brand row
	left := logoStyle.Render(AppName) + "  " + logoTagStyle.Render(AppTagline)
	session := mutedStyle.Render("oturum yok")
	if m.username != "" {
		session = okStyle.Render("@" + m.username)
		if m.trend != nil {
			session += "  " + trendBadge(m.trend.Overall)
		}
	} else if m.cfg.Token != "" {
		session = warnStyle.Render("token")
	}
	right := mutedStyle.Render(fmt.Sprintf("%d hedef", m.count)) + "  " + session
	brand := joinLR(left, right, w)

	rule := ruleStyle.Render(strings.Repeat("─", w))

	// Filters — 2 compact rows
	row1 := labelStyle.Render("API") + " " + m.apiInput.View() + "  " +
		labelStyle.Render("Ara") + " " + m.searchInput.View() + "  " +
		labelStyle.Render("Kat") + " " + catStyle.Render(m.categoryLabel())
	row2 := labelStyle.Render("User") + " " + m.userInput.View() + "  " +
		labelStyle.Render("Pass") + " " + m.passInput.View()

	return strings.Join([]string{
		padOrTrim(brand, w),
		rule,
		padOrTrim(row1, w),
		padOrTrim(row2, w),
		rule,
	}, "\n")
}

func (m Model) renderBottom(w int) string {
	rule := ruleStyle.Render(strings.Repeat("─", w))

	var analysisLines []string
	switch {
	case m.showDetail:
		analysisLines = m.renderDetailLines(w)
	case m.lastPingSummary == "" && m.lastTraceSummary == "":
		analysisLines = []string{mutedStyle.Render("Ping / Tracert: ölçüm için enter")}
	default:
		if m.lastPingSummary != "" {
			analysisLines = append(analysisLines, pingHeadStyle.Render("Ping:    "+trunc(m.lastPingSummary, max(8, w-9))))
		}
		if m.lastTraceSummary != "" {
			analysisLines = append(analysisLines, traceHeadStyle.Render("Tracert: "+trunc(m.lastTraceSummary, max(8, w-9))))
		} else {
			analysisLines = append(analysisLines, mutedStyle.Render("Tracert: kapalı/çalıştırılmadı"))
		}
		if m.trend != nil && m.trend.Overall.TrendKey != "" && m.trend.Overall.TrendKey != "insufficient_data" {
			analysisLines = append(analysisLines, mutedStyle.Render("  "+trunc(overallTrendLine(m.trend.Overall), max(8, w-3))))
		}
	}
	for i := range analysisLines {
		analysisLines[i] = padOrTrim(analysisLines[i], w)
	}

	status := m.status
	var statusLine string
	if m.err != "" {
		statusLine = errStyle.Render(padOrTrim(trunc(m.err, w), w))
	} else if m.spinning {
		live := m.spin.View() + " " + measuringStyle.Render("Ölçülüyor")
		if m.measureMsg != "" {
			live += mutedStyle.Render(" — " + trunc(m.measureMsg, max(8, w-20)))
		}
		statusLine = padOrTrim(live, w)
	} else {
		statusLine = statusStyle.Render(padOrTrim(trunc(status, w), w))
	}
	help := helpStyle.Render(padOrTrim("/ ara  [ ] kat  enter ölç  a tümü  e grup  i detay  r yenile  q çıkış", w))

	parts := []string{rule}
	parts = append(parts, analysisLines...)
	parts = append(parts, rule, statusLine, help)
	return strings.Join(parts, "\n")
}

func (m Model) renderList(w, height int) string {
	if len(m.lines) == 0 {
		empty := mutedStyle.Render("  (hedef yok — r yenile)")
		return padList(empty, w, height)
	}

	var b strings.Builder
	used := 0
	for i := m.offset; i < len(m.lines); i++ {
		block := m.renderItem(i, w)
		h := countLines(block)
		if used > 0 && used+h > height {
			break
		}
		b.WriteString(block)
		if !strings.HasSuffix(block, "\n") {
			b.WriteByte('\n')
		}
		used += h
		if used >= height {
			break
		}
	}
	return padList(strings.TrimRight(b.String(), "\n"), w, height)
}

func (m Model) renderItem(i, w int) string {
	line := m.lines[i]
	sel := i == m.cursor

	switch line.kind {
	case lineGroup:
		g := m.groups[line.groupIdx]
		mark := "▸"
		if g.Expanded || len(g.Targets) <= collapseThreshold {
			mark = "▾"
		}
		left := fmt.Sprintf(" %s %s", mark, g.Provider)
		right := fmt.Sprintf("%d", len(g.Targets))
		row := joinLR(left, right, w)
		if sel {
			return groupSelectedStyle.Width(w).Render(row)
		}
		return groupStyle.Width(w).Render(row)

	case lineTarget:
		r := m.groups[line.groupIdx].Targets[line.rowIdx]
		cur := "  "
		if sel {
			cur = "▸ "
		}
		badge := m.statusBadge(r.qual, r.status)
		lat := latencyText(r.avg, r.qual, r.status)
		right := badge + "  " + lat

		nameW := w - lipgloss.Width(cur) - lipgloss.Width(right) - 1
		if nameW < 8 {
			nameW = 8
		}
		name := trunc(r.target.Name, nameW)
		line1 := cur + nameStyle(sel).Render(padRightPlain(name, nameW)) + " " + right

		hostW := w - 4
		if hostW < 10 {
			hostW = 10
		}
		line2 := "    " + hostStyle(sel).Render(truncMiddle(r.target.Host, hostW))

		out := line1 + "\n" + line2
		if meta := metaLine(r); meta != "" {
			out += "\n    " + mutedStyle.Render(trunc(meta, w-5))
		}
		// Traceroute gets its own clearly labeled line, kept separate from
		// the ping (HTTP TTFB) result shown above it.
		if r.pathSummary != "" {
			out += "\n    " + traceMetaStyle.Render(trunc("↳ Tracert: "+r.pathSummary, w-5))
		}
		if sel {
			return selectedBlockStyle.Width(w).Render(out)
		}
		return out

	case lineMore:
		text := fmt.Sprintf("    + %d daha", line.hidden)
		if line.hidden < 0 {
			text = "    − daha az"
		}
		if sel {
			return moreSelectedStyle.Width(w).Render(text)
		}
		return moreStyle.Render(text)
	}
	return ""
}

// renderDetailLines builds the expanded "i" detail panel. Ping (HTTP TTFB)
// and traceroute are rendered as two clearly separated, independently
// labeled sections — never interleaved — for whichever target row is under
// the cursor.
func (m Model) renderDetailLines(w int) []string {
	r, ok := m.currentRow()
	if !ok {
		return []string{mutedStyle.Render("Detay: bir hedef seçin")}
	}
	if r.status == "bekliyor" {
		return []string{mutedStyle.Render("Detay: " + trunc(r.target.Name, max(8, w-9)) + " — henüz ölçülmedi")}
	}

	out := make([]string, 0, 12)
	head := fmt.Sprintf("Detay: %s", r.target.Name)
	out = append(out, analysisHeadStyle.Render(trunc(head, max(8, w-2))))

	// ── PING ──────────────────────────────────────────────────────────
	out = append(out, pingHeadStyle.Render("── PING (HTTP TTFB) "+strings.Repeat("─", max(0, w-22))))
	if r.avg != "" && r.avg != "—" {
		out = append(out, mutedStyle.Render(trunc(fmt.Sprintf(
			"  Gecikme: ort %s · p50 %s · p95 %s · min %s · maks %s · jitter %s",
			r.avg, r.p50, r.p95, r.min, r.max, r.jitter,
		), max(8, w-2))))
	}
	if r.dnsMs != "" && (r.dnsMs != "—" || r.tcpMs != "—" || r.tlsMs != "—") {
		out = append(out, mutedStyle.Render(trunc(fmt.Sprintf(
			"  Kırılım: DNS %s · TCP %s · TLS %s", r.dnsMs, r.tcpMs, r.tlsMs,
		), max(8, w-2))))
	}
	if len(r.pingInsights) == 0 && (r.avg == "" || r.avg == "—") {
		out = append(out, mutedStyle.Render("  veri yok"))
	}
	if r.trendLabel != "" {
		out = append(out, okStyle.Render(trunc("  "+r.trendLabel, max(8, w-2))))
	}

	// ── TRACEROUTE ────────────────────────────────────────────────────
	out = append(out, traceHeadStyle.Render("── TRACEROUTE "+strings.Repeat("─", max(0, w-16))))
	if len(r.hops) == 0 {
		out = append(out, mutedStyle.Render("  veri yok (traceroute kapalı olabilir — [e]/config: trace_on_measure)"))
		return out
	}
	if r.pathSummary != "" {
		out = append(out, mutedStyle.Render(trunc("  "+r.pathSummary, max(8, w-4))))
	}
	maxHops := 6
	for i, h := range r.hops {
		if i >= maxHops {
			out = append(out, mutedStyle.Render(fmt.Sprintf("  … +%d hop daha", len(r.hops)-maxHops)))
			break
		}
		out = append(out, mutedStyle.Render(trunc("  "+hopDetailLine(h), max(8, w-2))))
	}
	return out
}

func hopDetailLine(h traceroute.Hop) string {
	if h.Timeout {
		return fmt.Sprintf("%2d  *  zaman aşımı", h.TTL)
	}
	ip := h.IP
	if ip == "" {
		ip = "?"
	}
	rtt := "—"
	if h.AvgMs != nil {
		rtt = fmt.Sprintf("%.1f ms", *h.AvgMs)
	}
	return fmt.Sprintf("%2d  %-15s  %-8s  %s", h.TTL, ip, string(h.Kind), rtt)
}

func trendBadge(t api.Trend) string {
	switch t.TrendKey {
	case "improving":
		return okStyle.Render("↑ iyileşiyor")
	case "degrading":
		return badStyle.Render("↓ kötüleşiyor")
	case "stable":
		return mutedStyle.Render("→ stabil")
	default:
		return ""
	}
}

func overallTrendLine(t api.Trend) string {
	pct := ""
	if t.DeltaPercent != nil {
		pct = fmt.Sprintf(" (%%%.0f)", *t.DeltaPercent)
	}
	return fmt.Sprintf("Genel ağ eğilimi: %s%s", t.TrendLabel, pct)
}

// metaLine holds only static target metadata (location/country). Traceroute
// output is intentionally not mixed in here — it gets its own line/section
// so ping and tracert results are always visually distinct.
func metaLine(r rowState) string {
	parts := make([]string, 0, 2)
	if loc := deref(r.target.Location); loc != "" {
		parts = append(parts, loc)
	}
	if cc := deref(r.target.CountryCode); cc != "" {
		parts = append(parts, strings.ToUpper(cc))
	}
	return strings.Join(parts, " · ")
}

// rowLineCount returns how many terminal lines a target row occupies, kept
// in sync with renderItem's lineTarget case so scroll/cursor math never
// disagrees with what's actually rendered.
func rowLineCount(r rowState) int {
	n := 2
	if metaLine(r) != "" {
		n++
	}
	if r.pathSummary != "" {
		n++
	}
	return n
}

func padList(content string, w, height int) string {
	lines := strings.Split(content, "\n")
	if content == "" {
		lines = nil
	}
	for len(lines) < height {
		lines = append(lines, "")
	}
	if len(lines) > height {
		lines = lines[:height]
	}
	for i := range lines {
		lines[i] = padOrTrim(lines[i], w)
	}
	return strings.Join(lines, "\n")
}

func joinLR(left, right string, w int) string {
	lw := lipgloss.Width(left)
	rw := lipgloss.Width(right)
	gap := w - lw - rw
	if gap < 1 {
		gap = 1
		avail := w - rw - 1
		if avail < 4 {
			return trunc(left, w)
		}
		left = trunc(left, avail)
		lw = lipgloss.Width(left)
		gap = w - lw - rw
		if gap < 1 {
			gap = 1
		}
	}
	return left + strings.Repeat(" ", gap) + right
}

func padOrTrim(s string, w int) string {
	width := lipgloss.Width(s)
	if width == w {
		return s
	}
	if width < w {
		return s + strings.Repeat(" ", w-width)
	}
	return trunc(s, w)
}

func padRightPlain(s string, n int) string {
	r := []rune(s)
	if len(r) >= n {
		return string(r[:n])
	}
	return s + strings.Repeat(" ", n-len(r))
}

func countLines(s string) int {
	if s == "" {
		return 0
	}
	return strings.Count(s, "\n") + 1
}

func (m Model) statusBadge(qual, status string) string {
	switch {
	case status == "ölçülüyor":
		spin := m.spin.View()
		if !m.spinning {
			spin = measuringStyle.Render("…")
		}
		return spin + " " + measuringStyle.Render("Ölçülüyor")
	case status == "bekliyor":
		return pendingStyle.Render("bekliyor")
	case qual == "excellent":
		return badgeExcellent.Render("mükemmel")
	case qual == "good":
		return badgeGood.Render("iyi")
	case qual == "fair", qual == "ok":
		return badgeFair.Render("orta")
	case qual == "poor":
		return badgePoor.Render("zayıf")
	case qual == "degraded":
		return badgePoor.Render("kararsız")
	case qual == "fail", qual == "unreachable":
		return badgeFail.Render("fail")
	default:
		return mutedStyle.Render(qualLabel(qual, status))
	}
}

func latencyText(avg, qual, status string) string {
	if status == "bekliyor" || status == "ölçülüyor" || avg == "" || avg == "—" {
		return mutedStyle.Render("—")
	}
	return rowStyle(qual).Bold(true).Render(strings.TrimSuffix(avg, " ms") + "ms")
}

func nameStyle(selected bool) lipgloss.Style {
	if selected {
		return lipgloss.NewStyle().Bold(true).Foreground(lipgloss.Color("15"))
	}
	return lipgloss.NewStyle().Bold(true).Foreground(lipgloss.Color("252"))
}

func hostStyle(selected bool) lipgloss.Style {
	if selected {
		return lipgloss.NewStyle().Foreground(lipgloss.Color("159"))
	}
	return lipgloss.NewStyle().Foreground(lipgloss.Color("117"))
}

func truncMiddle(s string, n int) string {
	r := []rune(s)
	if n <= 0 {
		return ""
	}
	if len(r) <= n {
		return s
	}
	if n < 5 {
		return string(r[:n])
	}
	left := (n - 1) / 2
	right := n - 1 - left
	return string(r[:left]) + "…" + string(r[len(r)-right:])
}
