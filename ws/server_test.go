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

func TestDecodeEventForwardsClientEventData(t *testing.T) {
	client := `{"op":4,"data":{"id":56,"server_id":12,"type":"text"}}`

	broadcast, ok := decodeEvent(`{"gateway":{"route":{"server_id":12}},"client":` + client + `}`)

	if !ok {
		t.Fatal("valid event was rejected")
	}
	if broadcast.route.ServerId != 12 {
		t.Fatalf("expected server 12, got %d", broadcast.route.ServerId)
	}
	if string(broadcast.data) != client {
		t.Fatalf("client event data was altered: %s", broadcast.data)
	}
}

func TestDecodeEventRejectsInvalidEvents(t *testing.T) {
	for _, payload := range []string{
		`not json`,
		`{"gateway":{"route":{"server_id":12}}}`,
		`{"gateway":{},"client":{"op":4,"data":{}}}`,
	} {
		if _, ok := decodeEvent(payload); ok {
			t.Fatalf("invalid event was accepted: %s", payload)
		}
	}
}

func TestDecodeEventAcceptsUserRoute(t *testing.T) {
	broadcast, ok := decodeEvent(`{"gateway":{"route":{"user_ids":[3,9]}},"client":{"op":1,"data":{}}}`)

	if !ok {
		t.Fatal("user-routed event was rejected")
	}
	if len(broadcast.route.UserIds) != 2 || broadcast.route.UserIds[0] != 3 || broadcast.route.UserIds[1] != 9 {
		t.Fatalf("unexpected user route: %+v", broadcast.route)
	}
}
