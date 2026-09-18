package api_test

import (
	"encoding/json"
	"io"
	"net/http"
	"net/http/httptest"
	"strings"
	"testing"

	"pinglab/ui/internal/api"
)

func TestStoreResultSendsPayload(t *testing.T) {
	var receivedPayload api.ResultPayload
	var receivedAuthHeader string
	var receivedPath string

	server := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		receivedPath = r.URL.Path
		receivedAuthHeader = r.Header.Get("Authorization")
		body, _ := io.ReadAll(r.Body)
		_ = json.Unmarshal(body, &receivedPayload)

		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusCreated)
		_, _ = w.Write([]byte(`{"id": 123, "status": "success"}`))
	}))
	defer server.Close()

	client := api.New(server.URL, "test-token-123")
	sessionID := "12345678-1234-4234-8234-123456789abc"
	latency := 18.5
	loss := 0.0

	err := client.StoreResult(42, api.ResultPayload{
		SessionID:         &sessionID,
		Status:            "success",
		AvgLatencyMs:      &latency,
		MinLatencyMs:      &latency,
		MaxLatencyMs:      &latency,
		PacketsSent:       4,
		PacketsReceived:   4,
		PacketLossPercent: &loss,
		ConnectionType:    "ethernet",
	})

	if err != nil {
		t.Fatalf("unexpected error: %v", err)
	}
	if receivedPath != "/api/v1/targets/42/results" {
		t.Errorf("path = %q, want /api/v1/targets/42/results", receivedPath)
	}
	if receivedAuthHeader != "Bearer test-token-123" {
		t.Errorf("auth header = %q, want Bearer test-token-123", receivedAuthHeader)
	}
	if receivedPayload.SessionID == nil || *receivedPayload.SessionID != sessionID {
		t.Errorf("sessionID = %v, want %q", receivedPayload.SessionID, sessionID)
	}
	if receivedPayload.ConnectionType != "ethernet" {
		t.Errorf("connection_type = %q, want ethernet", receivedPayload.ConnectionType)
	}
}

func TestDetailedValidationErrorsFormatted(t *testing.T) {
	server := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusUnprocessableEntity)
		_, _ = w.Write([]byte(`{
			"message": "The given data was invalid.",
			"errors": {
				"packets_sent": ["The packets_sent field must not be greater than 100."],
				"status": ["The selected status is invalid."]
			}
		}`))
	}))
	defer server.Close()

	client := api.New(server.URL, "token")
	err := client.StoreResult(1, api.ResultPayload{Status: "invalid"})
	if err == nil {
		t.Fatal("expected error, got nil")
	}

	errMsg := err.Error()
	if !strings.Contains(errMsg, "packets_sent: The packets_sent field must not be greater than 100.") {
		t.Errorf("expected packets_sent error in message, got: %s", errMsg)
	}
	if !strings.Contains(errMsg, "status: The selected status is invalid.") {
		t.Errorf("expected status error in message, got: %s", errMsg)
	}
}

func TestStoreNetworkQuality_Fallback(t *testing.T) {
	pathsCalled := make([]string, 0)
	server := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		pathsCalled = append(pathsCalled, r.URL.Path)
		if r.URL.Path == "/api/v1/network-quality" {
			w.WriteHeader(http.StatusNotFound)
			_, _ = w.Write([]byte(`{"message": "The route api/v1/network-quality could not be found."}`))
			return
		}
		if r.URL.Path == "/api/v1/quality" {
			w.WriteHeader(http.StatusCreated)
			_, _ = w.Write([]byte(`{"id": 1, "score": 90}`))
			return
		}
		w.WriteHeader(http.StatusBadRequest)
	}))
	defer server.Close()

	client := api.New(server.URL, "token")
	err := client.StoreNetworkQuality(api.NetworkQualityPayload{
		Score:  90,
		Grade:  "A+",
		Status: "excellent",
	})
	if err != nil {
		t.Fatalf("expected nil error after fallback, got: %v", err)
	}

	if len(pathsCalled) != 2 {
		t.Fatalf("expected 2 calls, got %d: %v", len(pathsCalled), pathsCalled)
	}
	if pathsCalled[0] != "/api/v1/network-quality" || pathsCalled[1] != "/api/v1/quality" {
		t.Errorf("unexpected path sequence: %v", pathsCalled)
	}
}
