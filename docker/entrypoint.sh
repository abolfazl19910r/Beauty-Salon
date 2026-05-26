#!/bin/sh
# ============================================================
# Beauty Salon - Docker Entrypoint
# اجرای migration، cache، symlink و سپس start سرویس‌ها
# ============================================================

set -e

echo "🌸 Beauty Salon - Starting..."
echo "Environment: ${APP_ENV:-production}"

# ─── Wait for MySQL ───────────────────────────────────────────
echo "⏳ Waiting for MySQL..."
MAX_TRIES=30
COUNT=0
until php -r "
    try {
        \$pdo = new PDO(
            'mysql:host=${DB_HOST:-mysql};port=${DB_PORT:-3306};dbname=${DB_DATABASE:-beauty_salon}',
            '${DB_USERNAME:-beauty_user}',
            '${DB_PASSWORD:-secret}'
        );
        echo 'connected';
    } catch (Exception \$e) {
        exit(1);
    }
" 2>/dev/null; do
    COUNT=$((COUNT + 1))
    if [ $COUNT -ge $MAX_TRIES ]; then
        echo "❌ MySQL connection failed after ${MAX_TRIES} attempts"
        exit 1
    fi
    echo "   MySQL not ready yet... ($COUNT/$MAX_TRIES)"
    sleep 2
done
echo "✅ MySQL is ready"

# ─── Wait for Redis ───────────────────────────────────────────
echo "⏳ Waiting for Redis..."
COUNT=0
until redis-cli -h "${REDIS_HOST:-redis}" -p "${REDIS_PORT:-6379}" ping 2>/dev/null | grep -q PONG; do
    COUNT=$((COUNT + 1))
    if [ $COUNT -ge 15 ]; then
        echo "⚠️  Redis not available, continuing anyway..."
        break
    fi
    echo "   Redis not ready yet... ($COUNT/15)"
    sleep 2
done
echo "✅ Redis is ready"

# ─── Storage symlink ─────────────────────────────────────────
echo "🔗 Creating storage symlink..."
php artisan storage:link --force 2>/dev/null || true

# ─── Run Migrations ──────────────────────────────────────────
echo "🗄️  Running migrations..."
php artisan migrate --force --no-interaction

# ─── Cache (Production only) ─────────────────────────────────
if [ "${APP_ENV}" = "production" ]; then
    echo "⚡ Caching config, routes, views..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache
else
    echo "🛠️  Development mode - skipping cache"
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
fi

# ─── Telescope (فقط در development) ─────────────────────────
if [ "${APP_ENV}" != "production" ] && [ "${TELESCOPE_ENABLED}" = "true" ]; then
    echo "🔭 Running Telescope migrations..."
    php artisan telescope:install --no-interaction 2>/dev/null || true
fi

# ─── PHP-FPM socket directory ─────────────────────────────────
mkdir -p /var/run/php-fpm

# ─── Start Supervisor ─────────────────────────────────────────
echo "🚀 Starting services (PHP-FPM + Nginx)..."
exec supervisord -c /etc/supervisor/conf.d/supervisord.conf
