{{--
    بسته‌ی ۵ب — سربرگ «نمایشگاه» (بوم «۴ قالب ترکیبی»): نشان + نام با کوفی پررنگ در یک سو، منو، حساب و دکمه‌ی «رزرو نوبت» در سوی دیگر؛
    زمینه‌ی ساده با خط زیرین. پیوندها، منوی حساب و کشوی موبایل همان partialهای مشترک.
--}}
<header id="main-navbar" class="fixed top-0 inset-x-0 z-50 sc-hdr">
    <nav class="max-w-[1440px] mx-auto px-4 md:px-8 lg:px-[60px] h-[76px] flex items-center justify-between gap-4">
        <div class="flex items-center gap-2 min-w-0">
            @include('partials.site-header.pwa-back')
            <a href="{{ route('home') }}" class="flex items-center gap-2.5 shrink-0 min-w-0">
                <x-brand-emblem class="h-[30px] w-auto max-w-[120px]" />
                <span class="sc-hdr__name truncate">{{ $currentSalonName }}</span>
            </a>
        </div>
        <div class="hidden md:flex items-center gap-[22px] text-[14.5px]">
            @include('partials.site-header.nav')
            @include('partials.mahru-theme-toggle')
            @include('partials.site-header.account')
            <a href="{{ route('bookings.create') }}" class="sc-hdr__book" data-header-book>رزرو نوبت</a>
        </div>
        @include('partials.site-header.mobile-toggle')
    </nav>

    @include('partials.site-header.mobile-menu')
</header>
