#!/usr/bin/env bash

PORT="${PORT:-8080}"
echo "[entrypoint] PORT=${PORT}"

sed -ri "s/^Listen [0-9]+.*/Listen ${PORT}/" /etc/apache2/ports.conf || true
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf || true

if [ -d /app ]; then
    cd /app
    php artisan optimize:clear 2>&1 | sed 's/^/[entrypoint] /' || true
fi

echo "[entrypoint] starting: $*"
exec "$@"
