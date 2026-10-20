{{--
    دکمه‌ی «باز کردن در اپ ماهرو» روی صفحه‌ی وب سالن (بسته‌ی ۳ب، تصمیم ۲۰۲۶-۱۰-۱۴) و «نصب اپ» (بسته‌ی ۵) — فقط اندروید
    (اپ آیفون نداریم؛ آیفون PWA و بنر جدای خودش). پیش‌فرض پنهان است و اسکریپت پایین فقط روی اندروید نشانش می‌دهد؛ بستنش تا پایان همین نشست مرورگر می‌ماند.
    لینک‌ها: App\Support\CustomerAppLink. ورودی: $appSalon (سالن جاری).
--}}
@php($appLink = \App\Support\CustomerAppLink::forSalon($appSalon))
<div data-open-in-app hidden
     style="position:fixed;left:1rem;right:1rem;bottom:1rem;z-index:60;background:rgb(var(--m-card));color:rgb(var(--m-tx));border:1px solid rgb(var(--m-gold) / .45);border-radius:1rem;box-shadow:var(--m-sh-soft);padding:.85rem 1rem;font-family:"Mahru",Vazirmatn,sans-serif;"
     dir="rtl">
    <div style="display:flex;align-items:center;gap:.75rem;">
        <img src="{{ asset('brand/mahru-customer-app-192.png') }}" alt="" width="40" height="40" style="border-radius:10px;flex-shrink:0;">
        <div style="flex:1;min-width:0;">
            <div style="font-weight:700;font-size:.95rem;">«{{ $appSalon->name }}» در اپ ماهرو</div>
            <div style="font-size:.8rem;opacity:.75;">رزرو، پرداخت و نوبت‌هایتان در اپ</div>
        </div>
        <button type="button" data-open-in-app-close aria-label="بستن"
                style="background:none;border:0;color:inherit;font-size:1.4rem;line-height:1;opacity:.7;cursor:pointer;">&times;</button>
    </div>
    <div style="display:flex;gap:.5rem;margin-top:.7rem;">
        <a href="{{ $appLink['intent_url'] }}" data-open-in-app-link
           style="flex:1;text-align:center;background:var(--m-btn);color:var(--m-on-gold);font-weight:700;border-radius:999px;padding:.6rem .75rem;text-decoration:none;">باز کردن در اپ</a>
        {{-- بسته‌ی ۵: «نصب اپ» به صفحه‌ی /app خود سالن (دانلود APK از ماهرو + کد سالن) --}}
        <a href="{{ $appLink['install_url'] }}" data-open-in-app-install
           style="flex:1;text-align:center;border:1px solid rgb(var(--m-gold) / .6);color:rgb(var(--m-gold2));font-weight:700;border-radius:999px;padding:.6rem .75rem;text-decoration:none;">نصب اپ</a>
    </div>
</div>
<script>
    (function () {
        var box = document.querySelector('[data-open-in-app]');
        if (!box || !/Android/i.test(navigator.userAgent)) {
            return;
        }
        var key = 'mahru-open-in-app-closed';
        try {
            if (sessionStorage.getItem(key)) {
                return;
            }
        } catch (e) {}
        box.hidden = false;
        box.querySelector('[data-open-in-app-close]').addEventListener('click', function () {
            box.hidden = true;
            try { sessionStorage.setItem(key, '1'); } catch (e) {}
        });
    })();
</script>
