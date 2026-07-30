FROM node:24-alpine AS frontend-build

WORKDIR /app

ARG NUXT_PUBLIC_API_BASE=
ARG NUXT_PUBLIC_APP_URL=
ARG NUXT_PUBLIC_WS_URL=
ARG NUXT_PUBLIC_KLIPY_API_KEY=
ARG NUXT_AUTH_TIMEOUT=10000

ENV NUXT_PUBLIC_API_BASE=$NUXT_PUBLIC_API_BASE
ENV NUXT_PUBLIC_APP_URL=$NUXT_PUBLIC_APP_URL
ENV NUXT_PUBLIC_WS_URL=$NUXT_PUBLIC_WS_URL
ENV NUXT_PUBLIC_KLIPY_API_KEY=$NUXT_PUBLIC_KLIPY_API_KEY
ENV NUXT_AUTH_TIMEOUT=$NUXT_AUTH_TIMEOUT

COPY frontend/package.json frontend/package-lock.json ./
RUN npm ci

COPY frontend ./

RUN npm run generate

FROM node:24-alpine AS frontend-dev

WORKDIR /app

ARG NUXT_PUBLIC_API_BASE=
ARG NUXT_PUBLIC_APP_URL=
ARG NUXT_PUBLIC_WS_URL=
ARG NUXT_PUBLIC_KLIPY_API_KEY=
ARG NUXT_AUTH_TIMEOUT=10000

ENV NUXT_PUBLIC_API_BASE=$NUXT_PUBLIC_API_BASE
ENV NUXT_PUBLIC_APP_URL=$NUXT_PUBLIC_APP_URL
ENV NUXT_PUBLIC_WS_URL=$NUXT_PUBLIC_WS_URL
ENV NUXT_PUBLIC_KLIPY_API_KEY=$NUXT_PUBLIC_KLIPY_API_KEY
ENV NUXT_AUTH_TIMEOUT=$NUXT_AUTH_TIMEOUT

COPY frontend/package.json frontend/package-lock.json ./
RUN npm ci

COPY frontend ./

EXPOSE 3000
CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0", "--port", "3000"]

FROM nginx:1.30.2-alpine3.23 AS frontend-prod

RUN apk upgrade --no-cache \
    && apk del --no-cache curl

COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/frontend/nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=frontend-build /app/.output/public /usr/share/nginx/html

USER nginx

EXPOSE 8080

ENTRYPOINT ["nginx"]
CMD ["-g", "daemon off;"]
