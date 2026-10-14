{{-- ⭐ ناوبری مشترک صفحه‌های عمومی متخصص‌ها (۲۰۲۶-۰۹-۲۴). --}}
<div class="flex flex-wrap gap-2 mb-8 text-sm">
    <a href="{{ route('specialists.search') }}" class="px-4 py-2 rounded-full {{ request()->routeIs('specialists.search', 'legacy.specialists.search') ? 'font-bold' : 'opacity-80' }}"
       style="border: 1px solid rgb(var(--m-gold) / .35); {{ request()->routeIs('specialists.search', 'legacy.specialists.search') ? 'background: var(--m-btn); color: var(--m-on-gold);' : 'color: var(--rasta-gold-light);' }}">همه‌ی متخصص‌ها</a>
    <a href="{{ route('specialists.top-rated') }}" class="px-4 py-2 rounded-full {{ request()->routeIs('specialists.top-rated', 'legacy.specialists.top-rated') ? 'font-bold' : 'opacity-80' }}"
       style="border: 1px solid rgb(var(--m-gold) / .35); {{ request()->routeIs('specialists.top-rated', 'legacy.specialists.top-rated') ? 'background: var(--m-btn); color: var(--m-on-gold);' : 'color: var(--rasta-gold-light);' }}">برترین متخصص‌ها</a>
</div>
