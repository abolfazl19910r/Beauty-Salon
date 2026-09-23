{{--
    ⭐ هشدار «درگاه پرداخت سالن تنظیم نشده» (۲۰۲۶-۰۹-۲۴) — بالای داشبورد و صفحه‌ی اشتراک. بدون کد
    پذیرنده‌ی زرین‌پال خود سالن هیچ پرداخت آنلاینی برای مشتری‌ها ممکن نیست (App\Support\ZarinpalMerchant).
    ورودی: $salon (App\Models\Salon|null)
--}}
@if ($salon && ! $salon->acceptsOnlinePayments())
    <div class="rounded-xl p-4 mb-6 text-sm leading-7" style="background:#FEF2F2; border:1px solid #FCA5A5; color:#991B1B;" role="alert">
        <b>درگاه پرداخت سالن شما هنوز تنظیم نشده است.</b>
        تا کد پذیرنده‌ی زرین‌پال (Merchant ID) سالن وارد نشود، هیچ پرداخت آنلاینی برای مشتری‌ها ممکن نیست: نه پیش‌پرداخت نوبت،
        نه پرداخت باقی‌مانده و نه شارژ کیف پول، و مشتری‌ها نمی‌توانند خدماتی را که پیش‌پرداخت دارند آنلاین رزرو کنند.
        @if (\Illuminate\Support\Facades\Route::has('admin.salon-settings.edit'))
            <a href="{{ route('admin.salon-settings.edit') }}#merchant" class="font-bold underline mr-1">وارد کردن کد پذیرنده</a>
            <span class="text-xs">(فقط مالک سالن)</span>
        @endif
    </div>
@endif
