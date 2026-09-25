#!/bin/sh
# ============================================================
# Beauty Salon (Mahru) - Docker Entrypoint
#
# دو نقش، با یک image:
#   بدون آرگومان (سرویس app)   → نقش web: migrate + cache + php-fpm + nginx (supervisord)
#   با آرگومان (queue/scheduler) → نقش worker: فقط cache، بعد همون دستور (مثلاً
#                                   «php artisan queue:work» یا «php artisan schedule:work») با کاربر www
#
# ⚠️ قبلاً این فایل آرگومان‌ها رو نادیده می‌گرفت و همیشه supervisord (وب‌سرور) رو اجرا می‌کرد؛ یعنی
# containerهای queue و scheduler در عمل وب‌سرور دوم و سوم بودن و queue:work / schedule:run هیچ‌وقت اجرا
# نمی‌شد. هر سه هم هم‌زمان migrate می‌زدن. حالا فقط app migrate می‌کنه و بقیه منتظر healthy شدنش می‌مونن.
# ============================================================

set -e

ROLE=web
if [ "$#" -gt 0 ]; then
    ROLE=worker
fi

# دستورهای artisan با کاربر www اجرا می‌شن تا فایل‌های log/cache/view مال www باشن (php-fpm با www اجرا
# می‌شه؛ فایلی که root بسازه، بعداً برای وب‌سرور غیرقابل‌نوشتن می‌شه).
RUN_AS=""
if [ "$(id -u)" = "0" ] && command -v su-exec >/dev/null 2>&1; then
    RUN_AS="su-exec www"
fi

artisan() {
    $RUN_AS php artisan "$@"
}

echo "🌸 Mahru - starting (role: ${ROLE}, env: ${APP_ENV:-production})"

# مالکیت پوشه‌های قابل‌نوشتن (volumeها ممکنه با root ساخته شده باشن). storage/app عمداً نه: می‌تونه خیلی بزرگ باشه.
if [ "$(id -u)" = "0" ]; then
    mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache
    chown -R www:www storage/logs storage/framework bootstrap/cache 2>/dev/null || true
fi

# ─── Wait for MySQL ───────────────────────────────────────────
echo "⏳ Waiting for MySQL..."
MAX_TRIES=30
COUNT=0
until php -r "
    try {
        new PDO(
            'mysql:host=${DB_HOST:-mysql};port=${DB_PORT:-3306};dbname=${DB_DATABASE:-beauty_salon}',
            '${DB_USERNAME:-beauty_user}',
            '${DB_PASSWORD:-secret}'
        );
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
# با افزونه‌ی phpredis (redis-cli در image نصب نیست؛ نسخه‌ی قبلی هر بار ۳۰ ثانیه بی‌هوده منتظر می‌موند).
echo "⏳ Waiting for Redis..."
COUNT=0
until php -r "
    try {
        \$r = new Redis();
        \$r->connect('${REDIS_HOST:-redis}', (int) '${REDIS_PORT:-6379}', 1.0);
        \$password = getenv('REDIS_PASSWORD');
        if (\$password !== false && \$password !== '' && \$password !== 'null') {
            \$r->auth(\$password);
        }
        exit(\$r->ping() ? 0 : 1);
    } catch (Throwable \$e) {
        exit(1);
    }
" 2>/dev/null; do
    COUNT=$((COUNT + 1))
    if [ $COUNT -ge 15 ]; then
        echo "⚠️  Redis not available, continuing anyway..."
        break
    fi
    echo "   Redis not ready yet... ($COUNT/15)"
    sleep 2
done

# ─── Web role only: storage link + migrations ────────────────
if [ "$ROLE" = "web" ]; then
    echo "🔗 Creating storage symlink..."
    artisan storage:link --force 2>/dev/null || true

    echo "🗄️  Running migrations..."
    artisan migrate --force --no-interaction
fi

# ─── Cache (Production only) — هر container فایل‌های cache خودش رو داره ─────
if [ "${APP_ENV}" = "production" ]; then
    echo "⚡ Caching config, routes, views, events..."
    artisan config:cache
    artisan route:cache
    artisan view:cache
    artisan event:cache
else
    echo "🛠️  Development mode - clearing caches"
    artisan config:clear
    artisan route:clear
    artisan view:clear
fi

# ─── Worker role: exec the given command as www ──────────────
if [ "$ROLE" = "worker" ]; then
    echo "🚀 Running: $*"
    exec $RUN_AS "$@"
fi

# ─── Telescope (فقط در development) ─────────────────────────
if [ "${APP_ENV}" != "production" ] && [ "${TELESCOPE_ENABLED}" = "true" ]; then
    echo "🔭 Running Telescope migrations..."
    artisan telescope:install --no-interaction 2>/dev/null || true
fi

# ─── PHP-FPM socket directory + supervisord log directory ─────
mkdir -p /var/run/php-fpm /var/log/supervisor

# ─── Start Supervisor ─────────────────────────────────────────
echo "🚀 Starting services (PHP-FPM + Nginx)..."
exec supervisord -c /etc/supervisor/conf.d/supervisord.conf
