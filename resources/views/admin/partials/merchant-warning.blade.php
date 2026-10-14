{{--
    ⭐ هشدار «درگاه پرداخت سالن تنظیم نشده» (۲۰۲۶-۰۹-۲۴) — بالای داشبورد و صفحه‌ی اشتراک. بدون کد
    پذیرنده‌ی زرین‌پال خود سالن هیچ پرداخت آنلاینی برای مشتری‌ها ممکن نیست (App\Support\ZarinpalMerchant).
    ورودی: $salon (App\Models\Salon|null)
--}}
@if ($salon && ! $salon->hasFeature(\App\Support\Features\Feature::OnlinePayment))
    {{-- پرداخت آنلاین در پلن سالن نیست (بسته‌ی پلن‌ها، ۲۰۲۶-۱۰-۱۴) — نه «درگاه تنظیم نشده» --}}
    <div class="rounded-xl p-4 mb-6 text-sm leading-7" style="background:#FEF3C7; border:1px solid #FCD34D; color:#92400E;" role="alert">
        <b>پرداخت آنلاین مشتری در پلن سالن شما نیست.</b>
        مشتری‌ها نمی‌توانند پیش‌پرداخت نوبت یا شارژ کیف پول انجام دهند و خدماتی را که پیش‌پرداخت دارند آنلاین رزرو کنند.
        <a href="{{ route('admin.billing.index') }}" class="font-bold underline mr-1">ارتقای پلن</a>
    </div>
@elseif ($salon && ! $salon->acceptsOnlinePayments())
    <div class="rounded-xl p-4 mb-6 text-sm leading-7" style="background:#FEF2F2; border:1px solid #FCA5A5; color:#991B1B;" role="alert">
        <b>درگاه پرداخت سالن شما هنوز تنظیم نشده است.</b>
        تا حداقل یک درگاه پرداخت فعال (زرین‌پال، زیبال، آسان پرداخت یا وندار) برای سالن اضافه نشود، هیچ پرداخت آنلاینی برای مشتری‌ها ممکن نیست: نه پیش‌پرداخت نوبت،
        نه پرداخت باقی‌مانده و نه شارژ کیف پول، و مشتری‌ها نمی‌توانند خدماتی را که پیش‌پرداخت دارند آنلاین رزرو کنند.
        @if (\Illuminate\Support\Facades\Route::has('admin.payment-gateways.index'))
            <a href="{{ route('admin.payment-gateways.index') }}" class="font-bold underline mr-1">افزودن درگاه پرداخت</a>
            <span class="text-xs">(فقط مالک سالن)</span>
        @endif
    </div>
@endif
