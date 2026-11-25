{{--
    بسته‌ی ۵ج: دکمه‌ی «برگشت» سربرگ، فقط در حالت نصب‌شده روی گوشی (.m-pwa-back در partials/pwa-shell) و نه در صفحه‌ی خانه.
    ورودی: $home (نشانی برگشت وقتی صفحه‌ی قبلی از همین سایت نیست)، $isHome (bool)، $class (اختیاری).
--}}
@unless ($isHome ?? false)
    <a href="{{ $home }}" class="m-pwa-back items-center justify-center w-9 h-9 shrink-0 rounded-full {{ $class ?? 'border border-m-gold/30 text-m-tx' }}" data-pwa-back aria-label="برگشت">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
    </a>
@endunless
