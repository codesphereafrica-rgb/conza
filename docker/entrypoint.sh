#!/bin/bash
set -e
PORT=${PORT:-10000}
echo "Starting on port $PORT"
sed -i "s/80/$PORT/g" /etc/apache2/sites-available/000-default.conf
sed -i "s/Listen 80/Listen $PORT/g" /etc/apache2/ports.conf
echo "ServerName localhost" >> /etc/apache2/apache2.conf
php artisan migrate --force || true
php artisan config:clear
php artisan cache:clear
php artisan storage:link || true
chown -R www-data:www-data storage bootstrap/cache
exec apache2-foreground