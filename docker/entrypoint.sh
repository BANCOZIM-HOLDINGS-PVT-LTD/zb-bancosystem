#!/bin/sh
set -e

# Switch to app directory
cd /var/www/html





# Create log files if they don't exist and set proper permissions
touch storage/logs/nginx-error.log 2>/dev/null || true
touch storage/logs/nginx-access.log 2>/dev/null || true
touch storage/logs/php-fpm.log 2>/dev/null || true
touch storage/logs/php-error.log 2>/dev/null || true
touch storage/logs/supervisord.log 2>/dev/null || true
chown -R www:www storage/logs 2>/dev/null || true
chmod -R 664 storage/logs/* 2>/dev/null || true

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
if [ -n "$DATABASE_URL" ] || [ -n "$DB_HOST" ]; then
  echo "[entrypoint] Running database migrations (if DB available)"
  php artisan migrate --force || echo "[entrypoint] Migrations skipped/failed"
else
  echo "[entrypoint] Skipping migrations (DATABASE_URL or DB_HOST not set)"
fi

# Ensure storage directories exist and have correct permissions (run AFTER cache generation)
mkdir -p storage/framework/{cache,sessions,views} 2>/dev/null || true
mkdir -p storage/logs 2>/dev/null || true
mkdir -p storage/nginx/{client_body,proxy,fastcgi,uwsgi,scgi} 2>/dev/null || true
mkdir -p storage/app/public 2>/dev/null || true
mkdir -p bootstrap/cache 2>/dev/null || true

# Set permissions for storage and bootstrap/cache
echo "[entrypoint] Setting permissions for storage and bootstrap/cache..."
chown -R www:www storage 2>/dev/null || true
chown -R www:www bootstrap/cache 2>/dev/null || true
chmod -R 775 storage 2>/dev/null || true
chmod -R 775 bootstrap/cache 2>/dev/null || true

# Start supervisor (nginx + php-fpm)
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
