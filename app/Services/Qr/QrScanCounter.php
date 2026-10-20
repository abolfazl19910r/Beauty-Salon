<?php

namespace App\Services\Qr;

use App\Models\QrScan;
use App\Models\Salon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * شمارش بازدید از QR (بسته‌ی ۵): هر بازدید صفحه‌ی سالن یا متخصص با ?src=qr، برای هر نشست مرورگر روزی یک بار (رفرش شمرده نمی‌شود).
 * خطای ثبت هرگز صفحه را نمی‌شکند.
 */
class QrScanCounter
{
    public function record(Salon $salon, ?int $specialistId = null): void
    {
        $specialistId ??= 0;
        $date = now()->toDateString();
        $sessionKey = "qr_scan.{$salon->id}.{$specialistId}.{$date}";

        try {
            if (session()->has($sessionKey)) {
                return;
            }
            session()->put($sessionKey, true);

            DB::table('qr_scans')->upsert(
                [['salon_id' => $salon->id, 'specialist_id' => $specialistId, 'scan_date' => $date, 'hits' => 1]],
                ['salon_id', 'specialist_id', 'scan_date'],
                ['hits' => DB::raw('hits + 1')],
            );
        } catch (\Throwable $e) {
            Log::warning('QrScanCounter: record failed', ['salon_id' => $salon->id, 'error' => $e->getMessage()]);
        }
    }

    /**
     * @return array{salon: int, specialists: array<int, int>, total: int}
     */
    public function summary(Salon $salon, int $days = 30): array
    {
        $rows = QrScan::query()->withoutGlobalScopes()->where('salon_id', $salon->id)
            ->where('scan_date', '>=', now()->subDays($days - 1)->toDateString())
            ->selectRaw('specialist_id, SUM(hits) as total')->groupBy('specialist_id')
            ->pluck('total', 'specialist_id')->map(fn ($v) => (int) $v)->all();

        $salonHits = $rows[0] ?? 0;
        unset($rows[0]);

        return ['salon' => $salonHits, 'specialists' => $rows, 'total' => $salonHits + array_sum($rows)];
    }
}
