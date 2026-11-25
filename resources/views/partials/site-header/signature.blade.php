{{--
    بسته‌ی ۵الف — سربرگ «شاهکار» (بوم «۴ قالب ترکیبی»): سه‌ستونه و وسط‌چین؛ منو | نشان + نام با نسخ | حساب و «رزرو نوبت».
    پیوندها، منوی حساب و کشوی موبایل همان partialهای مشترک (همان رفتار و همان اسکریپت layouts/app).
--}}
<header id="main-navbar" class="fixed top-0 inset-x-0 z-50 sg-hdr">
    <nav class="max-w-[1240px] mx-auto px-4 md:px-8 h-20 grid grid-cols-[1fr_auto_1fr] items-center gap-4">
        <div class="hidden md:flex items-center gap-7 text-[14.5px]">
            @include('partials.site-header.nav')
        </div>
        <div class="flex items-center gap-2 justify-self-start md:justify-self-center col-start-1 md:col-start-2 min-w-0">
            @include('partials.site-header.pwa-back')
            <a href="{{ route('home') }}" class="flex items-center gap-2.5 min-w-0">
                <x-brand-emblem class="h-9 w-auto max-w-[120px]" />
                <span class="sg-name truncate">{{ $currentSalonName }}</span>
            </a>
        </div>
        <div class="hidden md:flex items-center gap-5 justify-self-end text-[14.5px]">
            @include('partials.mahru-theme-toggle')
            @include('partials.site-header.account')
            <a href="{{ route('bookings.create') }}" class="sg-hdr__book" data-header-book>رزرو نوبت</a>
        </div>
        <div class="md:hidden justify-self-end col-start-3">
            @include('partials.site-header.mobile-toggle')
        </div>
    </nav>

    @include('partials.site-header.mobile-menu')
</header>
