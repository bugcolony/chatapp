package blueberry

const (
	OpClientActiveServer = 200
	OpClientTypingStart  = 201
	OpClientTypingStop   = 202

	OpGatewayMemberStatusSnapshot = 300
	OpGatewayMemberStatusChanged  = 301
)

const (
	MemberStatusOffline        = "offline"
	MemberStatusOnline         = "online"
	TypePresenceExpirationTime = 6
)

type ClientCommand struct {
	Op        int  `json:"op"`
	ServerId  *int `json:"serverId"`
	ChannelId *int `json:"channelId"`
}

type SetActiveServerCommand struct {
	client   *Client
	serverId *int
}

type MemberState struct {
	Id     int    `json:"id"`
	Status string `json:"status"`
}

type ServerMemberSnapshotEvent struct {
	Op             int                      `json:"op"`
	TargetServerId int                      `json:"targetServerId"`
	Data           map[string][]MemberState `json:"data"`
}

type ServerMemberStatusChangeEvent struct {
	Op             int         `json:"op"`
	TargetServerId int         `json:"targetServerId"`
	Data           MemberState `json:"data"`
}

type TypePresenceEvent struct {
	Op              int            `json:"op"`
	TargetServerId  int            `json:"targetServerId"`
	TargetChannelId int            `json:"targetChannelId"`
	Data            map[string]any `json:"data"`
}

type SetTypingPresenceCommand struct {
	start          bool
	client         *Client
	typingPresence *TypingState
}
