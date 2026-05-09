#!/usr/bin/env bash
set -e

PORT="${PORT:-8080}"

sed -ri "s/^Listen [0-9]+.*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

if [ -d /app ]; then
    php artisan config:clear  || true
    php artisan view:clear    || true
    php artisan route:clear   || true
    php artisan cache:clear   || true
fi

exec "$@"
