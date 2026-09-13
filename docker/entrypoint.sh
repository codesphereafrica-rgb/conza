#!/bin/sh
set -e
port="${PORT:-10000}"
echo "Starting on port $port"
sed -i "s/^Listen .*/Listen ${port}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:.*>/<VirtualHost *:${port}>/" /etc/apache2/sites-available/000-default.conf
echo "ServerName localhost" >> /etc/apache2/apache2.conf
if [ -n "${RENDER_EXTERNAL_URL:-}" ]; then
  export APP_URL="$RENDER_EXTERNAL_URL"
fi
php artisan config:clear
php artisan migrate --force || echo "migrate failed"
php artisan storage:link || true
chown -R www-data:www-data storage bootstrap/cache
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
exec apache2-foreground
