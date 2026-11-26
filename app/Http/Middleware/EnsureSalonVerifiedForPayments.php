<?php

namespace App\Http\Middleware;

use App\Support\CurrentSalon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * بسته‌ی ۷ (تصمیم ابوالفضل ۲۰۲۶-۱۱-۲۶): احراز سالن فقط پیش‌نیاز پول آنلاین است — وصل کردن درگاه خود سالن (فرم یا «اتصال حساب
 * زرین‌پال») و درخواست «درگاه ماهرو». صفحه‌ی سالن و رزرو بی‌پیش‌پرداخت از روز اول (دوره‌ی آزمایشی) باز است.
 */
class EnsureSalonVerifiedForPayments
{
    public function handle(Request $request, Closure $next): Response
    {
        $salon = app(CurrentSalon::class)->get();

        if ($salon && ! $salon->isVerified()) {
            return redirect()->route('admin.verification.show')
                ->with('error', 'برای وصل کردن درگاه پرداخت، اول احراز سالن (کد ملی مالک و پروانه‌ی کسب) را کامل کنید.');
        }

        return $next($request);
    }
}
