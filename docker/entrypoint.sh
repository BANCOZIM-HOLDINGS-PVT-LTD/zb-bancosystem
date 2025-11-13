#!/bin/sh
set -e

# Switch to app directory
cd /var/www/html

# Ensure storage directories exist and permissions are correct
mkdir -p storage/framework/{cache,sessions,views}
mkdir -p storage/logs
chown -R www:www storage bootstrap/cache || true
chmod -R 755 storage bootstrap/cache || true

# Generate APP_KEY if missing
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then
  echo "[entrypoint] APP_KEY is not set; generating a new key"
  php artisan key:generate --force
fi

# Migrate environment caches at runtime (after secrets are present)
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true

php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Run database migrations if DB is configured (optional, non-fatal)
if [ -n "$DB_CONNECTION" ]; then
  echo "[entrypoint] Running database migrations (if DB available)"
  php artisan migrate --force || echo "[entrypoint] Migrations skipped/failed"
fi

# Start supervisor (nginx + php-fpm)
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf

