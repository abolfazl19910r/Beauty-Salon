{{-- صفحه‌ی «اینترنت قطع است» سایت سالن/پنل متخصص نصب‌شده (بسته‌ی ۶) — service worker آن را نگه می‌دارد؛ بدون فایل بیرونی.
     بسته‌ی ۵ج: رنگ از پالت سالن (زیردامنه) یا طلایی ماهرو، روشن/تیره با همان کلید «mahru-theme» سایت؛ فقط پیام، هیچ داده‌ی حسابی. --}}
@php
    $__c = fn (string $mode, string $key, string $fallback) => $colors[$mode][$key] ?? $fallback;
@endphp
<!DOCTYPE html>
<html lang="fa" dir="rtl" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="{{ $__c('dark', 'bg', '#15110E') }}">
    <title>اتصال برقرار نیست</title>
    <script @nonce>
        (function () {
            var t = null;
            try { t = localStorage.getItem('mahru-theme'); } catch (e) {}
            if (t !== 'light' && t !== 'dark') t = window.matchMedia && matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark';
            document.documentElement.dataset.theme = t;
            var m = document.querySelector('meta[name="theme-color"]');
            if (m) m.setAttribute('content', t === 'light' ? '{{ $__c('light', 'bg', '#FBF8F2') }}' : '{{ $__c('dark', 'bg', '#15110E') }}');
        })();
    </script>
    <style>
        html[data-theme=dark] { --bg: {{ $__c('dark', 'bg', '#15110E') }}; --tx: {{ $__c('dark', 'text', '#F4EDE0') }}; --h: {{ $__c('dark', 'accentStrong', '#E3C985') }}; --ic: {{ $__c('dark', 'accent', '#D8B96A') }}; --btn: {{ $__c('dark', 'accent2', '#E3C985') }}; --on: {{ $__c('dark', 'onAccent2', '#221A0C') }}; }
        html[data-theme=light] { --bg: {{ $__c('light', 'bg', '#FBF8F2') }}; --tx: {{ $__c('light', 'text', '#21190F') }}; --h: {{ $__c('light', 'accentStrong', '#6B4E18') }}; --ic: {{ $__c('light', 'accent', '#8A6A22') }}; --btn: {{ $__c('light', 'accent2', '#6B4E18') }}; --on: {{ $__c('light', 'onAccent2', '#FFFFFF') }}; }
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; background: var(--bg); color: var(--tx);
               font-family: Vazirmatn, Tahoma, sans-serif; text-align: center; padding: 24px; box-sizing: border-box; }
        h1 { font-size: 20px; color: var(--h); margin: 16px 0 8px; }
        p { font-size: 14px; opacity: .75; line-height: 1.9; margin: 0 0 20px; }
        button { background: var(--btn); color: var(--on); border: 0; border-radius: 999px; padding: 12px 28px;
                 font: inherit; font-weight: 700; cursor: pointer; }
        svg { width: 56px; height: 56px; color: var(--ic); }
    </style>
    @include('partials.ui-handlers')
</head>
<body>
<main>
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
        <path d="M2 8.8a15 15 0 0 1 20 0"/><path d="M5 12.6a10 10 0 0 1 14 0"/><path d="M8.5 16.3a5 5 0 0 1 7 0"/><path d="M12 20h.01"/><path d="M3 3l18 18"/>
    </svg>
    <h1>اتصال به اینترنت برقرار نیست</h1>
    <p>برای دیدن نوبت‌ها و رزرو، اینترنت گوشی را بررسی کنید و دوباره امتحان کنید.</p>
    <button type="button" data-ui-click="reload">تلاش دوباره</button>
</main>
<script @nonce>uiActions.register({ reload: function () { location.reload(); } });</script>
</body>
</html>
