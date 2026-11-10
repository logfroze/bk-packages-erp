#!/usr/bin/env bash
set -e

# 1. Bind Apache dynamically to Render's injected PORT (default 10000)
PORT="${PORT:-10000}"
sed -ri -e "s/Listen [0-9]+/Listen ${PORT}/g" /etc/apache2/ports.conf
sed -ri -e "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/g" /etc/apache2/sites-available/000-default.conf

# 2. Auto-detect Neon / Render PostgreSQL DATABASE_URL
if [ -n "${DATABASE_URL}" ] && [ -z "${DB_URL}" ]; then
    export DB_URL="${DATABASE_URL}"
fi

if [ -n "${DB_URL}" ] && [ -z "${DB_CONNECTION}" ]; then
    export DB_CONNECTION="pgsql"
fi

# 3. Fallback SQLite file if no remote Postgres URL was configured yet
if [ "${DB_CONNECTION}" = "sqlite" ] || [ -z "${DB_CONNECTION}" ]; then
    export DB_CONNECTION="sqlite"
    touch /var/www/html/database/database.sqlite
    chown www-data:www-data /var/www/html/database/database.sqlite
fi

# 4. Ensure Laravel .env exists & APP_KEY is present
if [ ! -f /var/www/html/.env ]; then
    cp /var/www/html/.env.example /var/www/html/.env
fi

if [ -z "${APP_KEY}" ]; then
    php artisan key:generate --force
fi

# 5. Run migrations cleanly
php artisan migrate --force

# 6. Seed initial factory data ONLY on first empty boot (never overwrites client edits)
php -r "require 'vendor/autoload.php'; \$app = require 'bootstrap/app.php'; \$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap(); exit(\App\Models\Party::count() === 0 ? 0 : 1);" && php artisan db:seed --force || true

# 7. Cache config, routes, and views for fast production response
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

echo "[BK PACKAGES ERP] Starting Apache on port ${PORT}..."
exec apache2-foreground
