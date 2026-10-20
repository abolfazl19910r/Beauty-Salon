{{--
    صفحه‌ی عمومی «دانلود اپ ماهرو» (بسته‌ی ۵، ۲۰۲۶-۱۰-۲۰) روی دامنه‌ی اصلی — اپ‌ها در مارکت نیستند و APK از همین‌جا دانلود می‌شود.
    ورودی: $latest = ['customer' => ?AppRelease, 'staff' => ?AppRelease]. ?salon={slug} (از سایت سالن): کد همان سالن نشان داده می‌شود.
--}}
@php
    $salonCode = null;
    if (is_string(request()->query('salon')) && preg_match('/^[\p{L}\p{N}\-]{2,64}$/u', request()->query('salon'))) {
        $salonCode = \App\Models\Salon::query()->where('slug', request()->query('salon'))->where('is_suspended', false)->value('slug');
    }
@endphp
<x-guest-layout>
    <div class="text-center mb-6" data-app-download>
        <img src="{{ asset('brand/mahru-customer-app-192.png') }}" alt="" width="72" height="72" class="mx-auto mb-3 rounded-2xl">
        <h2 class="text-2xl font-bold" style="font-family:'Mahru','Vazirmatn',sans-serif;font-weight:800;color:rgb(var(--m-gold2))">اپ ماهرو</h2>
        <p class="text-sm text-m-tx/60 mt-1">رزرو نوبت، پرداخت و پیگیری نوبت‌ها در سالن زیبایی‌تان</p>
    </div>

    @php($customer = $latest['customer'])
    @if ($customer)
        <a href="{{ route('app-download.file', ['variant' => 'customer']) }}" data-app-download-customer
           class="block w-full text-center py-3 rounded-lg font-semibold transition-all duration-300 bg-gradient-to-l from-[#C9A24B] to-[#E6CD8A] text-m-ongold hover:shadow-lg hover:shadow-[#C9A24B]/30">
            دانلود اپ برای اندروید
        </a>
        <p class="text-xs text-m-tx/50 text-center mt-2">
            نسخه‌ی <span dir="ltr">{{ $customer->version_name }}</span> · {{ $customer->sizeLabel() }} · {{ \Morilog\Jalali\Jalalian::fromCarbon($customer->created_at)->format('Y/m/d') }}
        </p>
        @if ($customer->notes)
            <p class="text-sm text-m-tx/70 mt-3 leading-7 whitespace-pre-line">{{ $customer->notes }}</p>
        @endif
    @else
        <p class="text-center text-sm text-m-tx/70 rounded-lg p-3 bg-m-gold/10" data-app-download-soon>اپ اندروید به‌زودی از همین صفحه منتشر می‌شود.</p>
    @endif

    @if ($salonCode)
        <div class="mt-5 rounded-xl p-3 text-center border border-m-gold/30" data-app-download-salon-code>
            <p class="text-sm text-m-tx/70">بعد از نصب، در اپ کد سالن را وارد کنید یا QR سالن را اسکن کنید:</p>
            <p class="mt-1 text-xl font-bold text-m-gold2" dir="ltr">{{ $salonCode }}</p>
        </div>
    @endif

    <div class="mt-6">
        <h3 class="font-bold text-m-tx mb-2">نصب روی اندروید</h3>
        <ol class="list-decimal pr-5 space-y-1 text-sm text-m-tx/75 leading-7">
            <li>دکمه‌ی «دانلود» را بزنید و فایل را باز کنید.</li>
            <li>اگر گوشی پرسید «نصب از منبع ناشناس»، در همان پیام به مرورگرتان اجازه‌ی نصب بدهید و برگردید.</li>
            <li>«نصب» را بزنید. اپ را باز کنید و کد سالن را وارد کنید یا QR سالن را اسکن کنید.</li>
        </ol>
        <p class="text-xs text-m-tx/50 mt-2 leading-6">اپ ماهرو فقط از همین صفحه منتشر می‌شود؛ فایل را از جای دیگری نصب نکنید.</p>
    </div>

    <div class="mt-6">
        <h3 class="font-bold text-m-tx mb-2">آیفون</h3>
        <p class="text-sm text-m-tx/75 leading-7">
            برای آیفون اپ جدا نداریم: صفحه‌ی سالن خودتان را در Safari باز کنید و از دکمه‌ی اشتراک‌گذاری «افزودن به صفحه‌ی اصلی» را بزنید؛
            مثل یک اپ روی صفحه‌ی گوشی می‌ماند. راهنمای کامل در صفحه‌ی «اپ» سایت هر سالن هست.
        </p>
    </div>

    @php($staff = $latest['staff'])
    <div class="mt-6 pt-5 border-t border-m-gold/15" data-app-download-staff-section>
        <h3 class="font-bold text-m-tx mb-1">متخصص سالن هستید؟</h3>
        <p class="text-sm text-m-tx/70 leading-7">اپ «ماهرو همکار» برای دیدن نوبت‌های امروز، تقویم و کیف پول.</p>
        @if ($staff)
            <a href="{{ route('app-download.file', ['variant' => 'staff']) }}" data-app-download-staff
               class="inline-block mt-2 px-4 py-2 rounded-lg text-sm font-semibold border border-m-gold/50 text-m-gold2 hover:bg-m-gold/10">
                دانلود ماهرو همکار
            </a>
            <span class="text-xs text-m-tx/50 mr-2">نسخه‌ی <span dir="ltr">{{ $staff->version_name }}</span> · {{ $staff->sizeLabel() }}</span>
        @else
            <p class="text-xs text-m-tx/50 mt-1">به‌زودی.</p>
        @endif
    </div>

    @if ($customer)
        <p class="text-[11px] text-m-tx/40 mt-6 break-all" dir="ltr" title="SHA-256">SHA-256 (ماهرو): {{ $customer->sha256 }}</p>
    @endif
</x-guest-layout>
