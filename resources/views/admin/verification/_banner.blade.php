{{-- بسته‌ی مجوز 5د: تا تأیید احراز، صفحه‌ی عمومی و رزرو سالن بسته است --}}
@php($__vSalon = app(\App\Support\CurrentSalon::class)->get())
@if ($__vSalon && ! $__vSalon->isVerified() && ! request()->routeIs('admin.verification.*'))
    <div class="px-4 py-2 text-sm flex flex-wrap items-center justify-between gap-2" data-verification-banner
         style="background: rgb(var(--m-warn) / .16); color: var(--admin-text); border-bottom: 1px solid rgb(var(--m-warn) / .35);">
        <span>
            @switch($__vSalon->verification_status)
                @case('pending') مدارک احراز سالن در انتظار بررسی است؛ تا تأیید، سایت سالن برای مشتری‌ها باز نیست. @break
                @case('rejected') احراز سالن تأیید نشد؛ مدارک را اصلاح و دوباره بفرستید. تا تأیید، سایت سالن برای مشتری‌ها باز نیست. @break
                @default برای باز شدن سایت و رزرو آنلاین، احراز سالن (کد ملی مالک و پروانه‌ی کسب) را کامل کنید.
            @endswitch
        </span>
        @if ($__vSalon->verification_status !== 'pending')
            <a href="{{ route('admin.verification.show') }}" class="underline font-bold" style="color: var(--admin-accent);">احراز سالن</a>
        @endif
    </div>
@endif
