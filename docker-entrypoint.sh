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

    # Mounted volumes (e.g. Railway) attach AFTER the image build as root-owned
    # dirs, overriding the build-time chown. Recreate the expected structure and
    # fix ownership on every boot so the web user can read/write uploads.
    mkdir -p storage/app/public storage/framework/cache storage/framework/sessions storage/framework/views storage/logs
    chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
    chmod -R ug+rwX storage bootstrap/cache 2>/dev/null || true
    echo "[entrypoint] storage ownership normalized"

    php artisan optimize:clear 2>&1 | sed 's/^/[entrypoint] /' || true
fi

echo "[entrypoint] starting: $*"
exec "$@"
