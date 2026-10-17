{{--
    دکمه‌ی «چشم» داخل همه‌ی فیلدهای رمز (درخواست ۲۰۲۶-۱۰-۱۷): کاربر پیش از ذخیره/ورود رمزی را که نوشته ببیند.
    یک بار در هر صفحه، بدون وابستگی و بدون build (صفحه‌ی ثبت‌نام سالن @vite ندارد)؛ در همه‌ی layoutها پیش از </body>.
    هر input[type=password] (حتی آن‌هایی که بعداً با JS یا در مودال اضافه می‌شوند) یک دکمه در لبه‌ی چپ می‌گیرد — آیکن‌های
    موجود (قفل) سمت راست‌اند. با ارسال فرم دوباره «رمز» می‌شود تا مرورگر/مدیر رمز درست ذخیره کند. خاموش کردن برای یک فیلد:
    data-no-reveal.
--}}
<script data-password-reveal>
(function () {
    if (window.__mahruPasswordReveal) return;
    window.__mahruPasswordReveal = true;

    var EYE = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>';
    var EYE_OFF = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 19c-6.5 0-10-7-10-7a18.4 18.4 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c6.5 0 10 7 10 7a18.5 18.5 0 0 1-2.16 3.19"/><path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"/><path d="M1 1l22 22"/></svg>';
    var SHOW = 'نمایش رمز';
    var HIDE = 'پنهان کردن رمز';

    function setState(input, button, visible) {
        input.type = visible ? 'text' : 'password';
        button.innerHTML = visible ? EYE_OFF : EYE;
        button.setAttribute('aria-label', visible ? HIDE : SHOW);
        button.setAttribute('title', visible ? HIDE : SHOW);
        button.setAttribute('aria-pressed', visible ? 'true' : 'false');
    }

    function enhance(input) {
        if (input.dataset.revealReady || input.hasAttribute('data-no-reveal') || input.type !== 'password') return;
        input.dataset.revealReady = '1';

        var wrap = document.createElement('span');
        wrap.setAttribute('data-password-reveal-wrap', '');
        wrap.style.cssText = 'position:relative;display:block;width:100%';
        input.parentNode.insertBefore(wrap, input);
        wrap.appendChild(input);

        var button = document.createElement('button');
        button.type = 'button';
        button.setAttribute('data-password-reveal-toggle', '');
        button.style.cssText = 'position:absolute;top:50%;left:.5rem;transform:translateY(-50%);display:flex;align-items:center;justify-content:center;'
            + 'width:2rem;height:2rem;padding:0;border:0;border-radius:.375rem;background:transparent;color:inherit;opacity:.6;cursor:pointer;z-index:2';
        button.addEventListener('mouseenter', function () { button.style.opacity = '1'; });
        button.addEventListener('mouseleave', function () { button.style.opacity = '.6'; });
        button.addEventListener('click', function () {
            setState(input, button, input.type === 'password');
            input.focus();
        });
        wrap.appendChild(button);
        setState(input, button, false);

        // جا برای دکمه، بی‌آنکه padding راست (آیکن قفل) دست بخورد
        var current = parseFloat(window.getComputedStyle(input).paddingLeft) || 0;
        if (current < 40) input.style.paddingLeft = '2.5rem';

        if (input.form && !input.form.dataset.revealSubmit) {
            input.form.dataset.revealSubmit = '1';
            input.form.addEventListener('submit', function (event) {
                event.target.querySelectorAll('[data-password-reveal-toggle]').forEach(function (b) {
                    var field = b.parentNode.querySelector('input');
                    if (field) setState(field, b, false);
                });
            }, true);
        }
    }

    function scan(root) {
        if (!root || !root.querySelectorAll) return;
        if (root.matches && root.matches('input[type="password"]')) enhance(root);
        root.querySelectorAll('input[type="password"]').forEach(enhance);
    }

    function start() {
        scan(document);
        new MutationObserver(function (mutations) {
            mutations.forEach(function (m) { m.addedNodes.forEach(scan); });
        }).observe(document.body, { childList: true, subtree: true });
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
    else start();
})();
</script>
