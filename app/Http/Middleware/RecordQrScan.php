<?php

namespace App\Http\Middleware;

use App\Models\Specialist;
use App\Services\Qr\QrScanCounter;
use App\Support\CurrentSalon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * بسته‌ی ۵: صفحه‌ی اصلی سالن و صفحه‌ی متخصص، وقتی با ?src=qr باز شوند (آدرس QR چاپی)، یک بازدید QR ثبت می‌کنند.
 * فقط پاسخ موفق شمرده می‌شود (سالن معلق یا متخصص دیگر سالن نه).
 */
class RecordQrScan
{
    public function __construct(private readonly QrScanCounter $counter, private readonly CurrentSalon $currentSalon) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->isMethod('GET') && $request->query('src') === 'qr' && $response->isSuccessful() && ($salon = $this->currentSalon->get())) {
            $specialist = $request->route('specialist');
            $specialistId = $specialist instanceof Specialist ? $specialist->id : (is_numeric($specialist) ? (int) $specialist : null);
            $this->counter->record($salon, $specialistId);
        }

        return $response;
    }
}
