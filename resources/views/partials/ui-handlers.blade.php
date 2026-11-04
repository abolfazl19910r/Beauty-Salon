{{--
    ⭐ بررسی امنیتی H3 مرحله‌ی ۲ (۲۰۲۶-۱۱-۰۵): جایگزین همه‌ی handlerهای درون‌خطی (onclick/onmouseover/onsubmit/… که CSP سخت‌گیر
    — script-src فقط nonce — اجرایشان نمی‌کند). یک بار در <head> هر layout و هر صفحه‌ی مستقل؛ بی وابستگی و بی build. همه با
    event delegation روی document، پس عنصرهایی هم که بعداً با JS ساخته می‌شوند کار می‌کنند. ترتیب مثل handler درون‌خطی:
    از خود عنصر به سمت والدها (bubbling)؛ stopPropagation ادامه را می‌بُرد.

    ظاهر (هر مقدار «ویژگی: مقدار; …» مثل style):
      data-ui-hover / data-ui-unhover      ← onmouseover / onmouseout   (مثلاً data-ui-hover="background: var(--admin-border)")
      data-ui-focus / data-ui-blur         ← onfocus / onblur (فقط خود فیلد، مثل قبل)
    رفتارهای آماده:
      data-ui-confirm="پیام"               ← return confirm('پیام') — روی <form> هنگام ارسال، روی دکمه/لینک هنگام کلیک
      data-ui-once                         ← فرم فقط یک بار ارسال شود و دکمه‌ی submit غیرفعال شود
      data-ui-show / data-ui-hide="#id"    ← کلیک: برداشتن / گذاشتن کلاس hidden
      data-ui-backdrop[="#id"]             ← کلیک روی خود پس‌زمینه‌ی مودال (نه محتوایش): hidden (پیش‌فرض خود عنصر)
      data-ui-remove[="#id"]               ← کلیک: حذف عنصر (پیش‌فرض خود عنصر)
      data-ui-autosubmit                   ← change: this.form.submit()
      data-ui-navigate                     ← change: رفتن به this.value
    کارهای خاص هر صفحه (تابع‌های خود صفحه، در <script @nonce> همان صفحه):
      data-ui-click|change|input|submit="نام" و data-ui-args='[…]' (JSON، اختیاری)
      uiActions.register({ نام: function (event, ...args) { this = عنصر; return false = preventDefault } })
    فقط کارهای ثبت‌شده اجرا می‌شوند (نه هر تابع سراسری) تا HTML تزریقی نتواند تابع دلخواه صدا بزند.
    تست: InlineEventHandlersTest (هیچ on…= و javascript: در resources/views نماند).
--}}
<script data-ui-handlers @nonce>
(function () {
    if (window.uiActions && window.uiActions.__mahru) return;

    var actions = {};
    window.uiActions = {
        __mahru: true,
        register: function (map) {
            for (var name in map) {
                if (Object.prototype.hasOwnProperty.call(map, name) && typeof map[name] === 'function') actions[name] = map[name];
            }
        }
    };

    function applyStyle(el, decl) {
        if (!decl) return;
        decl.split(';').forEach(function (part) {
            var i = part.indexOf(':');
            if (i < 0) return;
            var prop = part.slice(0, i).trim();
            if (prop) el.style.setProperty(prop, part.slice(i + 1).trim());
        });
    }

    function targets(el, attr) {
        var sel = el.getAttribute(attr);
        if (!sel) return [el];
        return Array.prototype.slice.call(document.querySelectorAll(sel));
    }

    // از عنصر رویداد به سمت والدها، هر عنصری که attr دارد (مثل bubbling handler درون‌خطی)
    function walk(event, attr, fn) {
        for (var el = event.target; el && el.nodeType === 1; el = el.parentElement) {
            if (el.hasAttribute(attr)) {
                fn(el);
                if (event.cancelBubble) return;
            }
        }
    }

    function args(el) {
        var raw = el.getAttribute('data-ui-args');
        if (!raw) return [];
        try {
            var parsed = JSON.parse(raw);
            return Array.isArray(parsed) ? parsed : [parsed];
        } catch (e) {
            return [];
        }
    }

    function run(el, event, attr) {
        var fn = actions[el.getAttribute(attr)];
        if (!fn) {
            if (window.console) console.warn('uiActions: «' + el.getAttribute(attr) + '» ثبت نشده است');
            return;
        }
        if (fn.apply(el, [event].concat(args(el))) === false) event.preventDefault();
    }

    document.addEventListener('mouseover', function (e) {
        walk(e, 'data-ui-hover', function (el) { applyStyle(el, el.getAttribute('data-ui-hover')); });
    });
    document.addEventListener('mouseout', function (e) {
        walk(e, 'data-ui-unhover', function (el) { applyStyle(el, el.getAttribute('data-ui-unhover')); });
    });

    // focus/blur حباب نمی‌زنند — فقط خود فیلد، مثل onfocus/onblur
    document.addEventListener('focusin', function (e) {
        if (e.target.nodeType === 1 && e.target.hasAttribute('data-ui-focus')) applyStyle(e.target, e.target.getAttribute('data-ui-focus'));
    });
    document.addEventListener('focusout', function (e) {
        if (e.target.nodeType === 1 && e.target.hasAttribute('data-ui-blur')) applyStyle(e.target, e.target.getAttribute('data-ui-blur'));
    });

    document.addEventListener('click', function (e) {
        for (var el = e.target; el && el.nodeType === 1; el = el.parentElement) {
            if (el.tagName !== 'FORM' && el.hasAttribute('data-ui-confirm') && !window.confirm(el.getAttribute('data-ui-confirm'))) {
                e.preventDefault();
                return;
            }
            if (el.hasAttribute('data-ui-click')) run(el, e, 'data-ui-click');
            if (el.hasAttribute('data-ui-show')) targets(el, 'data-ui-show').forEach(function (t) { t.classList.remove('hidden'); });
            if (el.hasAttribute('data-ui-hide')) targets(el, 'data-ui-hide').forEach(function (t) { t.classList.add('hidden'); });
            if (el.hasAttribute('data-ui-backdrop') && e.target === el) targets(el, 'data-ui-backdrop').forEach(function (t) { t.classList.add('hidden'); });
            if (el.hasAttribute('data-ui-remove')) targets(el, 'data-ui-remove').forEach(function (t) { t.remove(); });
            if (e.cancelBubble) return;
        }
    }, true);

    document.addEventListener('change', function (e) {
        walk(e, 'data-ui-change', function (el) { run(el, e, 'data-ui-change'); });
        var el = e.target;
        if (el.nodeType !== 1) return;
        if (el.hasAttribute('data-ui-autosubmit') && el.form) el.form.submit();
        if (el.hasAttribute('data-ui-navigate')) window.location.href = el.value;
    }, true);

    document.addEventListener('input', function (e) {
        walk(e, 'data-ui-input', function (el) { run(el, e, 'data-ui-input'); });
    }, true);

    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form || form.nodeType !== 1) return;
        if (form.hasAttribute('data-ui-confirm') && !window.confirm(form.getAttribute('data-ui-confirm'))) {
            e.preventDefault();
            return;
        }
        if (form.hasAttribute('data-ui-submit')) {
            run(form, e, 'data-ui-submit');
            if (e.defaultPrevented) return;
        }
        if (form.hasAttribute('data-ui-once')) {
            if (form.dataset.submitted) {
                e.preventDefault();
                return;
            }
            form.dataset.submitted = '1';
            var button = form.querySelector('button[type=submit]');
            if (button) button.disabled = true;
        }
    }, true);
})();
</script>
