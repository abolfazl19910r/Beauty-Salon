{{-- بسته‌ی ۵ج: «برگشت» سربرگ سایت سالن در حالت نصب‌شده (مشترک همه‌ی چیدمان‌ها) --}}
@include('partials.pwa-back', ['home' => route('home'), 'isHome' => request()->routeIs('home')])
