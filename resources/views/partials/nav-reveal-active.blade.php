{{--
    بسته‌ی «ح»، ۷ (۲۰۲۶-۱۰-۱۹): منوی کناری پنل‌ها اسکرول خودش را دارد؛ بعد از رفتن به صفحه‌ای که آیتمش پایین منوست (مثلاً
    «تنظیمات مالی»)، آیتم فعال برجسته بود ولی منو همیشه از بالا نشان داده می‌شد. هر ظرف با data-nav-scroll وقتی دیده می‌شود
    (بار صفحه، یا باز شدن کشوی موبایل که اندازه‌اش را از صفر عوض می‌کند) آیتم فعال را وسط خودش می‌آورد — فقط اسکرول همان منو،
    نه صفحه. اگر آیتم از قبل دیده می‌شود کاری نمی‌کند.
--}}
<script>
    (function () {
        var ACTIVE = '[aria-current="page"], .sidebar-active, .is-active';

        function reveal(box) {
            var item = box.querySelector(ACTIVE);
            if (!item || box.clientHeight === 0 || box.scrollHeight <= box.clientHeight) return;

            var boxRect = box.getBoundingClientRect();
            var itemRect = item.getBoundingClientRect();
            var visibleBottom = Math.min(boxRect.bottom, window.innerHeight);
            if (itemRect.top >= boxRect.top && itemRect.bottom <= visibleBottom) return;

            var visibleHeight = visibleBottom - boxRect.top;
            box.scrollTop += (itemRect.top - boxRect.top) - Math.max(0, (visibleHeight - itemRect.height) / 2);
        }

        function watch() {
            document.querySelectorAll('[data-nav-scroll]').forEach(function (box) {
                reveal(box);
                if (!('ResizeObserver' in window)) return;
                var wasHidden = box.clientHeight === 0;
                new ResizeObserver(function () {
                    var hidden = box.clientHeight === 0;
                    if (wasHidden && !hidden) reveal(box);
                    wasHidden = hidden;
                }).observe(box);
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', watch);
        } else {
            watch();
        }
    })();
</script>
