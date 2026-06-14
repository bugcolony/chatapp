package blueberry

import (
	"context"
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"testing"
)

type healthTestStore struct{}

func (healthTestStore) AuthUser(context.Context, string) (*User, error) {
	return nil, nil
}

func (healthTestStore) SubscribeToChannel(chan Broadcast, string) error {
	return nil
}

func TestHealth(t *testing.T) {
	server := NewServer(healthTestStore{}, "messages.created")
	response := httptest.NewRecorder()

	server.ServeHTTP(response, httptest.NewRequest(http.MethodGet, "/health", nil))

	if response.Code != http.StatusOK {
		t.Fatalf("expected status %d, got %d", http.StatusOK, response.Code)
	}

	var body struct {
		Status string `json:"status"`
	}

	if err := json.NewDecoder(response.Body).Decode(&body); err != nil {
		t.Fatalf("decode health response: %v", err)
	}
	if body.Status != "ok" {
		t.Fatalf("expected healthy status, got %q", body.Status)
	}
}
