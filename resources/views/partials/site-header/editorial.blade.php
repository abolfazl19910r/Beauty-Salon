{{--
    بسته‌ی ۵ب — سربرگ «روزنامه‌ی سالن» (بوم «۴ قالب ترکیبی»): سطر تاریخ (امروز، ساعت کاری امروز، نزدیک‌ترین نوبت در صفحه‌ی خانه، حساب)
    و نام سالن با نسخ درشت میان خط دوتایی بالا و خط زیرین، منو و دکمه‌ی چهارگوش «رزرو نوبت». مثل سرصفحه‌ی روزنامه با صفحه
    بالا می‌رود؛ فقط در موبایل (بی سطر تاریخ) بالای صفحه می‌چسبد. پیوندها، منوی حساب و کشوی موبایل همان partialهای مشترک.
--}}
@php
    $__edToday = \App\Support\SalonWorkingHours::today($__navSalon?->working_hours);
    $__edNearest = $template['nearest'] ?? null;
    $__edStatus = collect([
        // «باز تا ۲۱» ← «امروز باز تا ۲۱»؛ «امروز از ۹» خودش «امروز» دارد
        $__edToday ? ($__edToday['open'] ? 'امروز '.$__edToday['text'] : $__edToday['text']) : null,
        is_array($__edNearest) ? 'نزدیک‌ترین نوبت '.($__edNearest['day_label'] === 'امروز' ? '' : $__edNearest['day_label'].' ').$__edNearest['label'] : null,
    ])->filter()->implode('، ');
@endphp
<header id="main-navbar" class="ed-hdr sticky md:static top-0 z-50">
    <div class="max-w-[1200px] mx-auto px-4 md:px-6 md:pt-[22px]">
        <div class="hidden md:flex items-center justify-between gap-4 pb-3 text-[13px] text-m-mu" data-ed-dateline>
            <span>{{ fa_jdate(now(), 'l j F Y') }}</span>
            @if ($__edStatus !== '')
                <span data-ed-status>{{ $__edStatus }}</span>
            @endif
            <div class="flex items-center gap-4 text-m-tx">
                @include('partials.mahru-theme-toggle')
                @include('partials.site-header.account')
            </div>
        </div>
        <div class="ed-mast flex items-center justify-between gap-4">
            <a href="{{ route('home') }}" class="ed-name truncate">{{ $currentSalonName }}</a>
            <nav class="hidden md:flex items-center gap-6 text-[15px]">
                @include('partials.site-header.nav')
                <a href="{{ route('bookings.create') }}" class="ed-hdr__book" data-header-book>رزرو نوبت</a>
            </nav>
            @include('partials.site-header.mobile-toggle')
        </div>
    </div>

    @include('partials.site-header.mobile-menu')
</header>
