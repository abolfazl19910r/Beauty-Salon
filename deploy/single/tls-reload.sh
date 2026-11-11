#!/usr/bin/env bash
# ============================================================
# Mahru — بعد از صدور یا تمدید گواهی روی میزبان (acme.sh --reloadcmd؛ ARVAN_SERVER.md بخش ۸الف)
#
#   acme.sh --install-cert ... --reloadcmd "bash /srv/mahru/deploy/single/tls-reload.sh"
#
# ۱. دسترسی فایل‌ها: privkey.pem فقط root (۶۰۰)، fullchain.pem خواندنی. ./tls فقط‌خواندنی در /data/tls کانتینر است.
# ۲. اگر کانتینر بالاست:
#    - nginx از قبل HTTPS دارد (conf.d/tls.conf) ← nginx -t و nginx -s reload (بی قطعی؛ تمدید هر ۶۰ روز).
#    - اولین گواهی ← docker restart: nginx-setup فقط هنگام شروع کانتینر tls.conf و هدایت ۸۰ ← ۴۴۳ را روشن می‌کند.
#      restart (نه up -d --force-recreate) تغییرهای docker cp لایه‌ی کانتینر را نگه می‌دارد.
# ۳. اگر کانتینر بالا نیست: کاری نمی‌کند؛ شروع بعدی گواهی را می‌بیند.
# خروج غیرصفر فقط وقتی nginx گواهی تازه را نپذیرفت (acme.sh خطا را در لاگ و ایمیل نشان می‌دهد)؛ nginx با گواهی قبلی می‌ماند.
# ============================================================
set -euo pipefail

TLS_DIR="${TLS_DIR:-$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)/tls}"
CONTAINER="${MAHRU_CONTAINER:-mahru}"
DOCKER="${DOCKER:-docker}"

[[ -s "$TLS_DIR/fullchain.pem" && -s "$TLS_DIR/privkey.pem" ]] || { echo "❌ گواهی در $TLS_DIR نیست (fullchain.pem و privkey.pem)" >&2; exit 1; }
chmod 700 "$TLS_DIR"
chmod 600 "$TLS_DIR/privkey.pem"
chmod 644 "$TLS_DIR/fullchain.pem"

if [[ "$($DOCKER inspect -f '{{.State.Running}}' "$CONTAINER" 2>/dev/null || true)" != "true" ]]; then
    echo "ℹ️  کانتینر $CONTAINER بالا نیست — شروع بعدی HTTPS را با همین گواهی روشن می‌کند"
    exit 0
fi

if $DOCKER exec "$CONTAINER" test -f /etc/nginx/conf.d/tls.conf; then
    if ! $DOCKER exec "$CONTAINER" nginx -t -q; then
        echo "❌ nginx گواهی تازه را نپذیرفت — همان گواهی قبلی می‌ماند" >&2
        exit 1
    fi
    $DOCKER exec "$CONTAINER" nginx -s reload
    echo "🔒 nginx با گواهی تازه reload شد"
else
    echo "🔒 اولین گواهی — ری‌استارت کانتینر تا nginx پورت ۴۴۳ و هدایت http ← https را روشن کند ..."
    $DOCKER restart "$CONTAINER" >/dev/null
    for _ in $(seq 1 60); do
        STATE="$($DOCKER inspect -f '{{if .State.Health}}{{.State.Health.Status}}{{else}}{{.State.Status}}{{end}}' "$CONTAINER" 2>/dev/null || echo '?')"
        [[ "$STATE" == "healthy" ]] && { echo "✅ $CONTAINER healthy — HTTPS روشن است"; exit 0; }
        sleep 5
    done
    echo "⚠️  $CONTAINER پس از ۵ دقیقه healthy نشد: docker logs --tail 80 $CONTAINER" >&2
    exit 1
fi
