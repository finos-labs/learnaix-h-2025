# Moodle + Apache + PHP 8.2
FROM php:8.2-apache

# OS deps for PHP extensions used by Moodle 5.0
# - pkg-config + libsodium-dev (sodium)
# - libonig-dev (mbstring)
# - libzip-dev (zip)
# - libicu-dev (intl)
# - libxml2-dev (soap)
# - libjpeg/libpng/libfreetype/libwebp (gd)
RUN set -eux; \
    apt-get update; \
    DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends \
        cron git unzip curl ca-certificates pkg-config \
        libpng-dev libjpeg-dev libfreetype6-dev libwebp-dev \
        libzip-dev libicu-dev libxml2-dev libcurl4-openssl-dev \
        libsodium-dev libonig-dev \
        zlib1g-dev netcat-openbsd default-mysql-client \
    ; rm -rf /var/lib/apt/lists/*

# PHP extensions
# exif first (before gd); then gd with jpeg/freetype/webp
RUN set -eux; docker-php-ext-install -j"$(nproc)" exif
RUN set -eux; \
    docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp; \
    docker-php-ext-install -j"$(nproc)" gd
# core set + soap; mbstring finds oniguruma via libonig-dev
RUN set -eux; docker-php-ext-install -j"$(nproc)" mysqli intl zip opcache mbstring soap
# sodium separate (uses libsodium-dev)
RUN set -eux; docker-php-ext-install -j"$(nproc)" sodium

# Apache tweaks
RUN a2enmod rewrite headers

# PHP config
COPY php.ini /usr/local/etc/php/conf.d/zz-moodle.ini

# Download Moodle 5.0 (stable500) into web root
ARG MOODLE_DOWNLOAD_URL=https://download.moodle.org/download.php/direct/stable500/moodle-latest-500.tgz
RUN set -eux; \
    mkdir -p /var/www/html; \
    echo "Downloading: $MOODLE_DOWNLOAD_URL"; \
    curl -fL --retry 3 --connect-timeout 10 "$MOODLE_DOWNLOAD_URL" -o /tmp/moodle.tgz; \
    tar -xzf /tmp/moodle.tgz -C /var/www/html --strip-components=1; \
    rm /tmp/moodle.tgz

# (Optional) Pin to a specific point release instead of 'latest':
# ARG MOODLE_DOWNLOAD_URL=https://packaging.moodle.org/stable500/moodle-5.0.1.tgz

# Project config (optional—remove this COPY if you want the web installer to create it)
COPY config.php /var/www/html/config.php

# moodledata + ownership
RUN set -eux; \
    mkdir -p /var/www/moodledata; \
    chown -R www-data:www-data /var/www/html /var/www/moodledata

# Cron every minute
RUN set -eux; \
    echo '* * * * * www-data /usr/local/bin/php /var/www/html/admin/cli/cron.php >/proc/self/fd/1 2>/proc/self/fd/2' > /etc/cron.d/moodle; \
    chmod 0644 /etc/cron.d/moodle; \
    crontab /etc/cron.d/moodle

# Startup script: wait for DB, then start cron + Apache
RUN printf '%s\n' \
  '#!/usr/bin/env bash' \
  'set -euo pipefail' \
  'DB_HOST="${DB_HOST:-db}"' \
  'DB_PORT="${DB_PORT:-3306}"' \
  'echo "Waiting for database at $DB_HOST:$DB_PORT..."' \
  'until nc -z "$DB_HOST" "$DB_PORT"; do sleep 2; done' \
  'echo "Database is up. Starting cron and Apache..."' \
  'cron' \
  'exec apache2-foreground' > /usr/local/bin/startup.sh && \
  chmod +x /usr/local/bin/startup.sh

CMD ["startup.sh"]