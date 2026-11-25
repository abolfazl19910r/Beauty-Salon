{{-- بسته‌ی ۵الف: دکمه‌ی تم و منوی همبرگری موبایل سربرگ سایت سالن (مشترک همه‌ی چیدمان‌ها؛ متغیرها از layouts/app) --}}
<div class="flex md:hidden items-center gap-2">
@include('partials.mahru-theme-toggle')
<button id="mobile-menu-button" class="p-2 rounded-full border border-m-gold/30 text-m-tx" aria-label="باز کردن منو">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
    </svg>
</button>
</div>
