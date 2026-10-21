{{--
    بسته‌ی ۶ (PWA): ثبت service worker، کنار گذاشتن پیشنهاد نصب PWA کروم اندروید (اندروید اپ بومی دارد — بنر «باز کردن در اپ»)،
    و بنر «روی آیفون نصب کنید» (فقط آیفون/آیپد، وقتی هنوز از صفحه‌ی اصلی باز نشده؛ بستنش ۳۰ روز می‌ماند).
    ورودی: $pwaMode = salon | specialist، $pwaBanner (bool، پیش‌فرض true)، $pwaGuideUrl (صفحه‌ی راهنما).
--}}
@php
    $pwaBanner = $pwaBanner ?? true;
    $pwaSw = config('pwa.service_worker') && ! file_exists(public_path('hot'));
@endphp
@if ($pwaBanner)
    <div data-ios-install-banner hidden dir="rtl"
         style="position:fixed;left:.75rem;right:.75rem;bottom:calc({{ $pwaMode === 'specialist' ? '5rem' : '.75rem' }} + env(safe-area-inset-bottom));z-index:60;background:rgb(var(--m-card));color:rgb(var(--m-tx));border:1px solid rgb(var(--m-gold) / .45);border-radius:1rem;box-shadow:var(--m-sh-soft);padding:.8rem 1rem;font-family:'Mahru',Vazirmatn,sans-serif;">
        <div style="display:flex;align-items:center;gap:.75rem;">
            <div style="flex:1;min-width:0;">
                <div style="font-weight:700;font-size:.92rem;">{{ $pwaMode === 'specialist' ? 'پنل متخصص' : $currentSalonName }} روی صفحه‌ی اصلی آیفون</div>
                <div style="font-size:.78rem;opacity:.75;">مثل یک اپ باز شود، بدون نوار مرورگر</div>
            </div>
            <a href="{{ $pwaGuideUrl }}" data-ios-install-guide style="background:var(--m-btn);color:var(--m-on-gold);font-weight:700;border-radius:999px;padding:.5rem .9rem;text-decoration:none;font-size:.85rem;white-space:nowrap;">چطور؟</a>
            <button type="button" data-ios-install-close aria-label="بستن" style="background:none;border:0;color:inherit;font-size:1.4rem;line-height:1;opacity:.7;cursor:pointer;">&times;</button>
        </div>
    </div>
@endif
<script>
    (function () {
        var standalone = window.navigator.standalone === true || (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches);
        var ios = /iPhone|iPad|iPod/.test(navigator.userAgent) || (/Macintosh/.test(navigator.userAgent) && navigator.maxTouchPoints > 1);

        // سایت نصب‌شده روی صفحه‌ی اصلی ساعت‌ها باز می‌ماند و صفحه (و توکن فرمش) کهنه می‌شود (۴۱۹، گزارش ۲۰۲۶-۱۰-۲۱): اگر بیش از عمر نشست
        // پشت صحنه بوده، با برگشت تازه بار شود.
        if (standalone) {
            var hiddenAt = 0;
            var staleMs = {{ max(5, (int) config('session.lifetime') - 2) }} * 60000;
            document.addEventListener('visibilitychange', function () {
                if (document.hidden) { hiddenAt = Date.now(); return; }
                if (hiddenAt && Date.now() - hiddenAt > staleMs) location.reload();
            });
            window.addEventListener('pageshow', function (e) { if (e.persisted) location.reload(); });
        }

        // اندروید: پیشنهاد نصب PWA کروم نه — اپ بومی ماهرو (بسته‌ی ۵)
        window.addEventListener('beforeinstallprompt', function (e) { e.preventDefault(); });

        @if ($pwaSw)
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () { navigator.serviceWorker.register('/sw.js').catch(function () {}); });
        }
        @endif

        var banner = document.querySelector('[data-ios-install-banner]');
        if (!banner || !ios || standalone) return;
        var key = 'mahru-ios-install-closed-{{ $pwaMode }}';
        try {
            var closed = Number(localStorage.getItem(key) || 0);
            if (closed && Date.now() - closed < 30 * 864e5) return;
        } catch (e) {}
        setTimeout(function () { banner.hidden = false; }, 1500);
        banner.querySelector('[data-ios-install-close]').addEventListener('click', function () {
            banner.hidden = true;
            try { localStorage.setItem(key, String(Date.now())); } catch (e) {}
        });
    })();
</script>
