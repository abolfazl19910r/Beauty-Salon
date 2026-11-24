{{--
    صفحه‌ی اختصاصی دانلود «ماهرو همکار» برای متخصص‌ها (۲۰۲۶-۱۱-۲۱) روی دامنه‌ی اصلی — جدا از /app که اپ مشتری است.
    ورودی: $staff = ?AppRelease (آخرین نسخه‌ی منتشرشده‌ی اپ همکار). فایل از همان /app/download/staff می‌آید.
--}}
<x-guest-layout>
    <x-slot name="title">دانلود اپ ماهرو همکار</x-slot>
    <x-slot name="description">اپ اندروید «ماهرو همکار» برای کارشناسان سالن: نوبت‌های امروز، تقویم و ساعت کاری، مرخصی و کیف پول. نصب مستقیم با فایل APK.</x-slot>
    <div class="text-center mb-6" data-staff-page>
        <img src="{{ asset('brand/mahru-app-icon.svg') }}" alt="" width="72" height="72" class="mx-auto mb-3 rounded-2xl">
        <h2 class="text-2xl font-bold" style="font-family:'Mahru','Vazirmatn',sans-serif;font-weight:800;color:rgb(var(--m-gold2))">ماهرو همکار</h2>
        <p class="text-sm text-m-tx/60 mt-1">اپ ویژه‌ی کارشناسان سالن: نوبت‌های امروز، تقویم و کیف پول</p>
    </div>

    @if ($staff)
        <a href="{{ route('app-download.file', ['variant' => 'staff']) }}" data-staff-page-download
           class="block w-full text-center py-3 rounded-lg font-semibold transition-all duration-300 bg-gradient-to-l from-[#C9A24B] to-[#E6CD8A] text-m-ongold hover:shadow-lg hover:shadow-[#C9A24B]/30">
            دانلود ماهرو همکار برای اندروید
        </a>
        <p class="text-xs text-m-tx/50 text-center mt-2">
            نسخه‌ی <span dir="ltr">{{ $staff->version_name }}</span> · {{ $staff->sizeLabel() }} · {{ fa_date($staff->created_at) }}
        </p>
        @if ($staff->notes)
            <p class="text-sm text-m-tx/70 mt-3 leading-7 whitespace-pre-line">{{ $staff->notes }}</p>
        @endif
    @else
        <p class="text-center text-sm text-m-tx/70 rounded-lg p-3 bg-m-gold/10" data-staff-page-soon>اپ ماهرو همکار به‌زودی از همین صفحه منتشر می‌شود.</p>
    @endif

    <div class="mt-6">
        <h3 class="font-bold text-m-tx mb-2">نصب روی اندروید</h3>
        <ol class="list-decimal pr-5 space-y-1 text-sm text-m-tx/75 leading-7">
            <li>دکمه‌ی «دانلود» را بزنید و فایل را باز کنید.</li>
            <li>اگر گوشی پرسید «نصب از منبع ناشناس»، در همان پیام به مرورگرتان اجازه‌ی نصب بدهید و برگردید.</li>
            <li>«نصب» را بزنید، اپ را باز کنید و با حساب کارشناس‌تان وارد شوید.</li>
        </ol>
        <p class="text-xs text-m-tx/50 mt-2 leading-6">اپ ماهرو همکار فقط از همین صفحه منتشر می‌شود؛ فایل را از جای دیگری نصب نکنید.</p>
    </div>

    <div class="mt-6">
        <h3 class="font-bold text-m-tx mb-2">آیفون</h3>
        <p class="text-sm text-m-tx/75 leading-7">
            برای آیفون اپ جدا نداریم: پنل کارشناس را در Safari باز کنید و از دکمه‌ی اشتراک‌گذاری «افزودن به صفحه‌ی اصلی» را بزنید؛
            مثل یک اپ روی صفحه‌ی گوشی می‌ماند.
        </p>
    </div>

    <div class="mt-6 pt-5 border-t border-m-gold/15 text-center" data-staff-page-customer-link>
        <p class="text-sm text-m-tx/70">مشتری سالن هستید؟</p>
        <a href="{{ route('app-download.show') }}" class="inline-block mt-1 text-sm font-semibold text-m-gold2 hover:text-m-gold underline">دانلود اپ ماهرو برای مشتری‌ها</a>
    </div>
</x-guest-layout>
