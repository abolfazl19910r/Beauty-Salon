<?php

namespace App\Http\Middleware\Api;

use App\Http\Middleware\EnsureLegalTermsAccepted;
use App\Http\Responses\ApiResponse;
use App\Services\Api\ApiAudienceGate;
use App\Services\Legal\LegalAcceptanceRecorder;
use App\Services\Legal\LegalStatus;
use App\Support\CurrentSalon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * پذیرش دوباره‌ی قوانین در اپ مشتری (۲۰۲۶-۱۰-۲۸)، بعد از api.audience. فقط برای اپ مشتری و فقط وقتی تغییر «مهم»ی پذیرفته نشده:
 *  - اپ قدیمی (X-App-Version کمتر از legal.reaccept_min_app_version یا بی سرآیند) صفحه‌ی «قوانین به‌روز شد» ندارد: قفل نمی‌شود و
 *    پذیرشش همین لحظه با منبع app-implicit و نسخه‌ی اپ ثبت می‌شود (همان تصمیم ثبت‌نام ۲۰۲۶-۱۰-۲۵).
 *  - اپ تازه: نوبت و دوره‌ی تازه (BLOCKED) با 409 ‏terms_update_required؛ بقیه‌ی مسیرها (نوبت‌های فعلی، لغو، حساب و حذف حساب) باز.
 */
class EnsureApiLegalTerms
{
    /** نام مسیرهایی که نوبت یا دوره‌ی تازه می‌سازند */
    public const BLOCKED = [
        'api.v1.customer.bookings.quote',
        'api.v1.customer.bookings.store',
        'api.v1.customer.courses.purchase',
        'api.v1.customer.courses.book',
    ];

    public function __construct(
        private readonly LegalStatus $status,
        private readonly LegalAcceptanceRecorder $recorder,
        private readonly CurrentSalon $currentSalon,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $request->attributes->get('api_audience') !== ApiAudienceGate::CUSTOMER) {
            return $next($request);
        }

        $pending = $this->status->pending($user, LegalAcceptanceRecorder::CUSTOMER_DOCUMENTS);
        if ($pending === []) {
            return $next($request);
        }

        if (! $this->status->appSupportsReacceptance($request->header('X-App-Version'))) {
            $this->recorder->acceptNow($user, $this->currentSalon->get(), array_column($pending, 'document'), $request, 'app-implicit');

            return $next($request);
        }

        if (in_array($request->route()?->getName(), self::BLOCKED, true)) {
            return ApiResponse::error(EnsureLegalTermsAccepted::CODE, EnsureLegalTermsAccepted::MESSAGE, 409);
        }

        return $next($request);
    }
}
