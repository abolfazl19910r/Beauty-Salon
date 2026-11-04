<?php

namespace App\Console\Commands;

use App\Services\Payment\ZarinpalUnverifiedReconciler;
use Illuminate\Console\Command;

/**
 * ⭐ بسته‌ی 5ح (۲۰۲۶-۱۱-۰۴): تطبیق ساعتی تراکنش‌های بسته‌شده با فهرست `unVerified` زرین‌پال (bootstrap/app.php) —
 * جزئیات در App\Services\Payment\ZarinpalUnverifiedReconciler.
 */
class ReconcileZarinpalUnverified extends Command
{
    protected $signature = 'payments:zarinpal-unverified {--dry-run : فقط نمایش، بدون تغییر}';

    protected $description = 'پیدا کردن پرداخت‌های موفقِ تأییدنشده‌ی زرین‌پال (unVerified) که تراکنششان پیش ما بسته شده و تأیید و واریز آن‌ها';

    public function handle(ZarinpalUnverifiedReconciler $reconciler): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $stats = $reconciler->run($dryRun, fn (string $line) => $this->line($line));

        $outcomes = collect($stats['outcomes'])->map(fn ($c, $k) => "{$k}: {$c}")->implode('، ');
        $this->info(($dryRun ? '[dry-run] ' : '')."unVerified زرین‌پال: {$stats['merchants']} مرچنت (ناموفق: {$stats['failed_calls']})، {$stats['listed']} اتوریتی، ناشناس: {$stats['unknown']}، بی‌نیاز: {$stats['ignored']}".($outcomes ? " — {$outcomes}" : ''));

        return self::SUCCESS;
    }
}
