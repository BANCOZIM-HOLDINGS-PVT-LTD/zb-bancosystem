#!/bin/sh
# Quick diagnostic script to check app status

echo "=== Checking Migration Status ==="
php /var/www/html/artisan migrate:status

echo ""
echo "=== Checking Database Connection ==="
php /var/www/html/artisan tinker --execute="DB::connection()->getPdo(); echo 'Database connected successfully';"

echo ""
echo "=== Last 20 Lines of Laravel Log ==="
tail -20 /var/www/html/storage/logs/laravel.log

echo ""
echo "=== PHP-FPM Status ==="
ps aux | grep php-fpm | head -5

echo ""
echo "=== Nginx Status ==="
ps aux | grep nginx | head -5

echo ""
echo "=== Environment Variables ==="
env | grep -E "APP_|DB_|DATABASE_URL" | sed 's/=.*/=***/'

