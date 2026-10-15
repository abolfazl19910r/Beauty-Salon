<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * ⭐ «وضعیت آخرین پشتیبان‌گیری» (۲۰۲۶-۱۰-۱۵): deploy/backup/mahru-backup.sh بعد از هر اجرا storage/app/backup-status.json را
 * می‌نویسد (موفق یا ناموفق). پشتیبانِ قدیمی‌تر از BACKUP_STALE_HOURS (پیش‌فرض ۳۶ ساعت؛ cron روزانه است) «قدیمی» حساب می‌شود.
 */
class BackupStatus
{
    /** @return array{state: string, at: ?Carbon, size: ?string, dest: ?string, message: ?string} state: ok | stale | failed | never */
    public static function read(): array
    {
        $path = storage_path('app/backup-status.json');
        $data = is_file($path) ? json_decode((string) @file_get_contents($path), true) : null;

        if (! is_array($data) || empty($data['at'])) {
            return ['state' => 'never', 'at' => null, 'size' => null, 'dest' => null, 'message' => null];
        }

        try {
            $at = Carbon::parse($data['at'])->setTimezone(config('app.timezone'));
        } catch (\Throwable) {
            $at = null;
        }

        $state = match (true) {
            ($data['status'] ?? '') !== 'ok' => 'failed',
            ! $at || $at->lt(now()->subHours((int) config('backup.stale_hours', 36))) => 'stale',
            default => 'ok',
        };

        return [
            'state' => $state,
            'at' => $at,
            'size' => ($data['size'] ?? '') ?: null,
            'dest' => ($data['dest'] ?? '') ?: null,
            'message' => ($data['message'] ?? '') ?: null,
        ];
    }
}
