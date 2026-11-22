#!/usr/bin/env bash
# ============================================================
# mahru — دستورهای روزمره‌ی سرور در یک جا (۲۰۲۶-۱۱-۱۱؛ راهنما: docs/deployment/ARVAN_SERVER.md بخش ۱۳)
#
# نصب (یک بار، روی سرور):  chmod +x /srv/mahru/deploy/single/mahru.sh && ln -sf /srv/mahru/deploy/single/mahru.sh /usr/local/bin/mahru
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
    local count=0
    for d in "$DIR"/backups/20*; do
        [[ -d "$d" ]] || continue
        count=$((count + 1))
        printf '%s  %-6s %s\n' "$(basename "$d")" "$(du -sh "$d" | cut -f1)" "$(cd "$d" && ls -1 | tr '\n' ' ')"
    done
    [[ $count -gt 0 ]] || echo "⚠️  هیچ پشتیبانی روی سرور نیست — mahru backup"
    echo "── $count پشتیبان روی سرور (شبانه؛ قدیمی‌ها خودکار پاک می‌شوند). کپی هفتگی ویندوز: C:\\Users\\<شما>\\MahruBackups"
}

# ── لاگ nginx (۲۰۲۶-۱۱-۲۲) — درخواستی که nginx رد می‌کند (۴۰۸، ۴۹۹، ۴۱۳) به PHP نمی‌رسد و در «mahru logs» نیست.
# access: JSON روزانه‌ی ماندگار در volume (/data/logs/nginx/access-<تاریخ UTC>.log)؛ error: /var/log/nginx/error.log (با ساختن
# دوباره‌ی کانتینر از نو شروع می‌شود). فیلتر و تعداد با -e به کانتینر می‌رود، نه درون متن sh (هیچ نویسه‌ای تفسیر نمی‌شود).
# فیلتری که با / شروع شود فقط با ابتدای مسیر درخواست مقایسه می‌شود (نه referrer)؛ هر متن دیگر (POST، IP، 499) همه‌جای خط
NGINX_LATEST='f=$(ls -1t /data/logs/nginx/access-*.log 2>/dev/null | head -n 1); [ -n "$f" ] || { echo "لاگ access ‏nginx در /data/logs/nginx نیست" >&2; exit 1; }; case "${MAHRU_FILTER:-}" in /*) MAHRU_FILTER="\"uri\":\"$MAHRU_FILTER" ;; esac;'

# یک خط JSON ← «11-21 19:55:33  408  POST /superadmin/app-releases  rt=32.934s  0B  204.18.243.15»؛ خط ناآشنا همان‌طور می‌ماند
nginx_compact() {
    sed -u -E 's/^\{"time":"[0-9]{4}-([0-9-]+)T([0-9:]+)[^"]*","ip":"([^"]*)".*"method":"([^"]*)","uri":"([^"]*)","status":([0-9]+),"bytes":([0-9]+),"rt":([0-9.]+).*/\1 \2  \6  \4 \5  rt=\8s  \7B  \3/'
}

cmd_nginx() {
    local mode=access n=30 filter=""
    case "${1:-}" in
        errors|error|err) mode=errors; n=20; shift ;;
        follow|tail|-f) mode=follow; shift ;;
        codes|status) mode=codes; shift ;;
    esac
    local a
    for a in "$@"; do
        if [[ "$a" =~ ^[0-9]+$ ]]; then n="$a"; else filter="$a"; fi
    done
    case "$mode" in
        access)
            say "docker exec $C sh -c 'grep -F \"$filter\" /data/logs/nginx/access-\$(date -u +%F).log | tail -n $n'"
            docker exec -e MAHRU_N="$n" -e MAHRU_FILTER="$filter" "$C" sh -c \
                "$NGINX_LATEST"' grep -F -- "$MAHRU_FILTER" "$f" | tail -n "$MAHRU_N"' | nginx_compact
            ;;
        follow)
            say "docker exec $C sh -c 'tail -F /data/logs/nginx/access-\$(date -u +%F).log' | grep \"$filter\"   (خروج: Ctrl+C)"
            [[ "$filter" == /* ]] && filter="\"uri\":\"$filter"
            docker exec "$C" sh -c "$NGINX_LATEST"' tail -n 5 -F "$f"' | grep --line-buffered -F -- "$filter" | nginx_compact
            ;;
        codes)
            say "docker exec $C sh -c 'grep -F \"$filter\" /data/logs/nginx/access-\$(date -u +%F).log | grep -o status | sort | uniq -c'"
            docker exec -e MAHRU_FILTER="$filter" "$C" sh -c \
                "$NGINX_LATEST"' echo "── $f"; grep -F -- "$MAHRU_FILTER" "$f" | grep -o "\"status\":[0-9]*" | cut -d: -f2 | sort | uniq -c | sort -rn'
            echo "۲۰۰/۳۰۲ = موفق، ۴۰۸ = nginx ‏۳۰ ثانیه داده ندید، ۴۹۹ = مرورگر/اینترنت کاربر اتصال را بست، ۴۱۳ = بزرگ‌تر از سقف، ۵xx = خطای برنامه (mahru errors)"
            ;;
        errors)
            say "docker exec $C tail -n $n /var/log/nginx/error.log"
            docker exec -e MAHRU_N="$n" -e MAHRU_FILTER="$filter" "$C" sh -c \
                'grep -F -- "$MAHRU_FILTER" /var/log/nginx/error.log | tail -n "$MAHRU_N" | cut -c1-400'
            ;;
    esac
}

# ── imageها و deploy (۲۰۲۶-۱۱-۲۲) — همان server-deploy.sh، فقط کوتاه‌تر
cmd_images() {
    local cur run_img
    cur="$(grep -E '^MAHRU_VERSION=' "$DIR/.env" 2>/dev/null | cut -d= -f2)"
    run_img="$(docker inspect -f '{{.Config.Image}}' "$C" 2>/dev/null || echo '-')"
    echo "── imageهای load‌شده روی سرور"
    say "docker image ls mahru"
    docker image ls mahru --format '{{.Tag}}|{{.CreatedSince}}|{{.Size}}' | grep '^single-' | while IFS='|' read -r tag since size; do
        local v="${tag#single-}"
        printf '%-20s %-18s %s%s\n' "$v" "$since" "$size" "$([[ "$v" == "$cur" ]] && echo '   ← در حال اجرا')"
    done
    echo "── فایل‌های tar در $DIR/images"
    local t found=0
    for t in "$DIR"/images/*.tar; do
        [[ -f "$t" ]] || continue
        found=1
        printf '%-6s %s\n' "$(du -h "$t" | cut -f1)" "$(basename "$t")"
    done
    [[ $found -eq 1 ]] || echo "(هیچ)"
    echo "── کانتینر: $run_img   (MAHRU_VERSION=${cur:--})"
    df -h / | awk 'NR==2{print "دیسک: " $3 " از " $2 " (" $5 ")"}'
    echo "قاعده: image فعلی + یکی قبل (برای mahru rollback) و فقط tar آخر. پاک کردن قدیمی:"
    echo "  docker image rm mahru:single-<نسخه>     rm $DIR/images/mahru-single-<نسخه>.tar"
}

cmd_deploy() {
    local a="${1:-}"
    if [[ -z "$a" ]]; then
        echo "مثال: mahru deploy <نسخه>   یا   mahru deploy images/mahru-single-<نسخه>.tar" >&2
        cmd_images
        return 2
    fi
    local tar="" c
    for c in "$a" "$DIR/$a" "$DIR/images/$a" "$DIR/images/mahru-single-$a.tar"; do
        [[ -f "$c" ]] && { tar="$c"; break; }
    done
    [[ -n "$tar" ]] || { echo "فایل image پیدا نشد: $a — اول با upload-image.ps1 به $DIR/images بفرستید (mahru images)" >&2; return 1; }
    tar="$(cd "$(dirname "$tar")" && pwd)/$(basename "$tar")"
    say "cd $DIR && bash deploy/single/server-deploy.sh $tar"
    (cd "$DIR" && bash deploy/single/server-deploy.sh "$tar") || return 1
    echo "بعد: mahru images (image و tar قدیمی‌تر از «یکی قبل» را پاک کنید)"
}

cmd_rollback() {
    [[ -n "${1:-}" ]] || { cmd_images; echo "مثال: mahru rollback <نسخه‌ی قبلی>   (migrationهای نسخه‌ی تازه برنمی‌گردند)" >&2; return 2; }
    say "cd $DIR && bash deploy/single/server-deploy.sh --use $1"
    (cd "$DIR" && bash deploy/single/server-deploy.sh --use "$1")
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
  mahru nginx [تعداد] [فیلتر]       آخرین درخواست‌های nginx، کوتاه (پیش‌فرض ۳۰)، مثلاً: mahru nginx 50 /superadmin/app-releases
  mahru nginx follow [فیلتر]        درخواست‌های nginx زنده، مثلاً: mahru nginx follow POST
  mahru nginx codes [فیلتر]         شمارش کدهای امروز (۲۰۰، ۴۰۸، ۴۹۹، …)، مثلاً: mahru nginx codes /superadmin/app-releases
  mahru nginx errors [تعداد]        آخرین خط‌های error.log ‏nginx (پیش‌فرض ۲۰)
  mahru backups          فهرست پشتیبان‌ها (اندازه و فایل‌ها)
  mahru backup           همین الان یک پشتیبان رمزنگاری‌شده
  mahru restart          ری‌استارت کانتینر و صبر تا healthy
  mahru recreate         بعد از تغییر .env.server: کانتینر از نو (داده‌ها می‌مانند)
  mahru artisan …        دستور artisan، مثلاً: mahru artisan superadmin:create
  mahru shell            خط فرمان داخل کانتینر (کاربر www)
  mahru cert             گواهی HTTPS و زمان تمدید
  mahru images           imageها و tarهای روی سرور، نسخه‌ی در حال اجرا، دیسک
  mahru deploy <نسخه>     deploy نسخه‌ی تازه از images/ (پشتیبان، load، up، صبر تا healthy)
  mahru rollback <نسخه>   برگشت به imageی که قبلاً load شده

mahru deploy همان است:  cd /srv/mahru && bash deploy/single/server-deploy.sh images/mahru-single-<کامیت>.tar
HELP
}

case "${1:-help}" in
    status) cmd_status ;;
    logs|log) shift; cmd_logs "$@" ;;
    follow|tail) cmd_follow ;;
    errors|error|err) shift; cmd_errors "$@" ;;
    sms|code) shift; cmd_sms "$@" ;;
    logfiles) cmd_logfiles ;;
    nginx|ng) shift; cmd_nginx "$@" ;;
    images|image) cmd_images ;;
    deploy) shift; cmd_deploy "$@" ;;
    rollback) shift; cmd_rollback "$@" ;;
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
