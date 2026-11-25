{{--
    بسته‌ی ۵ج (۲۰۲۶-۱۱-۲۶) — حالت اپ در مرورگر گوشی: پایه‌ی مشترک سایت سالن، پنل کارشناس و صفحه‌های ورود (داخل <head>).
    - نما فقط با عرض صفحه (زیر md) و display-mode: standalone؛ user-agent برای چیدمان استفاده نمی‌شود.
    - html.is-standalone: سایت/پنل از صفحه‌ی اصلی گوشی باز شده (media query، و navigator.standalone آیفون).
    - ناحیه‌ی امن آیفون (نوار Home): با viewport-fit=cover، منوی پایین (.m-bottom-nav) و بدنه (body.m-has-bottom-nav) فاصله می‌گیرند؛
      --m-bottom-nav ارتفاع منوی پایین است تا بنرهای ثابت پایین (نصب آیفون، «باز کردن در اپ») رویش نیفتند. پنل کارشناس که خودش
      ناحیه‌ی اسکرول دارد body.m-nav-offset (فقط متغیر) و .m-pb-nav روی همان ناحیه می‌گیرد.
    - [data-pwa-hide] در حالت نصب‌شده پنهان می‌شود (بخش‌های بلند پانویس، بنرهای نصب)؛ .m-pwa-back دکمه‌ی «برگشت» سربرگ فقط در همان حالت.
    - رنگ نوار وضعیت (meta theme-color) با رنگ زمینه‌ی واقعی صفحه در تم روشن/تیره‌ی انتخاب‌شده یکی می‌شود.
--}}
<style>
    :root{--m-bottom-nav:0px}
    .m-bottom-nav{padding-bottom:env(safe-area-inset-bottom,0px)}
    @media (max-width:767.98px){
        body.m-has-bottom-nav,body.m-nav-offset{--m-bottom-nav:calc(4.5rem + env(safe-area-inset-bottom,0px))}
        body.m-has-bottom-nav{padding-bottom:calc(5rem + env(safe-area-inset-bottom,0px))}
        .m-pb-nav{padding-bottom:calc(6rem + env(safe-area-inset-bottom,0px))!important}
    }
    /* viewport-fit=cover: گوشی افقی، زیر بریدگی صفحه نرود */
    body,#main-navbar,.m-bottom-nav{padding-left:env(safe-area-inset-left,0px);padding-right:env(safe-area-inset-right,0px)}
    .m-pwa-back{display:none}
    @media (max-width:767.98px){html.is-standalone .m-pwa-back{display:inline-flex}}
    html.is-standalone [data-pwa-hide]{display:none!important}
</style>
<script @nonce>
(function () {
    var d = document.documentElement;
    var standalone = window.navigator.standalone === true || (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches);
    if (standalone) d.classList.add('is-standalone');

    function syncThemeColor() {
        var meta = document.querySelector('meta[name="theme-color"]');
        if (!meta || !document.body) return;
        var bg = getComputedStyle(document.body).backgroundColor;
        if (!bg || bg === 'transparent' || /rgba\(0, 0, 0, 0\)/.test(bg)) bg = getComputedStyle(d).backgroundColor;
        if (bg && bg !== 'transparent' && !/rgba\(0, 0, 0, 0\)/.test(bg)) meta.setAttribute('content', bg);
    }
    // زمینه با transition عوض می‌شود: رنگ وسط انتقال خوانده نشود (آزمون Chrome ویندوز ۵ج) — پایان transition و یک بار دیرتر هم
    function syncSoon() { syncThemeColor(); setTimeout(syncThemeColor, 450); }
    document.addEventListener('DOMContentLoaded', function () {
        syncThemeColor();
        document.body.addEventListener('transitionend', function (e) { if (e.target === document.body) syncThemeColor(); });
        if (window.MutationObserver) new MutationObserver(syncSoon).observe(d, { attributes: true, attributeFilter: ['data-theme', 'class'] });
    });

    // «برگشت» سربرگ در حالت نصب‌شده (آیفون دکمه‌ی برگشت مرورگر ندارد): صفحه‌ی قبلی همین سایت، وگرنه خانه
    document.addEventListener('click', function (e) {
        var btn = e.target.closest && e.target.closest('[data-pwa-back]');
        if (!btn) return;
        e.preventDefault();
        var sameSite = false;
        try { sameSite = !!document.referrer && new URL(document.referrer).origin === location.origin; } catch (err) {}
        if (sameSite && history.length > 1) history.back(); else location.href = btn.getAttribute('href');
    });
})();
</script>
