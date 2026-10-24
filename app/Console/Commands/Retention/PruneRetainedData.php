<?php

namespace App\Console\Commands\Retention;

use App\Models\DeletedAccountArchive;
use App\Models\SecurityLog;
use App\Support\Retention;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * پاک‌سازی خودکار داده‌ی نگهداری‌شده بعد از مهلت قانونی (بسته‌ی مجوز 5د) — config/retention.php، هر شب از scheduler.
 *
 * - security_logs: ردیف‌های قدیمی‌تر از مهلت (IP و user agent ورودها و کارهای کلیدی).
 * - deleted_account_archives: هویت و IP حساب حذف‌شده بعد از purge_after (شش ماه پس از حذف).
 * - لاگ دسترسی nginx: فایل روزهای قبل از دیروز فشرده می‌شود (nginx ممکن است چند ثانیه بعد از نیمه‌شب هنوز در فایل دیروز
 *   بنویسد)؛ فایل‌های قدیمی‌تر از مهلت (فشرده یا نه) پاک می‌شوند. تاریخ از نام فایل خوانده می‌شود، نه زمان تغییر.
 */
class PruneRetainedData extends Command
{
    protected $signature = 'retention:prune {--dry-run : فقط شمارش، بدون پاک کردن}';

    protected $description = 'Delete security logs and access logs older than the legal retention period';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        $cutoff = Retention::cutoff('security_logs_days');
        $query = SecurityLog::query()->where('created_at', '<', $cutoff);
        $count = (clone $query)->count();
        if (! $dry) {
            $query->chunkById(1000, fn ($rows) => SecurityLog::query()->whereIn('id', $rows->modelKeys())->delete());
        }
        $this->line("security_logs: {$count} ردیف قدیمی‌تر از {$cutoff->toDateString()}".($dry ? ' (dry-run)' : ' پاک شد'));

        $expired = DeletedAccountArchive::query()->where('purge_after', '<', now());
        $archives = (clone $expired)->count();
        $dry || $expired->delete();
        $this->line("deleted_account_archives: {$archives} بایگانی بعد از مهلت".($dry ? ' (dry-run)' : ' پاک شد'));

        [$compressed, $deleted] = $this->pruneAccessLogs($dry);
        $this->line("access logs: {$compressed} فشرده، {$deleted} پاک".($dry ? ' (dry-run)' : ''));

        return self::SUCCESS;
    }

    /** @return array{int, int} */
    private function pruneAccessLogs(bool $dry): array
    {
        $dir = (string) config('retention.access_log_path');
        if ($dir === '' || ! is_dir($dir)) {
            return [0, 0];
        }

        $cutoff = Retention::cutoff('access_log_days')->startOfDay();
        $compressBefore = now('UTC')->subDay()->startOfDay();
        $compressed = $deleted = 0;

        foreach (glob($dir.'/access-*.log*') ?: [] as $file) {
            if (! preg_match('/access-(\d{4}-\d{2}-\d{2})\.log(\.gz)?$/', basename($file), $m)) {
                continue;
            }
            $date = Carbon::createFromFormat('Y-m-d', $m[1], 'UTC')->startOfDay();

            if ($date->lt($cutoff)) {
                $deleted++;
                $dry || @unlink($file);

                continue;
            }

            if (empty($m[2]) && $date->lt($compressBefore)) {
                $compressed++;
                $dry || $this->gzip($file);
            }
        }

        return [$compressed, $deleted];
    }

    private function gzip(string $file): void
    {
        $target = $file.'.gz';
        $in = fopen($file, 'rb');
        $out = gzopen($target.'.tmp', 'wb6');
        if (! $in || ! $out) {
            return;
        }
        while (! feof($in)) {
            gzwrite($out, (string) fread($in, 1 << 20));
        }
        fclose($in);
        gzclose($out);
        // فقط بعد از نوشتن کامل جایگزین می‌شود (قطع وسط کار فایل اصلی را از بین نمی‌برد)
        rename($target.'.tmp', $target);
        @chmod($target, 0640);
        unlink($file);
    }
}
