#!/usr/bin/env sh
set -e

# Render provides the port the service must bind to. Fall back to 10000 so the
# image also runs with a plain `docker run`.
: "${PORT:=10000}"
export PORT

# Prefer Render's public URL as APP_URL unless one was set explicitly.
: "${APP_URL:=${RENDER_EXTERNAL_URL:-http://localhost:${PORT}}}"
export APP_URL

if [ -z "${APP_KEY:-}" ]; then
    echo "ERROR: APP_KEY is not set." >&2
    echo "Generate one with: php artisan key:generate --show" >&2
    echo "and add it as an APP_KEY environment variable in Render." >&2
    exit 1
fi

# Render the nginx site config with the runtime port substituted in.
envsubst '${PORT}' \
    < /etc/nginx/templates/default.conf.template \
    > /etc/nginx/conf.d/default.conf

# Ensure the writable framework directories exist (fresh volumes can strip them).
mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    storage/app/private \
    storage/app/evidence \
    bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Cache configuration, routes, and views now that the environment is available.
php artisan optimize --no-interaction

# Optionally migrate on boot. Enabled on the web service in render.yaml.
if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force --no-interaction
fi

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
