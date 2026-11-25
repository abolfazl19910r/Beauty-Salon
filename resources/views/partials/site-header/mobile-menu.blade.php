{{-- بسته‌ی ۵الف: کشوی منوی موبایل سربرگ سایت سالن (مشترک همه‌ی چیدمان‌ها؛ متغیرها از layouts/app) --}}
<div id="mobile-menu" class="hidden md:hidden bg-m-bg/95 border-t border-m-gold/15">
    <div class="container mx-auto px-4 py-4 flex flex-col gap-1 text-sm">
        @if ($__navCategories->isEmpty())
            <a href="{{ route('services.index') }}" class="block py-2 nav-link">خدمات</a>
        @else
            <details class="group" @if (request()->routeIs('services.*')) open @endif>
                <summary class="flex items-center justify-between py-2 nav-link cursor-pointer list-none">
                    خدمات
                    <svg class="w-4 h-4 transition-transform group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                </summary>
                <div class="grid grid-cols-2 gap-1 pb-2 pr-3">
                    <a href="{{ route('services.index') }}" class="col-span-2 py-1.5 text-m-gold2 font-semibold">همه‌ی خدمات</a>
                    @foreach ($__navCategories as $__navCategory)
                        <a href="{{ route('services.index', ['category' => $__navCategory->id]) }}" class="py-1.5 text-m-tx/75 truncate">{{ $__navCategory->name }}</a>
                    @endforeach
                </div>
            </details>
        @endif
        <a href="{{ route('specialists.search') }}" class="block py-2 nav-link">کارشناسان</a>
        @if ($__coursesOn)
            <a href="{{ route('courses.index') }}" class="block py-2 nav-link">دوره‌ها</a>
        @endif
        <a href="{{ route('blog.index') }}" class="block py-2 nav-link">وبلاگ</a>
        @if (($__reviewsCount ?? 0) > 0)
            <a href="{{ route('salon.reviews') }}" class="block py-2 nav-link">نظرها</a>
        @endif
        @auth
            @if ($__isCustomer)
                @foreach ($__accountTabs as $__tab)
                    <a href="{{ $__tab['url'] }}" class="block py-2 nav-link {{ $__tab['active'] ? 'text-m-gold2 font-bold' : '' }}">{{ $__tab['label'] }}</a>
                @endforeach
            @elseif (auth()->user()->isSpecialistAccount())
                <a href="{{ route('specialist.profile.show') }}" class="block py-2 nav-link">پروفایل من</a>
                <a href="{{ route('specialist.my-dashboard') }}" class="block py-2 nav-link">پنل کاری</a>
            @endif
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="block w-full text-right py-2 nav-link">خروج</button>
            </form>
        @else
            <a href="{{ route('salon.login') }}" class="block py-2 nav-link">ورود</a>
            <a href="{{ route('salon.register') }}" class="block py-2 text-m-gold2 font-bold">ثبت نام</a>
        @endauth
    </div>
</div>
