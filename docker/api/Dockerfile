FROM composer:2.8 AS composer-bin

FROM php:8.5-cli-alpine AS composer-deps

WORKDIR /app/backend

COPY --from=composer-bin /usr/bin/composer /usr/bin/composer

RUN apk add --no-cache git unzip

COPY backend/composer.json backend/composer.lock ./

RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --ignore-platform-req=ext-pcntl \
    --optimize-autoloader

COPY backend ./

RUN composer dump-autoload \
    --no-dev \
    --classmap-authoritative \
    --no-interaction

FROM composer-deps AS composer-deps-dev

RUN composer install \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --ignore-platform-req=ext-pcntl \
    --optimize-autoloader

FROM php:8.5-fpm-alpine AS api-php

WORKDIR /var/www/html

RUN apk add --no-cache \
        libpq \
        libzip \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        postgresql-dev \
        libzip-dev \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && docker-php-ext-install \
        bcmath \
        exif \
        pcntl \
        pdo_pgsql \
        zip \
    && apk del .build-deps

COPY docker/api/php.ini /usr/local/etc/php/conf.d/99-app.ini
COPY docker/api/www.conf /usr/local/etc/php-fpm.d/zz-app.conf
COPY docker/api/entrypoint.sh /usr/local/bin/docker-entrypoint
COPY docker/laravel/horizon.sh /docker/laravel/horizon.sh
COPY docker/laravel/migrate.sh /docker/laravel/migrate.sh
COPY docker/laravel/scheduler.sh /docker/laravel/scheduler.sh

RUN chmod +x \
        /usr/local/bin/docker-entrypoint \
        /docker/laravel/horizon.sh \
        /docker/laravel/migrate.sh \
        /docker/laravel/scheduler.sh

COPY --from=composer-deps /app/backend /var/www/html

RUN php artisan package:discover --ansi \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

ENTRYPOINT ["docker-entrypoint"]
CMD ["php-fpm", "-F"]

USER www-data

FROM api-php AS api-php-dev

USER root

COPY docker/api/php.dev.ini /usr/local/etc/php/conf.d/zz-dev.ini

COPY --from=composer-deps-dev /app/backend /var/www/html

RUN php artisan package:discover --ansi \
    && chmod -R a+rwX /var/www/html/vendor \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

USER www-data

FROM api-php-dev AS composer-dev

COPY --from=composer-bin /usr/bin/composer /usr/bin/composer

ENTRYPOINT ["composer"]
CMD ["--version"]

FROM nginx:1.30.2-alpine3.23 AS api-web

WORKDIR /var/www/html

RUN apk upgrade --no-cache \
    && apk del --no-cache curl

COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/api/nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=composer-deps /app/backend/public /var/www/html/public

USER nginx

EXPOSE 8080

ENTRYPOINT ["nginx"]
CMD ["-g", "daemon off;"]
