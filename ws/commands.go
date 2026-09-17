package blueberry

import "encoding/json"

const (
	OpMessageCreated        = 1
	OpChannelCreated        = 2
	OpChannelUpdated        = 3
	OpChannelDeleted        = 4
	OpVoiceUserJoined       = 5
	OpVoiceUserLeft         = 6
	OpVoiceChannelClosed    = 7
	OpFriendRequestReceived = 8
	OpFriendAdded           = 9
	OpFriendRemoved         = 10
	OpServerActive          = 200
	OpTypingStart           = 201
	OpTypingStop            = 202
	OpMemberStatusSnapshot  = 300
	OpMemberStatus          = 301
	OpFriendStatusSnapshot  = 302
	OpFriendStatus          = 303
)

const (
	MemberStatusOffline        = "offline"
	MemberStatusOnline         = "online"
	TypePresenceExpirationTime = 6
)

type EventData[T any] struct {
	Op   int `json:"op"`
	Data T   `json:"data"`
}

type Route struct {
	ServerId int   `json:"server_id"`
	UserIds  []int `json:"user_ids"`
}

type Gateway struct {
	Route Route `json:"route"`
	Op    int   `json:"op"`
}

type EventPub struct {
	Gateway Gateway         `json:"gateway"`
	Client  json.RawMessage `json:"client"`
}

type ChannelRef struct {
	ServerId  *int `json:"server_id"`
	ChannelId *int `json:"channel_id"`
}

type TypingData struct {
	ServerId  int `json:"server_id"`
	ChannelId int `json:"channel_id"`
	UserId    int `json:"user_id"`
}

type MemberState struct {
	UserId int    `json:"user_id"`
	Status string `json:"status"`
}

type MemberStatusData struct {
	ServerId int    `json:"server_id"`
	UserId   int    `json:"user_id"`
	Status   string `json:"status"`
}

type MemberSnapshotData struct {
	ServerId int           `json:"server_id"`
	Members  []MemberState `json:"members"`
}

type FriendStatusData struct {
	UserId int    `json:"user_id"`
	Status string `json:"status"`
}

type FriendSnapshotData struct {
	Friends []MemberState `json:"friends"`
}

type SetActiveServerCommand struct {
	client   *Client
	serverId *int
}

type SetTypingPresenceCommand struct {
	start          bool
	client         *Client
	typingPresence *TypingState
}
