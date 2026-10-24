#!/bin/sh
# ============================================================
# Mahru — entrypoint حالت تک‌کانتینری (target «single»، بسته‌ی Docker ۲۰۲۶-۱۰-۲۳)
#
# ۱. پوشه‌های volume /data (دیتابیس، storage/app، لاگ‌ها) و symlinkهای storage
# ۲. APP_KEY و رمز دیتابیس: اگر در .env.single خالی‌اند، یک بار ساخته و در /data نگه داشته می‌شوند (با پاک شدن کانتینر عوض نشوند)
# ۳. MariaDB: بار اول mariadb-install-db؛ هر بار یک اجرای موقت برای ساخت دیتابیس/کاربر، migrate و کش‌ها، بعد خاموش
# ۴. supervisord همه را بالا می‌آورد: mariadb، php-fpm، nginx، دو worker صف، scheduler، پشتیبان شبانه
#
# با آرگومان (مثلاً «docker compose exec app …» نه، بلکه «docker run … image php artisan …») همان دستور اجرا می‌شود.
# ============================================================
set -e

APP_DIR=/var/www/html
DATA=/data
SOCK=/run/mysqld/mysqld.sock

cd "$APP_DIR"

as_www() { su-exec www "$@"; }
artisan() { su-exec www php artisan "$@"; }

echo "🌸 Mahru (single container) - starting, env: ${APP_ENV:-production}"

# ─── ۱. volume /data ─────────────────────────────────────────
mkdir -p "$DATA/mysql" "$DATA/storage/app" "$DATA/logs" "$DATA/backups" /run/mysqld /var/run/php-fpm /var/log/supervisor
chown -R mysql:mysql "$DATA/mysql" /run/mysqld
touch "$DATA/logs/mariadb-slow.log" && chown mysql:mysql "$DATA/logs/mariadb-slow.log"

# بار اول: محتوای storage/app خود image (پوشه‌های public/private و .gitignoreها) به volume
if [ ! -L storage/app ]; then
    cp -an storage/app/. "$DATA/storage/app/" 2>/dev/null || true
    rm -rf storage/app
    ln -s "$DATA/storage/app" storage/app
fi
if [ ! -L storage/logs ]; then
    rm -rf storage/logs
    ln -s "$DATA/logs" storage/logs
fi
mkdir -p "$DATA/storage/app/public" "$DATA/storage/app/private" storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache
chown -R www:www "$DATA/storage" storage/framework bootstrap/cache
chown www:www "$DATA/logs"
chmod 775 "$DATA/logs"
# پشتیبان‌ها (روی سرور پوشه‌ی میزبان، برای دانلود): فقط www — دیتابیس، عکس مشتری‌ها و APP_KEY
chown www:www "$DATA/backups"
chmod 700 "$DATA/backups"

# ─── ۲. کلیدها ───────────────────────────────────────────────
if [ -z "$APP_KEY" ]; then
    if [ ! -s "$DATA/app.key" ]; then
        php -r 'echo "base64:".base64_encode(random_bytes(32));' > "$DATA/app.key"
        chmod 600 "$DATA/app.key"
        echo "🔑 APP_KEY ساخته شد و در /data/app.key ماند (از آن بکاپ بگیرید)"
    fi
    # www (php و پشتیبان شبانه) همین کلید را در .env هم دارد؛ خواندنش برای www مجوز تازه‌ای نیست
    chown www:www "$DATA/app.key"
    APP_KEY=$(cat "$DATA/app.key")
    export APP_KEY
fi
if [ -z "$DB_PASSWORD" ]; then
    if [ ! -s "$DATA/db.password" ]; then
        php -r 'echo bin2hex(random_bytes(16));' > "$DATA/db.password"
        chmod 600 "$DATA/db.password"
    fi
    DB_PASSWORD=$(cat "$DATA/db.password")
    export DB_PASSWORD
fi
DB_DATABASE=${DB_DATABASE:-mahru}
DB_USERNAME=${DB_USERNAME:-mahru}
export DB_DATABASE DB_USERNAME

# دستورهای «docker compose exec … php artisan» از این entrypoint ارث نمی‌برند (env فقط برای همین درخت پردازه export شده)؛
# در APP_ENV=local که config کش نمی‌شود، بی این فایل artisan کلید و رمز دیتابیس را نمی‌دید. متغیرهای واقعی محیط بر .env مقدم‌اند.
printf 'APP_KEY=%s\nDB_PASSWORD=%s\n' "$APP_KEY" "$DB_PASSWORD" > "$APP_DIR/.env"
chown www:www "$APP_DIR/.env"
chmod 600 "$APP_DIR/.env"

# ─── ۳. MariaDB ──────────────────────────────────────────────
FIRST_RUN=0
if [ ! -d "$DATA/mysql/mysql" ]; then
    echo "🗄️  First run: initializing MariaDB in /data/mysql..."
    mariadb-install-db --user=mysql --datadir="$DATA/mysql" --auth-root-authentication-method=socket --skip-test-db >/dev/null
    FIRST_RUN=1
fi

echo "⏳ Starting MariaDB (setup)..."
su-exec mysql mariadbd --skip-networking=0 --bind-address=127.0.0.1 >/dev/null 2>&1 &
i=0
until mariadb-admin --socket="$SOCK" ping --silent >/dev/null 2>&1; do
    i=$((i + 1))
    if [ "$i" -ge 60 ]; then echo "❌ MariaDB did not start"; exit 1; fi
    sleep 1
done

# دیتابیس و کاربر (هر بار: اگر رمز در .env.single عوض شد، همین‌جا هماهنگ می‌شود)
mariadb --socket="$SOCK" -uroot <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_DATABASE}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USERNAME}'@'127.0.0.1' IDENTIFIED BY '${DB_PASSWORD}';
CREATE USER IF NOT EXISTS '${DB_USERNAME}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';
ALTER USER '${DB_USERNAME}'@'127.0.0.1' IDENTIFIED BY '${DB_PASSWORD}';
ALTER USER '${DB_USERNAME}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';
GRANT ALL PRIVILEGES ON \`${DB_DATABASE}\`.* TO '${DB_USERNAME}'@'127.0.0.1';
GRANT ALL PRIVILEGES ON \`${DB_DATABASE}\`.* TO '${DB_USERNAME}'@'localhost';
FLUSH PRIVILEGES;
SQL

# ─── Laravel ─────────────────────────────────────────────────
artisan storage:link --force >/dev/null 2>&1 || true
echo "🗄️  Running migrations..."
artisan migrate --force --no-interaction

# داده‌ی نمونه فقط بار اول و فقط اگر خواسته شده (MAHRU_SEED=true در .env.single — برای آزمون لوکال، نه سرور واقعی)
if [ "${MAHRU_SEED}" = "true" ] && [ ! -f "$DATA/.seeded" ]; then
    echo "🌱 Seeding demo data (MAHRU_SEED=true)..."
    if artisan db:seed --force --no-interaction; then
        touch "$DATA/.seeded"
    else
        echo "⚠️  Seeding failed — the app still starts; see the error above (next start tries again)"
    fi
fi

artisan salons:analyze-logos --no-interaction >/dev/null 2>&1 || true

if [ "${APP_ENV}" = "production" ]; then
    echo "⚡ Caching config, routes, views, events..."
    artisan config:cache
    artisan route:cache
    artisan view:cache
    artisan event:cache
else
    artisan optimize:clear >/dev/null
fi

# دستور دلخواه (یک بار) به‌جای سرویس‌ها
if [ "$#" -gt 0 ]; then
    as_www "$@"
    status=$?
    mariadb-admin --socket="$SOCK" -uroot shutdown
    exit $status
fi

# nginx: real_ip از TRUSTED_PROXIES، HTTPS اگر /data/tls گواهی دارد (artisan هنوز به MariaDB موقت دسترسی دارد)
/usr/local/bin/mahru-nginx-setup || exit 1

echo "⏹️  Stopping setup MariaDB..."
mariadb-admin --socket="$SOCK" -uroot shutdown
i=0
while [ -S "$SOCK" ] && [ "$i" -lt 30 ]; do i=$((i + 1)); sleep 1; done

# supervisord با کاربر root اجرا می‌شود و هر برنامه را با کاربر خودش (mysql، www) بالا می‌آورد
echo "🚀 Starting services (MariaDB + PHP-FPM + Nginx + queue workers + scheduler)..."
exec supervisord -c /etc/supervisor/conf.d/supervisord-single.conf
