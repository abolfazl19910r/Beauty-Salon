<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\PaymentTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * ⭐ آدرس بازگشت مشترک همه‌ی درگاه‌ها (لایه‌ی چند درگاه — مرحله‌ی ۰ بخش ۲، ۲۰۲۶-۰۹-۲۵).
 *
 * درگاه مشتری رو به /payments/return/{public_id} برمی‌گردونه (GET مثل زرین‌پال/زیبال، یا POST مثل
 * بانک‌ها و PayPing v3). این مسیر عمداً بدون auth و بدون CSRF است — در POST cross-site کوکی session
 * (SameSite=Lax) اصلاً فرستاده نمی‌شه. هیچ منطق مالی‌ای اینجا اجرا نمی‌شه: فقط با یک redirect از نوع
 * 303 (همیشه GET) مشتری رو به callback کسب‌وکار همون تراکنش (پرداخت نوبت / شارژ کیف پول / اشتراک)
 * می‌فرسته، با پارامترهای درگاه + tx. اون درخواست یک navigation از نوع GET است و کوکی‌های Lax همراهش
 * هستن، پس session و لاگین مشتری سالم می‌مونه. تأیید واقعی پرداخت همون‌جا با درگاه و مبلغِ ثبت‌شده در
 * همین ردیف انجام می‌شه (نه با چیزی که در query اومده).
 */
class GatewayReturnController extends Controller
{
    /** پارامترهایی که هرگز از درگاه به callback کسب‌وکار منتقل نمی‌شن. */
    private const BLOCKED = ['tx', '_token', 'booking', 'invoice'];

    public function __invoke(Request $request, string $publicId): RedirectResponse
    {
        $transaction = PaymentTransaction::where('public_id', $publicId)->firstOrFail();

        $params = collect($request->all())
            ->except(self::BLOCKED)
            ->filter(fn ($v) => is_scalar($v))
            ->map(fn ($v) => mb_substr((string) $v, 0, 2000))
            ->all();

        $separator = str_contains($transaction->callback_url, '?') ? '&' : '?';

        return redirect()->away(
            $transaction->callback_url.$separator.http_build_query($params + ['tx' => $transaction->public_id]),
            303,
        );
    }
}
