#!/usr/bin/env bash
# ============================================================
# mahru — دستورهای روزمره‌ی سرور در یک جا (۲۰۲۶-۱۱-۱۱؛ راهنما: docs/deployment/ARVAN_SERVER.md بخش ۱۳)
#
# نصب (یک بار، روی سرور):  ln -sf /srv/mahru/deploy/single/mahru.sh /usr/local/bin/mahru
# بعد:                      mahru            (فهرست دستورها)
#
# فقط چیزهایی را که هر روز لازم است کوتاه می‌کند؛ هر کدام همان دستور docker زیرش را هم چاپ می‌کند تا یاد بماند.
# ============================================================
set -uo pipefail

DIR="${MAHRU_DIR:-/srv/mahru}"
C="${MAHRU_CONTAINER:-mahru}"
COMPOSE=(docker compose -f "$DIR/docker-compose.server.yml")
DOMAIN="${MAHRU_DOMAIN:-arisheh.ir}"

say() { printf '\033[2m$ %s\033[0m\n' "$*" >&2; }
run() { say "$*"; "$@"; }

health() { docker inspect -f '{{if .State.Health}}{{.State.Health.Status}}{{else}}{{.State.Status}}{{end}}' "$C" 2>/dev/null || echo 'نیست'; }

wait_healthy() {
    for _ in $(seq 1 60); do
        [[ "$(health)" == healthy ]] && { echo "✅ $C healthy"; return 0; }
        sleep 5
    done
    echo "⚠️  $C بعد از ۵ دقیقه healthy نشد — mahru logs 10" >&2
    return 1
}

cmd_status() {
    echo "── کانتینر"
    docker ps -a --filter "name=^${C}$" --format '{{.Names}} | {{.Status}} | {{.Image}}'
    echo "── سایت"
    printf 'https://%s/up  →  %s\n' "$DOMAIN" "$(curl -s -o /dev/null -m 10 -w '%{http_code}' "https://$DOMAIN/up")"
    echo "── گواهی"
    if [[ -s "$DIR/tls/fullchain.pem" ]]; then
        END="$(openssl x509 -enddate -noout -in "$DIR/tls/fullchain.pem" | cut -d= -f2)"
        DAYS=$(( ( $(date -d "$END" +%s) - $(date +%s) ) / 86400 ))
        echo "تا $END ($DAYS روز)$([[ $DAYS -lt 20 ]] && echo '  ⚠️ تمدید خودکار انجام نشده: ~/.acme.sh/acme.sh --cron')"
    else
        echo "گواهی نیست (فقط HTTP)"
    fi
    echo "── آخرین پشتیبان"
    LAST="$(ls -1d "$DIR"/backups/20* 2>/dev/null | tail -1)"
    if [[ -n "$LAST" ]]; then
        AGE_H=$(( ( $(date +%s) - $(stat -c %Y "$LAST") ) / 3600 ))
        echo "$(basename "$LAST")  ($(du -sh "$LAST" | cut -f1)، $AGE_H ساعت پیش)$([[ $AGE_H -gt 36 ]] && echo '  ⚠️ بیش از ۳۶ ساعت')"
    else
        echo "⚠️  هیچ پشتیبانی نیست"
    fi
    echo "── دیسک و حافظه"
    df -h / | awk 'NR==2{print "دیسک: " $3 " از " $2 " (" $5 ")"}'
    free -h | awk '/^Mem/{print "رم: " $3 " از " $2}'
    echo "── ساعت"
    timedatectl show -p NTPSynchronized --value | sed 's/yes/NTP همگام ✅/; s/no/⚠️ NTP همگام نیست/'
}

cmd_logs() {
    local m="${1:-30}"
    say "docker logs --since ${m}m $C 2>&1 | tail -100"
    docker logs --since "${m}m" "$C" 2>&1 | tail -100
}

cmd_follow() {
    say "docker logs -f --tail 20 $C   (خروج: Ctrl+C)"
    docker logs -f --tail 20 "$C" 2>&1
}

cmd_errors() {
    local n="${1:-5}"
    say "docker exec $C sh -c 'grep -h production.ERROR /data/logs/laravel-*.log | tail -$n'"
    docker exec "$C" sh -c "grep -h 'production\.ERROR' /data/logs/laravel-*.log 2>/dev/null | tail -n $n | cut -c1-700" \
        || echo "خطایی در لاگ نیست"
}

cmd_sms() {
    local m="${1:-15}"
    say "docker logs --since ${m}m $C 2>&1 | grep SMS"
    docker logs --since "${m}m" "$C" 2>&1 | grep -E 'SMS|پیامک' | tail -20 || echo "در ${m} دقیقه‌ی اخیر پیامکی نبود"
}

cmd_logfiles() {
    say "docker exec $C ls -lh /data/logs"
    docker exec "$C" ls -lh /data/logs
}

cmd_backups() {
    say "ls -lh $DIR/backups"
    for d in "$DIR"/backups/20*; do
        [[ -d "$d" ]] && printf '%s  %s\n' "$(basename "$d")" "$(du -sh "$d" | cut -f1)"
    done
}

cmd_backup() {
    run docker exec -u www "$C" mahru-backup now
}

cmd_restart() {
    run docker restart "$C" >/dev/null
    wait_healthy
}

cmd_recreate() {
    # بعد از تغییر .env.server: کانتینر از نو با همان image (داده‌ها در volume می‌مانند)
    say "${COMPOSE[*]} up -d --force-recreate"
    "${COMPOSE[@]}" up -d --force-recreate
    wait_healthy
}

cmd_artisan() {
    [[ $# -gt 0 ]] || { echo "مثال: mahru artisan superadmin:create" >&2; return 2; }
    run docker exec -it -u www "$C" php artisan "$@"
}

cmd_shell() {
    run docker exec -it -u www "$C" sh
}

cmd_cert() {
    run ~/.acme.sh/acme.sh --list
    openssl x509 -noout -subject -ext subjectAltName -dates -in "$DIR/tls/fullchain.pem"
}

cmd_help() {
    cat <<'HELP'
mahru — دستورهای روزمره‌ی سرور ماهرو

  mahru status           وضعیت کلی: کانتینر، سایت، گواهی، آخرین پشتیبان، دیسک، ساعت
  mahru logs [دقیقه]      لاگ کانتینر در N دقیقه‌ی اخیر (پیش‌فرض ۳۰)
  mahru follow           لاگ زنده (Ctrl+C برای خروج)
  mahru errors [تعداد]    آخرین خطاهای برنامه (Laravel؛ پیش‌فرض ۵)
  mahru sms [دقیقه]       پیامک‌ها / کد ورود (تا وقتی SMS_LOG_ONLY=true؛ پیش‌فرض ۱۵ دقیقه)
  mahru logfiles         فهرست فایل‌های لاگ (laravel، security، payments)
  mahru backups          فهرست پشتیبان‌ها
  mahru backup           همین الان یک پشتیبان رمزنگاری‌شده
  mahru restart          ری‌استارت کانتینر و صبر تا healthy
  mahru recreate         بعد از تغییر .env.server: کانتینر از نو (داده‌ها می‌مانند)
  mahru artisan …        دستور artisan، مثلاً: mahru artisan superadmin:create
  mahru shell            خط فرمان داخل کانتینر (کاربر www)
  mahru cert             گواهی HTTPS و زمان تمدید

deploy نسخه‌ی تازه همان است:  cd /srv/mahru && bash deploy/single/server-deploy.sh images/mahru-single-<کامیت>.tar
HELP
}

case "${1:-help}" in
    status) cmd_status ;;
    logs|log) shift; cmd_logs "$@" ;;
    follow|tail) cmd_follow ;;
    errors|error|err) shift; cmd_errors "$@" ;;
    sms|code) shift; cmd_sms "$@" ;;
    logfiles) cmd_logfiles ;;
    backups) cmd_backups ;;
    backup) cmd_backup ;;
    restart) cmd_restart ;;
    recreate) cmd_recreate ;;
    artisan) shift; cmd_artisan "$@" ;;
    shell|sh) cmd_shell ;;
    cert|tls) cmd_cert ;;
    help|-h|--help) cmd_help ;;
    *) echo "دستور ناشناخته: $1" >&2; cmd_help; exit 2 ;;
esac
