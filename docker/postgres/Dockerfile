FROM postgres:17.10-alpine3.23@sha256:979c4379dd698aba0b890599a6104e082035f98ef31d9b9291ec22f2b13059ca

RUN apk upgrade --no-cache \
    && rm /usr/local/bin/gosu

USER postgres
