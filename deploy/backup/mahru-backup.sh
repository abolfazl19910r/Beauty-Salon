#!/usr/bin/env bash
# ============================================================
# Mahru — پشتیبان روزانه (DirectAdmin / هر سرور لینوکسی بدون Docker)
# ------------------------------------------------------------
# چه چیزی: ۱) دیتابیس (mysqldump --single-transaction، فشرده)
#          ۲) فایل‌های آپلودی storage/app:
#             course-photos  عکس‌های قبل/بعد دوره‌های درمانی (دیسک خصوصی course_photos — داده‌ی حساس چهره)
#             public         لوگوی سالن، عکس متخصص، گالری، ...
#             private        خروجی گزارش‌ها و فایل‌های خصوصی دیگر
# هر اجرا یک پوشه‌ی تاریخ‌دار؛ پشتیبان‌های قدیمی‌تر از KEEP_DAYS روز پاک می‌شوند. همه‌ی فایل‌ها فقط برای مالک خواندنی‌اند
# (umask 077) چون عکس‌های دوره و دیتابیس داده‌ی شخصی مشتری‌اند — پوشه‌ی پشتیبان هرگز زیر public_html نباشد.
#
# اجرا:   bash deploy/backup/mahru-backup.sh [مسیر برنامه] [مسیر پشتیبان]
#   پیش‌فرض‌ها: مسیر برنامه = دو پوشه بالاتر از همین فایل؛ مسیر پشتیبان = $HOME/mahru-backups
#   KEEP_DAYS=14 (متغیر محیطی)
# cron (DirectAdmin → Cron Jobs، هر شب ۰۳:۴۰):
#   40 3 * * * bash /home/[username]/public_html/deploy/backup/mahru-backup.sh /home/[username]/public_html /home/[username]/mahru-backups >> /home/[username]/mahru-backups/backup.log 2>&1
# بازگردانی: docs/deployment/BACKUP.md
# ============================================================
set -euo pipefail
umask 077

APP_DIR="${1:-$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)}"
BACKUP_ROOT="${2:-$HOME/mahru-backups}"
KEEP_DAYS="${KEEP_DAYS:-14}"
STAMP="$(date +%Y%m%d_%H%M%S)"
DEST="$BACKUP_ROOT/$STAMP"

if [[ ! -f "$APP_DIR/.env" ]]; then
    echo "[$STAMP] .env not found in $APP_DIR" >&2
    exit 1
fi

# فقط کلیدهای DB_* از .env (بدون source کردن کل فایل)
env_value() {
    local line
    line="$(grep -E "^$1=" "$APP_DIR/.env" | tail -n 1 || true)"
    line="${line#*=}"
    line="${line%\"}"; line="${line#\"}"
    line="${line%\'}"; line="${line#\'}"
    printf '%s' "$line"
}

DB_CONNECTION="$(env_value DB_CONNECTION)"
DB_HOST="$(env_value DB_HOST)"; DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="$(env_value DB_PORT)"; DB_PORT="${DB_PORT:-3306}"
DB_DATABASE="$(env_value DB_DATABASE)"
DB_USERNAME="$(env_value DB_USERNAME)"
DB_PASSWORD="$(env_value DB_PASSWORD)"

mkdir -p "$DEST"

# ─── ۱) دیتابیس ───────────────────────────────────────────
if [[ "$DB_CONNECTION" == "mysql" || "$DB_CONNECTION" == "mariadb" ]]; then
    DUMP="$(command -v mariadb-dump || command -v mysqldump)"
    # رمز از فایل موقت (نه خط فرمان، تا در ps دیده نشود)
    CNF="$(mktemp)"
    trap 'rm -f "$CNF"' EXIT
    printf '[client]\nuser=%s\npassword=%s\nhost=%s\nport=%s\n' "$DB_USERNAME" "$DB_PASSWORD" "$DB_HOST" "$DB_PORT" > "$CNF"
    "$DUMP" --defaults-extra-file="$CNF" --single-transaction --quick --routines --no-tablespaces "$DB_DATABASE" | gzip -9 > "$DEST/database.sql.gz"
elif [[ "$DB_CONNECTION" == "sqlite" ]]; then
    cp "$(env_value DB_DATABASE)" "$DEST/database.sqlite"
else
    echo "[$STAMP] unsupported DB_CONNECTION=$DB_CONNECTION" >&2
    exit 1
fi

# ─── ۲) فایل‌ها ─────────────────────────────────────────────
DIRS=()
for dir in course-photos public private; do
    [[ -d "$APP_DIR/storage/app/$dir" ]] && DIRS+=("storage/app/$dir")
done
if [[ ${#DIRS[@]} -gt 0 ]]; then
    tar -czf "$DEST/storage-app.tar.gz" -C "$APP_DIR" "${DIRS[@]}"
fi

# ─── ۳) نگه‌داری ──────────────────────────────────────────
find "$BACKUP_ROOT" -mindepth 1 -maxdepth 1 -type d -name '20*' -mtime +"$KEEP_DAYS" -exec rm -rf {} +

echo "[$STAMP] backup OK → $DEST ($(du -sh "$DEST" | cut -f1)); files: ${DIRS[*]:-none}"
