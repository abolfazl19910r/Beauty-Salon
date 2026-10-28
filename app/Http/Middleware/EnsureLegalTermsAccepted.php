<?php

namespace App\Http\Middleware;

use App\Services\Legal\LegalAcceptanceRecorder;
use App\Services\Legal\LegalStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * پذیرش دوباره‌ی قوانین مشتری وب (۲۰۲۶-۱۰-۲۸): روی مسیرهایی که نوبت یا دوره‌ی تازه می‌سازند. مشتری‌ای که تغییر «مهم» قوانین
 * یا حریم خصوصی را هنوز نپذیرفته به صفحه‌ی «قوانین به‌روز شد» می‌رود (درخواست JSON: 409 با کد terms_update_required).
 * بقیه‌ی حساب (نوبت‌های فعلی و لغو، دوره‌ها، شکایت، پروفایل و حذف حساب، خروج) باز می‌ماند — تصمیم ابوالفضل: کسی که نپذیرد
 * می‌تواند خارج شود یا حسابش را حذف کند، ولی نوبت تازه نمی‌گیرد.
 */
class EnsureLegalTermsAccepted
{
    public const CODE = 'terms_update_required';

    public const MESSAGE = 'قوانین ماهرو به‌روز شده است؛ برای گرفتن نوبت تازه، اول نسخه‌ی تازه را بپذیرید.';

    public function __construct(private readonly LegalStatus $status) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->user_type !== 'customer'
            || $this->status->pending($user, LegalAcceptanceRecorder::CUSTOMER_DOCUMENTS) === []) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false, 'code' => self::CODE, 'message' => self::MESSAGE, 'redirect' => route('terms-update.show'),
            ], 409);
        }

        if ($request->isMethod('GET')) {
            $request->session()->put('legal.intended', $request->fullUrl());
        }

        return redirect()->route('terms-update.show')->with('error', self::MESSAGE);
    }
}
