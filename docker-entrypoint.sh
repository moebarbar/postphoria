#!/usr/bin/env bash
set -e

PORT="${PORT:-8080}"

sed -ri "s/^Listen [0-9]+.*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

if [ -d /app ]; then
    php artisan optimize:clear || true
    php artisan config:cache   || true
    php artisan route:cache    || true
    php artisan view:cache     || true
    php artisan event:cache    || true
fi

exec "$@"
