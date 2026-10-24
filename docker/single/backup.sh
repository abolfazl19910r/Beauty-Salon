#!/bin/sh
# ============================================================
# Mahru — پشتیبان داخل کانتینر تک‌کانتینری (۲۰۲۶-۱۰-۲۴)
#
#   mahru-backup now    یک بار، همین حالا (دستی: docker exec -u www mahru mahru-backup now)
#   mahru-backup loop   برنامه‌ی supervisord «backup»: هر روز ساعت BACKUP_AT به وقت APP_TIMEZONE
#
# خروجی: /data/backups/<تاریخ>/ — database.sql.gz، storage-app.tar.gz (course-photos، public، private) و app.key
# فقط BACKUP_KEEP پشتیبان آخر می‌ماند (پیش‌فرض ۳). روی سرور، /data/backups پوشه‌ی میزبان است تا با WinSCP دانلود شود.
# storage/app/backup-status.json را می‌نویسد (داشبورد ابرمدیر «آخرین پشتیبان‌گیری»).
# BACKUP_AT خالی یا off = پشتیبان خودکار خاموش (لوکال)؛ «now» همیشه کار می‌کند.
# ============================================================
set -eu

run_once() {
    KEEP_LAST="${BACKUP_KEEP:-3}" BACKUP_APP_KEY_FILE=/data/app.key \
        bash /var/www/html/deploy/backup/mahru-backup.sh /var/www/html /data/backups
}

case "${1:-now}" in
    now)
        run_once
        ;;
    loop)
        at="${BACKUP_AT:-}"
        if [ -z "$at" ] || [ "$at" = "off" ]; then
            echo "backup: BACKUP_AT is empty — automatic backup is off"
            exec sleep infinity
        fi
        echo "backup: daily at $at (${APP_TIMEZONE:-Asia/Tehran}), keeping the last ${BACKUP_KEEP:-3}"
        last=""
        while true; do
            now="$(TZ="${APP_TIMEZONE:-Asia/Tehran}" date +%H:%M)"
            today="$(TZ="${APP_TIMEZONE:-Asia/Tehran}" date +%F)"
            if [ "$now" = "$at" ] && [ "$last" != "$today" ]; then
                last="$today"
                run_once || echo "backup: failed (see storage/app/backup-status.json)" >&2
            fi
            sleep 20
        done
        ;;
    *)
        echo "usage: mahru-backup [now|loop]" >&2
        exit 2
        ;;
esac
