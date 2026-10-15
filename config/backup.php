<?php

/*
 * وضعیت پشتیبان‌گیری در پنل ابرمدیر (۲۰۲۶-۱۰-۱۵) — App\Support\BackupStatus.
 * stale_hours: آخرین پشتیبانِ موفقِ قدیمی‌تر از این، هشدار «قدیمی» می‌گیرد (cron روزانه است؛ ۳۶ ساعت یعنی یک شب جا افتاده).
 */
return [
    'stale_hours' => (int) env('BACKUP_STALE_HOURS', 36),
];
