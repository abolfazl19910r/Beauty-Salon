{{--
    بسته‌ی ۵الف — سربرگ «اپ» (بوم «۴ قالب ترکیبی»): نشان + نام و منو در یک سو، حساب و دکمه‌ی قرصی پررنگ «رزرو نوبت» در سوی دیگر،
    زمینه‌ی ساده با خط زیرین. پیوندها، منوی حساب و کشوی موبایل همان partialهای مشترک.
--}}
<header id="main-navbar" class="fixed top-0 inset-x-0 z-50 ap-hdr">
    <nav class="max-w-[1240px] mx-auto px-4 md:px-6 h-[72px] flex items-center justify-between gap-4">
        <div class="flex items-center gap-8 min-w-0">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5 shrink-0 min-w-0">
                <x-brand-emblem class="h-9 w-auto max-w-[120px]" />
                <b class="text-[19px] font-extrabold text-m-tx truncate">{{ $currentSalonName }}</b>
            </a>
            <div class="hidden md:flex items-center gap-6 text-[14.5px]">
                @include('partials.site-header.nav')
            </div>
        </div>
        <div class="hidden md:flex items-center gap-4 text-[14.5px]">
            @include('partials.mahru-theme-toggle')
            @include('partials.site-header.account')
            <a href="{{ route('bookings.create') }}" class="ap-hdr__book" data-header-book>رزرو نوبت</a>
        </div>
        @include('partials.site-header.mobile-toggle')
    </nav>

    @include('partials.site-header.mobile-menu')
</header>
