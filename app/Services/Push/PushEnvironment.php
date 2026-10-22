<?php

namespace App\Services\Push;

use App\Support\Queues;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * پیش‌بررسی محیط برای دستورهای push:test و push:live-check (تصمیم ۴-الف، ۲۰۲۶-۱۰-۲۲). این سه چیز آزمون «فعال» را بی‌صدا خراب
 * می‌کنند و از پنل دیده نمی‌شوند:
 *
 * - حالت پلتفرم: «خاموش» هیچ پوشی نمی‌فرستد؛ در «آزمایشی» پیامک همیشه همان لحظه می‌رود و مسیر پیامک پشتیبان آزموده نمی‌شود.
 * - صف sync: AppMessenger::deliver در «فعال» پیامک پشتیبان را همان لحظه می‌فرستد (رسید هرگز پیش از job نمی‌رسد)، پس «فعال» مثل
 *   «آزمایشی» رفتار می‌کند.
 * - worker: با صف database، اگر jobی روی صف push یا sms بیش از چند ثانیه برداشته نشده، worker باز نیست (یا آن صف را نمی‌خواند).
 */
final class PushEnvironment
{
    /** jobی که این‌قدر (ثانیه) بعد از موعدش برداشته نشده، یعنی worker آن صف را نمی‌خواند */
    public const STALE_JOB_SECONDS = 30;

    /**
     * @param  bool  $fallbackPath  آزمون مسیر پیامک پشتیبان (push:live-check) — صف sync و حالت «آزمایشی» برایش مهم‌اند
     * @return list<array{level: 'error'|'warn'|'info', text: string}>
     */
    public static function checks(bool $fallbackPath): array
    {
        $out = [];
        $mode = PushMode::current();

        $out[] = ['level' => 'info', 'text' => 'حالت پوش پلتفرم: '.PushMode::label($mode)];

        if ($mode === PushMode::OFF) {
            $out[] = ['level' => $fallbackPath ? 'error' : 'warn', 'text' => 'حالت «خاموش» است: رویدادها پوش نمی‌شوند (فقط پیامک). از «تنظیمات پلتفرم» عوضش کنید.'];
        } elseif ($fallbackPath && $mode === PushMode::SHADOW) {
            $out[] = ['level' => 'warn', 'text' => 'حالت «آزمایشی» است: پیامک همیشه همان لحظه می‌رود و مسیر «بدون رسید ← پیامک» آزموده نمی‌شود. برای آزمون رفتار «فعال»، اول حالت را «فعال» کنید.'];
        }

        $driver = (string) config('queue.default');
        if ($driver === 'sync') {
            $out[] = ['level' => 'warn', 'text' => $fallbackPath
                ? 'QUEUE_CONNECTION=sync است: در «فعال» پیامک پشتیبان همان لحظه می‌رود (مهلت رسید نمی‌ماند). در .env بگذارید database و queue:work را باز کنید.'
                : 'QUEUE_CONNECTION=sync است: پوش‌های رویدادها همان لحظه می‌روند ولی در «فعال» مهلت پیامک پشتیبان کار نمی‌کند (push:live-check را ببینید).'];
        }

        foreach (self::staleJobs() as $queue => $count) {
            $out[] = ['level' => 'warn', 'text' => to_persian_num((string) $count)." job روی صف «{$queue}» بیش از "
                .to_persian_num((string) self::STALE_JOB_SECONDS).' ثانیه برداشته نشده — worker باز نیست یا این صف را نمی‌خواند: php artisan queue:work --queue='.Queues::workerOrder()];
        }

        return $out;
    }

    /**
     * فقط صف database قابل دیدن است (sync صفی ندارد؛ redis را این‌جا نمی‌خوانیم).
     *
     * @return array<string, int>
     */
    public static function staleJobs(): array
    {
        if (config('queue.default') !== 'database') {
            return [];
        }

        $connection = config('queue.connections.database.connection');
        $table = (string) (config('queue.connections.database.table') ?: 'jobs');

        try {
            if (! Schema::connection($connection)->hasTable($table)) {
                return [];
            }

            return DB::connection($connection)->table($table)
                ->whereIn('queue', [Queues::PUSH, Queues::SMS])
                ->whereNull('reserved_at')
                ->where('available_at', '<=', now()->subSeconds(self::STALE_JOB_SECONDS)->getTimestamp())
                ->selectRaw('queue, count(*) as c')->groupBy('queue')
                ->pluck('c', 'queue')->map(fn ($c) => (int) $c)->all();
        } catch (\Throwable) {
            return [];
        }
    }
}
