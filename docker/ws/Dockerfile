FROM golang:1.26.5-alpine AS ws-build

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

FROM golang:1.26.5-alpine AS ws-dev

WORKDIR /app

COPY ws/go.mod ws/go.sum ./
RUN go mod download

COPY ws ./

EXPOSE 8080
CMD ["go", "run", "./cmd/web"]

FROM alpine:3.23 AS ws-prod

RUN apk upgrade --no-cache \
    && apk add --no-cache ca-certificates \
    && addgroup -S -g 10001 app \
    && adduser -S -D -H -u 10001 -G app app

COPY docker/ws/entrypoint.sh /usr/local/bin/docker-entrypoint
COPY --from=ws-build /usr/local/bin/ws /usr/local/bin/ws

RUN chmod +x /usr/local/bin/docker-entrypoint /usr/local/bin/ws

USER app

EXPOSE 8080

ENTRYPOINT ["docker-entrypoint"]
CMD ["ws"]
