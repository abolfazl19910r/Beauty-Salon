#!/usr/bin/env bash
# ============================================================
# Mahru — بالا آوردن یا به‌روزرسانی سرور واقعی با image منتقل‌شده از ویندوز (۲۰۲۶-۱۰-۲۴)
#
#   bash deploy/single/server-deploy.sh images/mahru-single-<نسخه>.tar   ← نسخه‌ی تازه (load، پشتیبان، up)
#   bash deploy/single/server-deploy.sh --use <نسخه>                     ← برگشت به نسخه‌ای که قبلاً load شده
#   bash deploy/single/server-deploy.sh --list                            ← نسخه‌های موجود روی سرور
#
# از پوشه‌ی /srv/mahru اجرا شود (کنار docker-compose.server.yml و .env.server). راهنما: docs/deployment/ARVAN_SERVER.md
# ⚠️ برگشت (--use) migrationهای نسخه‌ی تازه را برنمی‌گرداند؛ اگر نسخه‌ی تازه migration داشت، از پشتیبان پیش از deploy
#    (backups/) بازگردانی کنید (docs/deployment/BACKUP.md).
# ============================================================
set -euo pipefail

DIR="$(pwd)"
COMPOSE=(docker compose -f "$DIR/docker-compose.server.yml")

die() { echo "❌ $*" >&2; exit 1; }

[[ -f "$DIR/docker-compose.server.yml" ]] || die "docker-compose.server.yml اینجا نیست — از /srv/mahru اجرا کنید"
[[ -f "$DIR/.env.server" ]] || die ".env.server نیست — از .env.server.example بسازید (راهنما)"
chmod 600 "$DIR/.env.server"
mkdir -p "$DIR/backups" "$DIR/images"

env_server() { grep -E "^$1=" "$DIR/.env.server" | tail -n 1 | cut -d= -f2- | tr -d '"' || true; }

if [[ -z "$(env_server TRUSTED_PROXIES)" ]]; then
    echo "ℹ️  TRUSTED_PROXIES در .env.server خالی است = حالت «بدون CDN» (IP مستقیم واقعی است). پشت CDN آروان IP لبه‌ها دیده می‌شود"
    echo "    (throttle ورود بین همه مشترک می‌شود) — اول: bash deploy/single/arvan-ips.sh"
fi
if [[ "$(env_server SMS_LOG_ONLY)" != "true" ]]; then
    echo "⚠️  SMS_LOG_ONLY=true نیست — پیامک‌ها واقعاً به کاوه‌نگار فرستاده می‌شوند."
fi

case "${1:-}" in
    --list)
        docker image ls mahru --format '{{.Tag}}\t{{.CreatedSince}}\t{{.Size}}' | grep '^single-' | sed 's/^single-//' || true
        echo "در حال اجرا: $(grep -E '^MAHRU_VERSION=' "$DIR/.env" 2>/dev/null | cut -d= -f2 || echo '-')"
        exit 0
        ;;
    --use)
        VERSION="${2:-}"
        [[ -n "$VERSION" ]] || die "نسخه را بدهید: --use <نسخه> (فهرست: --list)"
        docker image inspect "mahru:single-$VERSION" >/dev/null 2>&1 || die "image mahru:single-$VERSION روی سرور نیست"
        ;;
    "")
        die "فایل image را بدهید: bash deploy/single/server-deploy.sh images/mahru-single-<نسخه>.tar"
        ;;
    *)
        TAR="$1"
        [[ -f "$TAR" ]] || die "فایل $TAR پیدا نشد"
        echo "📦 docker load $TAR ..."
        LOADED="$(docker load -i "$TAR" | sed -n 's/^Loaded image: //p' | tail -n 1)"
        [[ "$LOADED" == mahru:single-* ]] || die "این فایل image ‏mahru:single-<نسخه> نیست (بار شد: ${LOADED:-هیچ})"
        VERSION="${LOADED#mahru:single-}"
        ;;
esac

# پشتیبان پیش از عوض کردن نسخه (اگر کانتینر قبلی بالاست)
if [[ "$(docker inspect -f '{{.State.Running}}' mahru 2>/dev/null || true)" == "true" ]]; then
    echo "💾 پشتیبان پیش از deploy ..."
    docker exec -u www mahru mahru-backup now || die "پشتیبان پیش از deploy شکست خورد — deploy انجام نشد"
fi

echo "MAHRU_VERSION=$VERSION" > "$DIR/.env"
echo "🚀 نسخه‌ی $VERSION ..."
"${COMPOSE[@]}" up -d

echo "⏳ منتظر healthy (migrate بار اول تا چند دقیقه) ..."
for _ in $(seq 1 60); do
    STATE="$(docker inspect -f '{{if .State.Health}}{{.State.Health.Status}}{{else}}{{.State.Status}}{{end}}' mahru 2>/dev/null || echo '?')"
    case "$STATE" in
        healthy)
            docker exec mahru supervisorctl status || true
            echo "✅ نسخه‌ی $VERSION بالا آمد. بررسی: curl -sI http://127.0.0.1/up"
            exit 0
            ;;
        unhealthy|exited|dead)
            break
            ;;
    esac
    sleep 5
done

echo "❌ کانتینر healthy نشد (وضعیت: $STATE). آخرین لاگ‌ها:" >&2
docker logs --tail 80 mahru >&2 || true
echo "برگشت به نسخه‌ی قبلی: bash deploy/single/server-deploy.sh --list  و  --use <نسخه‌ی قبلی>" >&2
exit 1
