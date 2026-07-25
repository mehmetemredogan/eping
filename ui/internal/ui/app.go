package ui

import (
	"fmt"
	"sort"
	"strings"
	"sync"

	"github.com/charmbracelet/bubbles/spinner"
	"github.com/charmbracelet/bubbles/textinput"
	tea "github.com/charmbracelet/bubbletea"
	"github.com/charmbracelet/lipgloss"

	"pinglab/ui/internal/api"
	"pinglab/ui/internal/config"
	"pinglab/ui/internal/traceroute"
)

const collapseThreshold = 5

type focusField int

const (
	focusAPI focusField = iota
	focusSearch
	focusUser
	focusPass
	focusList
)

type lineKind int

const (
	lineGroup lineKind = iota
	lineTarget
	lineMore
)

type rowState struct {
	target api.Target
	avg    string
	min    string
	max    string
	jitter string
	p50    string
	p95    string
	dnsMs  string
	tcpMs  string
	tlsMs  string
	qual   string
	status string

	// Ping (HTTP TTFB) and traceroute results are kept separate end-to-end
	// so the UI can render them as two distinct, clearly labeled sections.
	pingSummary   string
	pingInsights  []string
	pathSummary   string
	traceInsights []string

	hops       []traceroute.Hop
	trendLabel string
	uploadErr  string
}

type groupState struct {
	Provider    string
	Description string
	Expanded    bool
	Targets     []rowState
}

type viewLine struct {
	kind     lineKind
	groupIdx int
	rowIdx   int // within group, for targets
	hidden   int // for "show more"
}

type targetsMsg struct {
	groups     []groupState
	categories map[string]string
	count      int
	err        error
}

type loginMsg struct {
	user  string
	token string
	err   error
}

type logoutMsg struct{}

type sessionInvalidMsg struct{}

type trendMsg struct {
	summary *api.TrendSummary
	err     error
}

type measureMsg struct {
	targetID uint64
	row      rowState
}

type allMeasuredMsg struct {
	byID map[uint64]rowState
}

type statusMsg string

type Model struct {
	cfg    config.Config
	client *api.Client

	apiInput    textinput.Model
	searchInput textinput.Model
	userInput   textinput.Model
	passInput   textinput.Model
	focus       focusField

	username   string
	category   string // empty = all
	categories map[string]string
	catKeys    []string

	groups []groupState
	lines  []viewLine
	cursor int
	offset int
	height int
	width  int

	// Keep last measured metrics across filter reloads.
	metrics map[uint64]rowState

	lastPingSummary   string
	lastPingInsights  []string
	lastTraceSummary  string
	lastTraceInsights []string

	status         string
	testing        bool
	singleInFlight bool
	err            string
	count          int

	spin       spinner.Model
	spinning   bool
	measureMsg string // live status while measuring

	trend      *api.TrendSummary
	showDetail bool
}

func New(cfg config.Config) Model {
	apiIn := textinput.New()
	apiIn.Placeholder = config.DefaultAPIURL
	apiIn.SetValue(cfg.APIURL)
	apiIn.CharLimit = 200
	apiIn.Width = 28

	searchIn := textinput.New()
	searchIn.Placeholder = "ara…"
	searchIn.CharLimit = 120
	searchIn.Width = 22

	userIn := textinput.New()
	userIn.Placeholder = "kullanıcı"
	userIn.SetValue(cfg.Username)
	userIn.CharLimit = 64
	userIn.Width = 14

	passIn := textinput.New()
	passIn.Placeholder = "şifre"
	passIn.EchoMode = textinput.EchoPassword
	passIn.EchoCharacter = '•'
	passIn.CharLimit = 128
	passIn.Width = 14

	sp := spinner.New()
	sp.Spinner = spinner.Line
	sp.Style = lipgloss.NewStyle().Foreground(lipgloss.Color("39")).Bold(true)

	m := Model{
		cfg:         cfg,
		client:      api.New(cfg.APIURL, cfg.Token),
		apiInput:    apiIn,
		searchInput: searchIn,
		userInput:   userIn,
		passInput:   passIn,
		focus:       focusList,
		categories:  map[string]string{},
		metrics:     map[uint64]rowState{},
		status:      "hazır",
		height:      30,
		width:       100,
		spin:        sp,
	}
	m.setFocus(focusList)
	return m
}

func (m Model) Init() tea.Cmd {
	cmds := []tea.Cmd{m.cmdLoadTargets()}
	if m.cfg.Token != "" {
		cmds = append(cmds, m.cmdCheckSession(), m.cmdFetchTrend())
	}
	return tea.Batch(cmds...)
}

func (m Model) Update(msg tea.Msg) (tea.Model, tea.Cmd) {
	switch msg := msg.(type) {
	case tea.WindowSizeMsg:
		m.width = msg.Width
		m.height = msg.Height
		m.resizeInputs()
		return m, nil

	case tea.KeyMsg:
		switch msg.String() {
		case "ctrl+c":
			return m, tea.Quit
		case "q":
			if m.focus == focusList {
				return m, tea.Quit
			}
		case "esc":
			m.setFocus(focusList)
			return m, nil
		case "tab":
			m.cycleFocus(1)
			return m, nil
		case "shift+tab":
			m.cycleFocus(-1)
			return m, nil
		}

		if m.focus != focusList {
			return m.updateInputs(msg)
		}

		switch msg.String() {
		case "up", "k":
			if m.cursor > 0 {
				m.cursor--
				m.ensureVisible()
				m.syncAnalysisFromCursor()
			}
		case "down", "j":
			if m.cursor < len(m.lines)-1 {
				m.cursor++
				m.ensureVisible()
				m.syncAnalysisFromCursor()
			}
		case "/":
			m.setFocus(focusSearch)
			return m, nil
		case "l":
			m.setFocus(focusUser)
			return m, nil
		case "o":
			return m, m.cmdLogout()
		case "r":
			m.status = "Hedefler yükleniyor…"
			return m, m.cmdLoadTargets()
		case "]":
			m.cycleCategory(1)
			m.status = "Filtre: " + m.categoryLabel()
			return m, m.cmdLoadTargets()
		case "[":
			m.cycleCategory(-1)
			m.status = "Filtre: " + m.categoryLabel()
			return m, m.cmdLoadTargets()
		case "e", "right", "left":
			m.toggleAtCursor()
			m.rebuildLines()
			m.clampCursor()
			m.ensureVisible()
		case "enter", " ":
			return m.activateCursor()
		case "t":
			return m.measureSelected(true)
		case "p":
			return m.measureSelected(true)
		case "a":
			return m.measureAllVisible()
		case "i":
			m.showDetail = !m.showDetail
			m.ensureVisible()
		}
		return m, nil

	case targetsMsg:
		if msg.err != nil {
			m.err = msg.err.Error()
			m.status = "Hedef yükleme hatası"
			return m, nil
		}
		m.err = ""
		m.categories = msg.categories
		m.catKeys = sortedKeys(msg.categories)
		m.groups = msg.groups
		m.applyMetrics()
		m.count = msg.count
		m.rebuildLines()
		m.cursor = 0
		m.offset = 0
		m.status = fmt.Sprintf("%d hedef · %d grup · %s", m.count, len(m.groups), m.categoryLabel())
		return m, nil

	case loginMsg:
		if msg.err != nil {
			m.err = msg.err.Error()
			m.status = "Giriş başarısız"
			return m, nil
		}
		m.err = ""
		m.username = msg.user
		m.cfg.Token = msg.token
		m.cfg.Username = msg.user
		m.client.Token = msg.token
		_ = m.cfg.Save()
		m.status = "Giriş OK — @" + msg.user
		m.setFocus(focusList)
		return m, m.cmdFetchTrend()

	case logoutMsg:
		m.username = ""
		m.cfg.Token = ""
		m.client.Token = ""
		m.trend = nil
		_ = m.cfg.Save()
		m.status = "Oturum kapatıldı"
		return m, nil

	case sessionInvalidMsg:
		// The saved token is no longer accepted by the server — drop it so
		// the UI doesn't keep pretending there's a valid session.
		m.username = ""
		m.cfg.Token = ""
		m.client.Token = ""
		m.trend = nil
		_ = m.cfg.Save()
		m.status = "Kayıtlı oturum geçersiz — tekrar giriş yapın"
		return m, nil

	case trendMsg:
		if msg.err == nil {
			m.trend = msg.summary
		}
		return m, nil

	case measureMsg:
		m.singleInFlight = false
		m.metrics[msg.targetID] = msg.row
		m.patchRow(msg.targetID, msg.row)
		m.lastPingSummary = msg.row.pingSummary
		m.lastPingInsights = msg.row.pingInsights
		m.lastTraceSummary = msg.row.pathSummary
		m.lastTraceInsights = msg.row.traceInsights
		m.status = msg.row.pingSummary
		if msg.row.pathSummary != "" {
			m.status += " · tracert: " + msg.row.pathSummary
		}
		if m.status == "" {
			m.status = "Ölçüm tamam: " + msg.row.target.Name
		}
		if msg.row.uploadErr != "" {
			m.status = "Ölçüm tamam ama sunucuya yüklenemedi: " + msg.row.uploadErr
		}
		if !m.hasMeasuring() {
			m.spinning = false
			m.measureMsg = ""
		}
		return m, nil

	case allMeasuredMsg:
		uploadErrs := 0
		for id, row := range msg.byID {
			m.metrics[id] = row
			m.patchRow(id, row)
			if row.uploadErr != "" {
				uploadErrs++
			}
		}
		m.testing = false
		m.spinning = false
		m.measureMsg = ""
		m.status = fmt.Sprintf("Tamamlandı — %d hedef", len(msg.byID))
		if uploadErrs > 0 {
			m.status += fmt.Sprintf(" (%d sonuç sunucuya yüklenemedi)", uploadErrs)
		}
		if m.cfg.Token != "" {
			return m, m.cmdFetchTrend()
		}
		return m, nil

	case spinner.TickMsg:
		if !m.spinning || !m.hasMeasuring() {
			m.spinning = false
			return m, nil
		}
		var cmd tea.Cmd
		m.spin, cmd = m.spin.Update(msg)
		return m, cmd

	case statusMsg:
		m.status = string(msg)
		return m, nil
	}

	return m, nil
}

func (m Model) updateInputs(msg tea.KeyMsg) (tea.Model, tea.Cmd) {
	if msg.String() == "enter" {
		switch m.focus {
		case focusAPI:
			url := strings.TrimSpace(m.apiInput.Value())
			if url == "" {
				url = config.DefaultAPIURL
				m.apiInput.SetValue(url)
			}
			m.cfg.APIURL = url
			m.client = api.New(m.cfg.APIURL, m.cfg.Token)
			_ = m.cfg.Save()
			m.status = "API kaydedildi: " + m.cfg.APIURL
			m.setFocus(focusList)
			return m, m.cmdLoadTargets()
		case focusSearch:
			m.setFocus(focusList)
			m.status = "Filtreleniyor…"
			return m, m.cmdLoadTargets()
		case focusUser:
			m.setFocus(focusPass)
			return m, nil
		case focusPass:
			return m, m.cmdLogin()
		}
	}

	var cmd tea.Cmd
	switch m.focus {
	case focusAPI:
		m.apiInput, cmd = m.apiInput.Update(msg)
	case focusSearch:
		m.searchInput, cmd = m.searchInput.Update(msg)
	case focusUser:
		m.userInput, cmd = m.userInput.Update(msg)
	case focusPass:
		m.passInput, cmd = m.passInput.Update(msg)
	}
	return m, cmd
}

func (m Model) activateCursor() (tea.Model, tea.Cmd) {
	if len(m.lines) == 0 {
		return m, nil
	}
	line := m.lines[m.cursor]
	switch line.kind {
	case lineGroup, lineMore:
		m.toggleGroup(line.groupIdx)
		m.rebuildLines()
		m.clampCursor()
		m.ensureVisible()
		return m, nil
	case lineTarget:
		return m.measureSelected(true)
	}
	return m, nil
}

func (m Model) measureSelected(withTrace bool) (tea.Model, tea.Cmd) {
	if m.testing || m.singleInFlight || len(m.lines) == 0 {
		return m, nil
	}
	line := m.lines[m.cursor]
	if line.kind != lineTarget {
		return m, nil
	}
	row := &m.groups[line.groupIdx].Targets[line.rowIdx]
	m.clearMeasurement(row)
	m.lastPingSummary = ""
	m.lastPingInsights = nil
	m.lastTraceSummary = ""
	m.lastTraceInsights = nil
	m.measureMsg = row.target.Name
	if withTrace && m.cfg.TraceOnMeasure {
		m.measureMsg += " (+ traceroute)"
	}
	m.status = "Ölçülüyor: " + m.measureMsg
	m.spinning = true
	m.singleInFlight = true
	return m, tea.Batch(m.spin.Tick, m.cmdMeasureOne(row.target, withTrace && m.cfg.TraceOnMeasure))
}

func (m Model) measureAllVisible() (tea.Model, tea.Cmd) {
	if m.testing || m.singleInFlight {
		return m, nil
	}
	targets := m.allTargets()
	if len(targets) == 0 {
		return m, nil
	}
	m.testing = true
	m.lastPingSummary = ""
	m.lastPingInsights = nil
	m.lastTraceSummary = ""
	m.lastTraceInsights = nil
	m.measureMsg = fmt.Sprintf("%d hedef", len(targets))
	m.status = "Ölçülüyor: " + m.measureMsg
	for i := range m.groups {
		for j := range m.groups[i].Targets {
			m.clearMeasurement(&m.groups[i].Targets[j])
		}
	}
	m.spinning = true
	return m, tea.Batch(m.spin.Tick, m.cmdMeasureAll(targets, m.cfg.TraceOnAll))
}

// clearMeasurement resets a row's visible values to the "measuring" state.
// It keeps (rather than deletes) the metrics map entry so that a filter
// reload or reflow mid-measurement doesn't lose track of the fact that this
// target is still in flight (see hasMeasuring/applyMetrics interplay).
func (m *Model) clearMeasurement(row *rowState) {
	row.avg = "—"
	row.min = "—"
	row.max = "—"
	row.jitter = "—"
	row.p50 = "—"
	row.p95 = "—"
	row.dnsMs = "—"
	row.tcpMs = "—"
	row.tlsMs = "—"
	row.qual = ""
	row.status = "ölçülüyor"
	row.pingSummary = ""
	row.pingInsights = nil
	row.pathSummary = ""
	row.traceInsights = nil
	row.hops = nil
	row.trendLabel = ""
	row.uploadErr = ""
	m.metrics[row.target.ID] = *row
}

func (m Model) hasMeasuring() bool {
	if m.testing || m.singleInFlight {
		return true
	}
	for _, g := range m.groups {
		for _, r := range g.Targets {
			if r.status == "ölçülüyor" {
				return true
			}
		}
	}
	return false
}

// currentRow returns the target row under the cursor, if any.
func (m Model) currentRow() (rowState, bool) {
	if len(m.lines) == 0 || m.cursor < 0 || m.cursor >= len(m.lines) {
		return rowState{}, false
	}
	line := m.lines[m.cursor]
	if line.kind != lineTarget {
		return rowState{}, false
	}
	return m.groups[line.groupIdx].Targets[line.rowIdx], true
}

func (m *Model) syncAnalysisFromCursor() {
	if len(m.lines) == 0 {
		return
	}
	line := m.lines[m.cursor]
	if line.kind != lineTarget {
		return
	}
	r := m.groups[line.groupIdx].Targets[line.rowIdx]
	if len(r.pingInsights) == 0 && len(r.traceInsights) == 0 {
		return
	}
	m.lastPingSummary = r.pingSummary
	m.lastPingInsights = r.pingInsights
	m.lastTraceSummary = r.pathSummary
	m.lastTraceInsights = r.traceInsights
}

func (m *Model) rebuildLines() {
	lines := make([]viewLine, 0, 64)
	for gi, g := range m.groups {
		lines = append(lines, viewLine{kind: lineGroup, groupIdx: gi})
		showAll := g.Expanded || len(g.Targets) <= collapseThreshold
		limit := len(g.Targets)
		if !showAll {
			limit = collapseThreshold
		}
		for ri := 0; ri < limit; ri++ {
			lines = append(lines, viewLine{kind: lineTarget, groupIdx: gi, rowIdx: ri})
		}
		if len(g.Targets) > collapseThreshold {
			if showAll {
				lines = append(lines, viewLine{kind: lineMore, groupIdx: gi, hidden: -1}) // -1 => show less
			} else {
				lines = append(lines, viewLine{kind: lineMore, groupIdx: gi, hidden: len(g.Targets) - collapseThreshold})
			}
		}
	}
	m.lines = lines
}

func (m *Model) toggleAtCursor() {
	if len(m.lines) == 0 {
		return
	}
	line := m.lines[m.cursor]
	if line.kind == lineGroup || line.kind == lineMore {
		m.toggleGroup(line.groupIdx)
	}
}

func (m *Model) toggleGroup(idx int) {
	if idx < 0 || idx >= len(m.groups) {
		return
	}
	if len(m.groups[idx].Targets) <= collapseThreshold {
		return
	}
	m.groups[idx].Expanded = !m.groups[idx].Expanded
}

func (m *Model) applyMetrics() {
	for gi := range m.groups {
		for ri := range m.groups[gi].Targets {
			id := m.groups[gi].Targets[ri].target.ID
			if prev, ok := m.metrics[id]; ok {
				t := m.groups[gi].Targets[ri].target
				prev.target = t
				m.groups[gi].Targets[ri] = prev
			}
		}
	}
}

func (m *Model) patchRow(id uint64, row rowState) {
	for gi := range m.groups {
		for ri := range m.groups[gi].Targets {
			if m.groups[gi].Targets[ri].target.ID == id {
				row.target = m.groups[gi].Targets[ri].target
				m.groups[gi].Targets[ri] = row
				return
			}
		}
	}
}

func (m Model) allTargets() []api.Target {
	out := make([]api.Target, 0)
	for _, g := range m.groups {
		for _, r := range g.Targets {
			out = append(out, r.target)
		}
	}
	return out
}

func (m Model) categoryLabel() string {
	if m.category == "" {
		return "tümü"
	}
	if label, ok := m.categories[m.category]; ok {
		return label
	}
	return m.category
}

func (m *Model) cycleCategory(dir int) {
	// order: "" (all) + catKeys
	keys := append([]string{""}, m.catKeys...)
	idx := 0
	for i, k := range keys {
		if k == m.category {
			idx = i
			break
		}
	}
	idx = (idx + dir + len(keys)) % len(keys)
	m.category = keys[idx]
}

func (m *Model) resizeInputs() {
	w := m.width
	if w < 60 {
		w = 60
	}
	m.apiInput.Width = min(32, max(16, w/3))
	m.searchInput.Width = min(24, max(12, w/4))
	m.userInput.Width = 14
	m.passInput.Width = 14
}

func (m Model) lineBlockHeight(i int) int {
	if i < 0 || i >= len(m.lines) {
		return 1
	}
	switch m.lines[i].kind {
	case lineTarget:
		r := m.groups[m.lines[i].groupIdx].Targets[m.lines[i].rowIdx]
		return rowLineCount(r)
	default:
		return 1
	}
}

// listBodyBudget mirrors the exact chrome height View() computes (top +
// bottom panels), so the scroll/cursor math never disagrees with what's
// actually rendered on screen (e.g. when the analysis panel grows to two
// lines, or the bottom detail panel is toggled on).
func (m *Model) listBodyBudget() int {
	w := m.contentWidth()
	h := m.termHeight()
	top := m.renderTop(w)
	bottom := m.renderBottom(w)
	budget := h - lipgloss.Height(top) - lipgloss.Height(bottom)
	if budget < 5 {
		budget = 5
	}
	return budget
}

func (m *Model) ensureVisible() {
	if len(m.lines) == 0 {
		m.offset = 0
		return
	}
	if m.cursor < m.offset {
		m.offset = m.cursor
		return
	}
	budget := m.listBodyBudget()
	for {
		used := 0
		fits := true
		for i := m.offset; i <= m.cursor && i < len(m.lines); i++ {
			used += m.lineBlockHeight(i)
			if used > budget {
				fits = false
				break
			}
		}
		if fits || m.offset >= m.cursor {
			break
		}
		m.offset++
	}
}

func min(a, b int) int {
	if a < b {
		return a
	}
	return b
}

func (m *Model) clampCursor() {
	if len(m.lines) == 0 {
		m.cursor = 0
		return
	}
	if m.cursor >= len(m.lines) {
		m.cursor = len(m.lines) - 1
	}
	if m.cursor < 0 {
		m.cursor = 0
	}
}

func (m *Model) setFocus(f focusField) {
	m.focus = f
	m.apiInput.Blur()
	m.searchInput.Blur()
	m.userInput.Blur()
	m.passInput.Blur()
	switch f {
	case focusAPI:
		m.apiInput.Focus()
	case focusSearch:
		m.searchInput.Focus()
	case focusUser:
		m.userInput.Focus()
	case focusPass:
		m.passInput.Focus()
	}
}

func (m *Model) cycleFocus(dir int) {
	order := []focusField{focusAPI, focusSearch, focusUser, focusPass, focusList}
	idx := 0
	for i, f := range order {
		if f == m.focus {
			idx = i
			break
		}
	}
	idx = (idx + dir + len(order)) % len(order)
	m.setFocus(order[idx])
}

func (m Model) cmdLoadTargets() tea.Cmd {
	base := strings.TrimSpace(m.apiInput.Value())
	token := m.cfg.Token
	filter := api.TargetFilter{
		Search:   strings.TrimSpace(m.searchInput.Value()),
		Category: m.category,
	}
	expanded := map[string]bool{}
	for _, g := range m.groups {
		if g.Expanded {
			expanded[g.Provider] = true
		}
	}
	return func() tea.Msg {
		client := api.New(base, token)
		res, err := client.Targets(filter)
		if err != nil {
			return targetsMsg{err: err}
		}
		groups := make([]groupState, 0, len(res.Groups))
		for _, g := range res.Groups {
			rows := make([]rowState, 0, len(g.Targets))
			for _, t := range g.Targets {
				rows = append(rows, rowState{
					target: t,
					avg:    "—",
					min:    "—",
					max:    "—",
					jitter: "—",
					status: "bekliyor",
				})
			}
			desc := ""
			if g.DescriptionMarkdown != nil {
				desc = flattenMarkdown(*g.DescriptionMarkdown)
			}
			groups = append(groups, groupState{
				Provider:    g.Provider,
				Description: desc,
				Expanded:    expanded[g.Provider],
				Targets:     rows,
			})
		}
		return targetsMsg{
			groups:     groups,
			categories: res.Categories,
			count:      res.Count,
		}
	}
}

func (m Model) cmdCheckSession() tea.Cmd {
	client := api.New(m.cfg.APIURL, m.cfg.Token)
	return func() tea.Msg {
		u, err := client.Me()
		if err != nil {
			return sessionInvalidMsg{}
		}
		return loginMsg{user: u.Username, token: client.Token}
	}
}

// cmdFetchTrend pulls the logged-in user's overall + per-target trend
// (recent measurements vs their own history) so the UI can show whether the
// network is improving or degrading over time.
func (m Model) cmdFetchTrend() tea.Cmd {
	if m.cfg.Token == "" {
		return nil
	}
	client := api.New(m.cfg.APIURL, m.cfg.Token)
	return func() tea.Msg {
		summary, err := client.Trend()
		return trendMsg{summary: summary, err: err}
	}
}

func (m Model) cmdLogin() tea.Cmd {
	base := strings.TrimSpace(m.apiInput.Value())
	user := m.userInput.Value()
	pass := m.passInput.Value()
	return func() tea.Msg {
		client := api.New(base, "")
		res, err := client.Login(user, pass)
		if err != nil {
			return loginMsg{err: err}
		}
		return loginMsg{user: res.User.Username, token: res.Token}
	}
}

func (m Model) cmdLogout() tea.Cmd {
	client := api.New(m.cfg.APIURL, m.cfg.Token)
	return func() tea.Msg {
		_ = client.Logout()
		return logoutMsg{}
	}
}

func (m Model) cmdMeasureOne(t api.Target, withTrace bool) tea.Cmd {
	samples := m.cfg.Samples
	client := api.New(strings.TrimSpace(m.apiInput.Value()), m.cfg.Token)
	token := m.cfg.Token
	return func() tea.Msg {
		row := measureTarget(client, token, t, samples, withTrace)
		return measureMsg{targetID: t.ID, row: row}
	}
}

func (m Model) cmdMeasureAll(targets []api.Target, withTrace bool) tea.Cmd {
	samples := m.cfg.Samples
	concurrency := m.cfg.Concurrency
	client := api.New(strings.TrimSpace(m.apiInput.Value()), m.cfg.Token)
	token := m.cfg.Token
	return func() tea.Msg {
		byID := make(map[uint64]rowState, len(targets))
		var mu sync.Mutex
		sem := make(chan struct{}, concurrency)
		var wg sync.WaitGroup
		for _, t := range targets {
			wg.Add(1)
			go func(t api.Target) {
				defer wg.Done()
				sem <- struct{}{}
				defer func() { <-sem }()
				row := measureTarget(client, token, t, samples, withTrace)
				mu.Lock()
				byID[t.ID] = row
				mu.Unlock()
			}(t)
		}
		wg.Wait()
		return allMeasuredMsg{byID: byID}
	}
}

func Run(cfg config.Config) error {
	p := tea.NewProgram(New(cfg), tea.WithAltScreen())
	_, err := p.Run()
	return err
}

func sortedKeys(m map[string]string) []string {
	keys := make([]string, 0, len(m))
	for k := range m {
		keys = append(keys, k)
	}
	sort.Strings(keys)
	return keys
}

func flattenMarkdown(s string) string {
	s = strings.ReplaceAll(s, "\r\n", "\n")
	s = strings.ReplaceAll(s, "\n", " ")
	s = strings.ReplaceAll(s, "**", "")
	s = strings.ReplaceAll(s, "*", "")
	s = strings.ReplaceAll(s, "`", "")
	for strings.Contains(s, "  ") {
		s = strings.ReplaceAll(s, "  ", " ")
	}
	return strings.TrimSpace(s)
}

func max(a, b int) int {
	if a > b {
		return a
	}
	return b
}

var (
	logoStyle          = lipgloss.NewStyle().Bold(true).Foreground(lipgloss.Color("213"))
	logoTagStyle       = lipgloss.NewStyle().Foreground(lipgloss.Color("245"))
	mutedStyle         = lipgloss.NewStyle().Foreground(lipgloss.Color("245"))
	labelStyle         = lipgloss.NewStyle().Foreground(lipgloss.Color("250")).Bold(true)
	groupStyle         = lipgloss.NewStyle().Bold(true).Foreground(lipgloss.Color("255")).Background(lipgloss.Color("237"))
	groupSelectedStyle = lipgloss.NewStyle().Bold(true).Foreground(lipgloss.Color("255")).Background(lipgloss.Color("62"))
	selectedBlockStyle = lipgloss.NewStyle().Background(lipgloss.Color("236"))
	okStyle            = lipgloss.NewStyle().Foreground(lipgloss.Color("82"))
	warnStyle          = lipgloss.NewStyle().Foreground(lipgloss.Color("221"))
	badStyle           = lipgloss.NewStyle().Foreground(lipgloss.Color("203"))
	errStyle           = lipgloss.NewStyle().Foreground(lipgloss.Color("196")).Bold(true)
	catStyle           = lipgloss.NewStyle().Foreground(lipgloss.Color("183")).Bold(true)
	badgeExcellent     = lipgloss.NewStyle().Foreground(lipgloss.Color("120")).Bold(true)
	badgeGood          = lipgloss.NewStyle().Foreground(lipgloss.Color("82")).Bold(true)
	badgeFair          = lipgloss.NewStyle().Foreground(lipgloss.Color("221")).Bold(true)
	badgePoor          = lipgloss.NewStyle().Foreground(lipgloss.Color("208")).Bold(true)
	badgeFail          = lipgloss.NewStyle().Foreground(lipgloss.Color("203")).Bold(true)
	measuringStyle     = lipgloss.NewStyle().Foreground(lipgloss.Color("39")).Bold(true)
	pendingStyle       = lipgloss.NewStyle().Foreground(lipgloss.Color("240"))
	moreStyle          = lipgloss.NewStyle().Foreground(lipgloss.Color("111"))
	moreSelectedStyle  = lipgloss.NewStyle().Foreground(lipgloss.Color("159")).Background(lipgloss.Color("236")).Bold(true)
	analysisHeadStyle  = lipgloss.NewStyle().Bold(true).Foreground(lipgloss.Color("189"))
	pingHeadStyle      = lipgloss.NewStyle().Bold(true).Foreground(lipgloss.Color("120"))
	traceHeadStyle     = lipgloss.NewStyle().Bold(true).Foreground(lipgloss.Color("111"))
	traceMetaStyle     = lipgloss.NewStyle().Foreground(lipgloss.Color("111"))
	statusStyle        = lipgloss.NewStyle().Foreground(lipgloss.Color("252"))
	helpStyle          = lipgloss.NewStyle().Foreground(lipgloss.Color("243"))
	ruleStyle          = lipgloss.NewStyle().Foreground(lipgloss.Color("238"))
)

func rowStyle(qual string) lipgloss.Style {
	switch qual {
	case "excellent", "good":
		return okStyle
	case "fair", "ok":
		return warnStyle
	case "poor", "degraded", "fail", "unreachable":
		return badStyle
	default:
		return lipgloss.NewStyle()
	}
}

func qualLabel(qual, status string) string {
	if status == "ölçülüyor" || status == "bekliyor" {
		return status
	}
	if qual != "" {
		return qual
	}
	return status
}

func trunc(s string, n int) string {
	r := []rune(s)
	if len(r) <= n {
		return s
	}
	if n <= 1 {
		return string(r[:n])
	}
	return string(r[:n-1]) + "…"
}

func deref(s *string) string {
	if s == nil {
		return ""
	}
	return *s
}
