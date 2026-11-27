# syntax=docker/dockerfile:1

# ==============================================================================
# Stage 1: Build Frontend Assets (Vite 7 + Tailwind CSS v4 membutuhkan Node >= 20.19 / 22.12)
# ==============================================================================
FROM node:22-bookworm-slim AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --include=optional

COPY . .
RUN npm run build

# ==============================================================================
# Stage 2: Production Runtime (PHP 8.3-FPM + Nginx)
# ==============================================================================
FROM php:8.3-fpm-bookworm

# Install Nginx, library sistem, dan ekstensi PHP yang dibutuhkan SIMPEG
# - zip      : wajib untuk ZipArchive saat import Excel (.xlsx)
# - pgsql    : koneksi PostgreSQL / Supabase
# - pdo_mysql: kompatibilitas koneksi MySQL / MariaDB
# - gd, intl, mbstring, bcmath, opcache, pcntl
RUN apt-get update && apt-get install -y --no-install-recommends \
        nginx \
        curl \
        unzip \
        libpq-dev \
        default-libmysqlclient-dev \
        libzip-dev \
        libonig-dev \
        libicu-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql \
        pgsql \
        pdo_mysql \
        mbstring \
        bcmath \
        zip \
        intl \
        gd \
        opcache \
        pcntl \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Konfigurasi PHP untuk upload file Excel/lampiran & proses kalkulasi gaji
RUN printf '%s\n' \
    'memory_limit = 512M' \
    'upload_max_filesize = 50M' \
    'post_max_size = 50M' \
    'max_execution_time = 300' \
    'max_input_vars = 5000' \
    'expose_php = Off' \
    'opcache.enable = 1' \
    'opcache.memory_consumption = 192' \
    'opcache.interned_strings_buffer = 16' \
    'opcache.max_accelerated_files = 20000' \
    'opcache.validate_timestamps = 0' \
    > /usr/local/etc/php/conf.d/zz-simpeg.ini

# Pastikan environment variable dari Dokploy / Docker diteruskan ke worker PHP-FPM
RUN printf '%s\n' \
    '[www]' \
    'clear_env = no' \
    'pm = dynamic' \
    'pm.max_children = 20' \
    'pm.start_servers = 4' \
    'pm.min_spare_servers = 2' \
    'pm.max_spare_servers = 8' \
    'pm.max_requests = 500' \
    > /usr/local/etc/php-fpm.d/zz-simpeg.conf

# Konfigurasi Nginx (mendukung HTTPS reverse-proxy Traefik/Dokploy agar aset tidak Mixed Content)
RUN rm -f /etc/nginx/sites-enabled/default /etc/nginx/conf.d/default.conf \
    && printf '%s\n' \
    'map $http_x_forwarded_proto $fcgi_https {' \
    '    default $https;' \
    '    https on;' \
    '}' \
    '' \
    'server {' \
    '    listen 8000 default_server;' \
    '    listen [::]:8000 default_server;' \
    '    server_name _;' \
    '' \
    '    root /app/public;' \
    '    index index.php;' \
    '' \
    '    charset utf-8;' \
    '    client_max_body_size 50M;' \
    '' \
    '    location /build/ {' \
    '        expires 1y;' \
    '        add_header Cache-Control "public, immutable";' \
    '        try_files $uri =404;' \
    '    }' \
    '' \
    '    location / {' \
    '        try_files $uri $uri/ /index.php?$query_string;' \
    '    }' \
    '' \
    '    location = /favicon.ico { access_log off; log_not_found off; }' \
    '    location = /robots.txt  { access_log off; log_not_found off; }' \
    '' \
    '    error_page 404 /index.php;' \
    '' \
    '    location ~ \.php$ {' \
    '        fastcgi_pass 127.0.0.1:9000;' \
    '        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;' \
    '        include fastcgi_params;' \
    '        fastcgi_param HTTPS $fcgi_https;' \
    '        fastcgi_read_timeout 300;' \
    '        fastcgi_buffers 16 16k;' \
    '        fastcgi_buffer_size 32k;' \
    '    }' \
    '' \
    '    location ~ /\.(?!well-known).* {' \
    '        deny all;' \
    '    }' \
    '}' \
    > /etc/nginx/conf.d/simpeg.conf

WORKDIR /app

# Install dependency Composer terlebih dahulu agar layer ter-cache
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist

# Salin source code aplikasi & hasil build Vite dari Stage 1
COPY . .
COPY --from=assets /app/public/build ./public/build

# Normalisasi huruf besar PSR-4 folder Concerns (mencegah Trait di-skip di Linux),
# siapkan direktori storage/cache, dan generate optimized autoloader
RUN if [ -d app/Http/Controllers/concerns ]; then \
        mv app/Http/Controllers/concerns app/Http/Controllers/Concerns_tmp && \
        mv app/Http/Controllers/Concerns_tmp app/Http/Controllers/Concerns; \
    fi \
    && mkdir -p \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/testing \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && composer dump-autoload --optimize --no-dev \
    && php artisan package:discover --ansi \
    && chown -R www-data:www-data /app/storage /app/bootstrap/cache \
    && chmod -R ug+rwx /app/storage /app/bootstrap/cache

# Entrypoint saat container dijalankan
RUN printf '%s\n' \
    '#!/bin/sh' \
    'set -e' \
    '' \
    'PORT="${PORT:-8000}"' \
    'sed -i "s/listen 8000 default_server;/listen ${PORT} default_server;/g" /etc/nginx/conf.d/simpeg.conf' \
    'sed -i "s/listen \[::\]:8000 default_server;/listen [::]:${PORT} default_server;/g" /etc/nginx/conf.d/simpeg.conf' \
    '' \
    'mkdir -p /app/storage/app/public /app/storage/framework/cache/data /app/storage/framework/sessions /app/storage/framework/views /app/storage/logs /app/bootstrap/cache' \
    'chown -R www-data:www-data /app/storage /app/bootstrap/cache' \
    'chmod -R ug+rwx /app/storage /app/bootstrap/cache' \
    '' \
    'if [ ! -L /app/public/storage ]; then' \
    '    php artisan storage:link --force >/dev/null 2>&1 || true' \
    'fi' \
    '' \
    'if [ "${APP_ENV}" = "production" ] && [ -n "${APP_KEY}" ]; then' \
    '    php artisan config:cache >/dev/null 2>&1 || true' \
    '    php artisan view:cache >/dev/null 2>&1 || true' \
    'fi' \
    '' \
    'php-fpm -D' \
    'exec nginx -g "daemon off;"' \
    > /usr/local/bin/docker-entrypoint.sh \
    && chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 8000

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
