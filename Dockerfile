# syntax=docker/dockerfile:1

# -----------------------------------------------------------------------------
# Truk — production image for Render (and any Docker host).
#
# Stage 1 "build" installs Composer and npm dependencies and compiles the
# front-end assets. The Wayfinder Vite plugin shells out to
# `php artisan wayfinder:generate` while building, so PHP and `vendor/` must be
# present during the asset build. That is why there is a single combined build
# stage rather than a Node-only one.
#
# Stage 2 "runtime" is a lean php-fpm + nginx image that serves the application
# on the port Render provides (the $PORT environment variable).
# -----------------------------------------------------------------------------

FROM php:8.5-cli-bookworm AS build

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_NO_INTERACTION=1 \
    COMPOSER_HOME=/tmp/composer

# git/unzip are needed by Composer; the base image already ships every PHP
# extension the application requires at build time.
RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY --from=node:22-bookworm-slim /usr/local/ /usr/local/

WORKDIR /app

# PHP dependencies first so this layer is cached until the lock file changes.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-progress --no-scripts

# Front-end dependencies.
COPY package.json package-lock.json .npmrc ./
RUN npm ci --no-audit --no-fund

# Application source. The Wayfinder plugin runs `php artisan` against it.
COPY . .

# Re-run Composer now that the full app is present so package discovery and
# scripts run, then compile the client assets and drop the tooling.
RUN composer install --no-dev --no-interaction --prefer-dist --no-progress \
    && npm run build \
    && rm -rf node_modules

# -----------------------------------------------------------------------------

FROM php:8.5-fpm-bookworm AS runtime

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stack \
    LOG_LEVEL=error

RUN apt-get update && apt-get install -y --no-install-recommends \
        nginx supervisor gettext-base curl \
    && rm -rf /var/lib/apt/lists/*

# install-php-extensions resolves the build dependencies, compiles, and removes
# them again — more reliable and leaner than calling docker-php-ext-install by
# hand (parallel builds of intl are prone to races).
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_pgsql intl opcache pcntl

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php-fpm/zz-app.conf /usr/local/etc/php-fpm.d/zz-app.conf
COPY docker/nginx/default.conf.template /etc/nginx/templates/default.conf.template
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

WORKDIR /var/www/html

# Copy the built application (vendor + public/build included) from the build stage.
COPY --from=build --chown=www-data:www-data /app /var/www/html

RUN mkdir -p \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        storage/app/private \
        storage/app/evidence \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

# Render sets $PORT (default 10000); the entrypoint binds nginx to it. EXPOSE is
# purely informational, but helps platforms auto-detect the port.
EXPOSE 10000

ENTRYPOINT ["entrypoint"]
