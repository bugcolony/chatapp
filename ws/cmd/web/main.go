package main

import (
	"log"
	"net"
	"net/http"

	blueberry "github.com/bugcolony/chat/ws"
)

func main() {
	config, err := blueberry.LoadConfig()
	if err != nil {
		log.Fatal(err)
	}

	store := blueberry.NewRedisStore(config.Redis)
	server := blueberry.NewServer(store, config.Redis.Channel)
	addr := net.JoinHostPort(config.Server.Host, config.Server.Port)

	if err := http.ListenAndServe(addr, server); err != nil {
		log.Fatal(err)
	}
}
