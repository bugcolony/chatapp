FROM golang:1.26-alpine AS ws-build

WORKDIR /app

ARG TARGETOS=linux
ARG TARGETARCH=amd64

COPY ws/go.mod ws/go.sum ./
RUN go mod download

COPY ws ./

RUN CGO_ENABLED=0 GOOS=$TARGETOS GOARCH=$TARGETARCH go build \
    -ldflags="-s -w" \
    -o /usr/local/bin/ws \
    ./cmd/web

FROM golang:1.26-alpine AS ws-dev

WORKDIR /app

COPY ws/go.mod ws/go.sum ./
RUN go mod download

COPY ws ./

EXPOSE 8080
CMD ["go", "run", "./cmd/web"]

FROM alpine:3.21 AS ws-prod

RUN apk add --no-cache ca-certificates

COPY docker/ws/entrypoint.sh /usr/local/bin/docker-entrypoint
COPY --from=ws-build /usr/local/bin/ws /usr/local/bin/ws

RUN chmod +x /usr/local/bin/docker-entrypoint /usr/local/bin/ws

ENTRYPOINT ["docker-entrypoint"]
CMD ["ws"]
