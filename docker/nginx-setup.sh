#!/bin/sh
# ============================================================
# Mahru — آماده‌سازی nginx پیش از supervisord (هر دو entrypoint؛ بسته‌ی مجوز 5د، امنیت مستقل از CDN)
#
# ۱. conf.d/00-real-ip.conf از TRUSTED_PROXIES (php artisan security:nginx-real-ip): IP واقعی در لاگ دسترسی فقط از لبه‌های
#    مورد اعتماد؛ خالی = بدون CDN، IP مستقیم.
# ۲. HTTPS روی خود مبدا: اگر /data/tls/fullchain.pem و privkey.pem هست، conf.d/tls.conf (پورت ۴۴۳) روشن و پورت ۸۰ به HTTPS
#    فرستاده می‌شود (جز /up، چالش ACME و درخواست لبه‌ی مورد اعتماد که خودش HTTPS گرفته).
# اگر artisan خطا داد: فایل امن «بی‌اعتماد به پروکسی» (nginx باید حتماً بالا بیاید).
# ============================================================
set -u
RUN_AS=${RUN_AS-su-exec www}
APP_DIR=${APP_DIR:-/var/www/html}
CONF=${NGINX_CONF_D:-/etc/nginx/conf.d}
MAHRU=${NGINX_MAHRU_DIR:-/etc/nginx/mahru}
TLS_DIR=${TLS_DIR:-/data/tls}
NGINX_TEST=${NGINX_TEST:-nginx -t -q}

if ! (cd "$APP_DIR" && $RUN_AS php artisan security:nginx-real-ip) > "$CONF/00-real-ip.conf.new" 2>/dev/null; then
    echo "⚠️  security:nginx-real-ip خطا داد — nginx بدون اعتماد به هیچ پروکسی بالا می‌آید"
    printf 'geo $realip_remote_addr $mahru_trusted_proxy {\n    default 0;\n}\n' > "$CONF/00-real-ip.conf.new"
fi
mv "$CONF/00-real-ip.conf.new" "$CONF/00-real-ip.conf"

if [ -s "$TLS_DIR/fullchain.pem" ] && [ -s "$TLS_DIR/privkey.pem" ]; then
    cp "$MAHRU/tls.conf" "$CONF/tls.conf"
    printf 'if ($mahru_needs_https) {\n    return 301 https://$host$request_uri;\n}\n' > "$MAHRU/https-redirect.inc"
    echo "🔒 HTTPS روی مبدا روشن است ($TLS_DIR)"
else
    rm -f "$CONF/tls.conf"
    echo '# بدون گواهی در /data/tls: فقط HTTP (HTTPS در لبه‌ی CDN یا هنوز راه نیفتاده)' > "$MAHRU/https-redirect.inc"
fi

$NGINX_TEST || { echo "❌ پیکربندی nginx نامعتبر است"; exit 1; }
