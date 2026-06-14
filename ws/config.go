package blueberry

import (
	"fmt"
	"os"
	"strconv"
)

type Config struct {
	Server ServerConfig
	Redis  RedisConfig
}

type ServerConfig struct {
	Host string
	Port string
}

type RedisConfig struct {
	Host     string
	Port     string
	Password string
	DB       int
	Channel  string
}

func LoadConfig() (*Config, error) {
	redisDB, err := getEnvInt("REDIS_DB", 0)

	if err != nil {
		return nil, err
	}

	return &Config{
		Server: ServerConfig{
			Host: getEnv("WS_HOST", "0.0.0.0"),
			Port: getEnv("WS_PORT", "8080"),
		},
		Redis: RedisConfig{
			Host:     getEnv("REDIS_HOST", "127.0.0.1"),
			Port:     getEnv("REDIS_PORT", "6379"),
			Password: getEnv("REDIS_PASSWORD", ""),
			DB:       redisDB,
			Channel:  getEnv("REDIS_CHANNEL", "messages.created"),
		},
	}, nil
}

func getEnv(key, fallback string) string {
	if val, ok := os.LookupEnv(key); ok {
		return val
	}

	return fallback
}

func getEnvInt(key string, fallback int) (int, error) {
	value := getEnv(key, strconv.Itoa(fallback))
	valueInt, err := strconv.Atoi(value)

	if err != nil {
		return 0, fmt.Errorf("%s must be an integer: %w", key, err)
	}

	return valueInt, nil
}
