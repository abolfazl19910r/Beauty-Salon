{{-- سربرگ پیش‌فرض (طلایی ماهرو؛ هر چهار قالب پولی سربرگ خودشان را دارند): لوگو + نام، منو، حساب، «رزرو نوبت» --}}
<header id="main-navbar" class="fixed top-0 inset-x-0 z-50">
    <nav class="container mx-auto px-4 h-20 flex items-center justify-between">
        <div class="flex items-center gap-2 min-w-0">
            @include('partials.site-header.pwa-back')
            <a href="{{ route('home') }}" class="flex items-center gap-2 shrink-0">
                {{-- ⭐ لوگوی اختصاصی سالن (۲۰۲۶-۰۹-۲۴) به‌جای آیکون پیش‌فرض، وقتی سالن لوگو داره. --}}
                <x-brand-emblem class="h-10 md:h-11 w-auto max-w-[140px]" />
                <span class="text-xl md:text-2xl font-serif-fa font-bold text-m-gold2">{{ $currentSalonName }}</span>
            </a>
        </div>

        <div class="hidden md:flex items-center gap-6 text-sm">
            @include('partials.mahru-theme-toggle')
            {{-- بسته‌ی «ح»، ۱۱: دسته‌بندی خدمات در هدر --}}
            @include('partials.site-header.nav')
            {{-- بسته‌ی ۴ ظاهری (۲۰۲۶-۱۱-۲۵): ردیف اول = منو + منوی حساب + یک دکمه‌ی طلایی «رزرو نوبت» --}}
            @include('partials.site-header.account')
            <a href="{{ route('bookings.create') }}" class="btn-gold rounded-full px-5 py-2 text-sm font-semibold" data-header-book>رزرو نوبت</a>
        </div>

        @include('partials.site-header.mobile-toggle')
    </nav>

    @include('partials.site-header.mobile-menu')
</header>
