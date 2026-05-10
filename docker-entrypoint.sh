#!/usr/bin/env bash

PORT="${PORT:-8080}"
echo "[entrypoint] PORT=${PORT}"

# Force exactly one MPM enabled, regardless of what the image has.
rm -f /etc/apache2/mods-enabled/mpm_*.conf /etc/apache2/mods-enabled/mpm_*.load
a2enmod mpm_prefork >/dev/null 2>&1 || true
echo "[entrypoint] enabled MPM:" "$(ls /etc/apache2/mods-enabled/mpm_*.load 2>/dev/null || echo none)"

sed -ri "s/^Listen [0-9]+.*/Listen ${PORT}/" /etc/apache2/ports.conf || true
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf || true

if [ -d /app ]; then
    cd /app
    php artisan optimize:clear 2>&1 | sed 's/^/[entrypoint] /' || true
fi

echo "[entrypoint] starting: $*"
exec "$@"
