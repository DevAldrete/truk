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

# If a command was passed (a Render one-off "Docker Command" or
# `docker run <image> php artisan ...`), run it instead of the web server.
if [ "$#" -gt 0 ]; then
    exec "$@"
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

# Optionally migrate on boot. Enabled on the web service via RUN_MIGRATIONS=true.
if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force --no-interaction
fi

# Optionally seed the demo dataset on boot. Guarded by an emptiness check so a
# redeploy never duplicates data: it only seeds when there are no users yet.
if [ "${RUN_SEED:-false}" = "true" ]; then
    users=$(php artisan tinker --execute='echo \App\Models\User::query()->count();' 2>/dev/null | tr -dc '0-9')

    if [ -z "$users" ] || [ "$users" = "0" ]; then
        php artisan db:seed --force --no-interaction
    else
        echo "RUN_SEED=true but the database already has ${users} user(s); skipping seed."
    fi
fi

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
