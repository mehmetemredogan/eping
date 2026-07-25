package api

import (
	"bytes"
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"net/url"
	"strings"
	"time"
)

type Client struct {
	BaseURL    string
	Token      string
	HTTPClient *http.Client
}

func New(baseURL, token string) *Client {
	return &Client{
		BaseURL: strings.TrimRight(baseURL, "/"),
		Token:   token,
		HTTPClient: &http.Client{
			Timeout: 30 * time.Second,
		},
	}
}

type LoginResponse struct {
	Token string `json:"token"`
	User  User   `json:"user"`
}

type User struct {
	ID       uint64 `json:"id"`
	Username string `json:"username"`
	IsAdmin  bool   `json:"is_admin"`
}

type Target struct {
	ID            uint64  `json:"id"`
	Name          string  `json:"name"`
	Host          string  `json:"host"`
	Category      string  `json:"category"`
	CategoryLabel string  `json:"category_label"`
	Provider      string  `json:"provider"`
	Location      *string `json:"location"`
	CountryCode   *string `json:"country_code"`
	Description   *string `json:"description"`
}

type Group struct {
	Provider            string   `json:"provider"`
	DescriptionMarkdown *string  `json:"description_markdown"`
	DescriptionHTML     *string  `json:"description_html"`
	Targets             []Target `json:"targets"`
}

type TargetsResponse struct {
	Count      int               `json:"count"`
	Categories map[string]string `json:"categories"`
	Groups     []Group           `json:"groups"`
	Targets    []Target          `json:"targets"`
}

type TargetFilter struct {
	Search   string
	Category string
}

// ResultPayload matches Laravel Api\ResultController validation.
type ResultPayload struct {
	SessionID         *string        `json:"session_id,omitempty"`
	Status            string         `json:"status"`
	MinLatencyMs      *float64       `json:"min_latency_ms,omitempty"`
	MaxLatencyMs      *float64       `json:"max_latency_ms,omitempty"`
	AvgLatencyMs      *float64       `json:"avg_latency_ms,omitempty"`
	JitterMs          *float64       `json:"jitter_ms,omitempty"`
	PacketLossPercent *float64       `json:"packet_loss_percent,omitempty"`
	PacketsSent       int            `json:"packets_sent"`
	PacketsReceived   int            `json:"packets_received"`
	Samples           []float64      `json:"samples,omitempty"`
	Metric            string         `json:"metric,omitempty"`
	ClientVersion     string         `json:"client_version,omitempty"`
	ConnectionType    string         `json:"connection_type,omitempty"` // wifi | ethernet | unknown
	NetworkAnalysis   map[string]any `json:"network_analysis,omitempty"`
}

func (c *Client) Login(username, password string) (*LoginResponse, error) {
	body, _ := json.Marshal(map[string]string{
		"username": username,
		"password": password,
	})
	var out LoginResponse
	if err := c.do("POST", "/api/v1/auth/login", body, false, &out); err != nil {
		return nil, err
	}
	c.Token = out.Token
	return &out, nil
}

func (c *Client) Me() (*User, error) {
	var out struct {
		User User `json:"user"`
	}
	if err := c.do("GET", "/api/v1/auth/me", nil, true, &out); err != nil {
		return nil, err
	}
	return &out.User, nil
}

func (c *Client) Logout() error {
	return c.do("POST", "/api/v1/auth/logout", nil, true, nil)
}

func (c *Client) Targets(filter TargetFilter) (*TargetsResponse, error) {
	q := url.Values{}
	if s := strings.TrimSpace(filter.Search); s != "" {
		q.Set("search", s)
	}
	if cat := strings.TrimSpace(filter.Category); cat != "" {
		q.Set("category", cat)
	}
	path := "/api/v1/targets"
	if enc := q.Encode(); enc != "" {
		path += "?" + enc
	}
	var out TargetsResponse
	if err := c.do("GET", path, nil, false, &out); err != nil {
		return nil, err
	}
	return &out, nil
}

func (c *Client) StoreResult(targetID uint64, payload ResultPayload) error {
	body, err := json.Marshal(payload)
	if err != nil {
		return err
	}
	return c.do("POST", fmt.Sprintf("/api/v1/targets/%d/results", targetID), body, true, nil)
}

// TrendLast is the user's most recent stored result for a target/overall scope.
type TrendLast struct {
	Status            string   `json:"status"`
	AvgLatencyMs      *float64 `json:"avg_latency_ms"`
	JitterMs          *float64 `json:"jitter_ms"`
	PacketLossPercent *float64 `json:"packet_loss_percent"`
	TestedAt          *string  `json:"tested_at"`
	NetworkStatus     *string  `json:"network_status"`
}

// Trend compares a user's recent results against their own historical
// baseline. Trend is one of: improving, degrading, stable, insufficient_data.
type Trend struct {
	TargetID            uint64     `json:"target_id,omitempty"`
	Name                string     `json:"name,omitempty"`
	Host                string     `json:"host,omitempty"`
	TrendKey            string     `json:"trend"`
	TrendLabel          string     `json:"trend_label"`
	Last                *TrendLast `json:"last"`
	RecentAvgMs         *float64   `json:"recent_avg_ms"`
	BaselineAvgMs       *float64   `json:"baseline_avg_ms"`
	DeltaMs             *float64   `json:"delta_ms"`
	DeltaPercent        *float64   `json:"delta_percent"`
	RecentLossPercent   *float64   `json:"recent_loss_percent"`
	BaselineLossPercent *float64   `json:"baseline_loss_percent"`
	RecentCount         int        `json:"recent_count"`
	BaselineCount       int        `json:"baseline_count"`
}

type TrendSummary struct {
	Overall Trend   `json:"overall"`
	Targets []Trend `json:"targets"`
}

// Trend fetches the authenticated user's overall improving/degrading trend
// plus a per-target breakdown (history compared against the user's own
// baseline). Requires a logged-in token.
func (c *Client) Trend() (*TrendSummary, error) {
	var out TrendSummary
	if err := c.do("GET", "/api/v1/results/trend", nil, true, &out); err != nil {
		return nil, err
	}
	return &out, nil
}

// TrendForTarget fetches the trend scoped to a single target.
func (c *Client) TrendForTarget(targetID uint64) (*Trend, error) {
	var out struct {
		Target Trend `json:"target"`
	}
	path := fmt.Sprintf("/api/v1/results/trend?target_id=%d", targetID)
	if err := c.do("GET", path, nil, true, &out); err != nil {
		return nil, err
	}
	return &out.Target, nil
}

func (c *Client) do(method, path string, body []byte, auth bool, out any) error {
	var reader io.Reader
	if body != nil {
		reader = bytes.NewReader(body)
	}
	req, err := http.NewRequest(method, c.BaseURL+path, reader)
	if err != nil {
		return err
	}
	req.Header.Set("Accept", "application/json")
	if body != nil {
		req.Header.Set("Content-Type", "application/json")
	}
	if auth && c.Token != "" {
		req.Header.Set("Authorization", "Bearer "+c.Token)
	}

	res, err := c.HTTPClient.Do(req)
	if err != nil {
		return err
	}
	defer res.Body.Close()

	raw, err := io.ReadAll(res.Body)
	if err != nil {
		return err
	}

	if res.StatusCode >= 400 {
		var apiErr struct {
			Message string              `json:"message"`
			Errors  map[string][]string `json:"errors"`
		}
		_ = json.Unmarshal(raw, &apiErr)
		if apiErr.Message != "" {
			return fmt.Errorf("%s", apiErr.Message)
		}
		return fmt.Errorf("API %s: %s", res.Status, strings.TrimSpace(string(raw)))
	}

	if out == nil || res.StatusCode == http.StatusNoContent || len(raw) == 0 {
		return nil
	}
	return json.Unmarshal(raw, out)
}
