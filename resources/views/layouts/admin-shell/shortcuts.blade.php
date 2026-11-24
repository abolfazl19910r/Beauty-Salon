{{--
    میانبرهای صفحه‌کلید پنل مدیر (بسته‌ی ۲ب، تصمیم ابوالفضل ۱۱-۲۵) — در هر ۴ پوسته.
    سبک استاندارد Gmail/GitHub: «?» راهنما، «/» یا Ctrl K جستجو، «g» و بعد یک حرف = رفتن به صفحه، «s» و بعد یک حرف = تنظیمات سالن،
    N = نوبت تازه. فقط وقتی فوکوس در فیلد متنی نیست؛ با e.code تا روی صفحه‌کلید فارسی هم کار کند. روشن/خاموش در همین پنجره
    (localStorage، پیش‌فرض روشن). Ctrl F و F3 دست نمی‌خورند (جستجوی خود مرورگر).
    ورودی: $menuAll (همه‌ی گروه‌های AdminMenu با شرط دسترسی) و $shell
--}}
@php
    $goItems = [];
    $settingsItems = [];
    foreach ($menuAll as $group) {
        foreach ($group['items'] as $item) {
            if (! empty($item['shortcut'])) {
                $item['group'] = $group['label'];
                str_starts_with($item['shortcut'], 's ') ? $settingsItems[] = $item : $goItems[] = $item;
            }
        }
    }
    $kbd = fn (string $sequence) => collect(explode(' ', $sequence))->map(fn ($k) => '<kbd>'.e(strtoupper($k)).'</kbd>')->implode(' <span class="adm-kbd-then">بعد</span> ');
@endphp
<dialog id="adm-shortcuts" class="adm-shortcuts" aria-labelledby="adm-shortcuts-title" data-adm-shortcuts>
    <div class="adm-shortcuts__head">
        <h2 id="adm-shortcuts-title">میانبرهای صفحه‌کلید</h2>
        <button type="button" class="adm-shortcuts__close" data-adm-shortcuts-close aria-label="بستن">✕</button>
    </div>
    <p class="adm-shortcuts__intro">کلیدها را <b>پشت‌سرهم</b> بزنید، نه با هم؛ مثلاً <kbd>G</kbd> و بعد <kbd>B</kbd> یعنی «برو به نوبت‌ها». وقتی داخل یک فیلد متنی هستید کار نمی‌کنند و روی صفحه‌کلید فارسی هم جواب می‌دهند.</p>
    <label class="adm-shortcuts__toggle">
        <input type="checkbox" data-adm-shortcuts-toggle checked>
        <span>میانبرها روشن باشند</span>
        <span class="adm-shortcuts__hint">(فقط در همین مرورگر ذخیره می‌شود)</span>
    </label>

    <div class="adm-shortcuts__grid">
        <section>
            <h3>عمومی</h3>
            <dl>
                <div><dt><kbd>?</kbd></dt><dd>همین راهنما</dd></div>
                <div><dt><kbd>/</kbd> یا <kbd dir="ltr">Ctrl K</kbd></dt><dd>جستجو</dd></div>
                <div><dt><kbd>N</kbd></dt><dd>ثبت نوبت تازه</dd></div>
                <div><dt><kbd>Esc</kbd></dt><dd>بستن پنجره یا منو</dd></div>
            </dl>
            @if ($shell === 'dense')
                <h3>فهرست نوبت‌ها (میز پذیرش)</h3>
                <dl>
                    <div><dt><kbd>J</kbd> / <kbd>K</kbd></dt><dd>نوبت بعد / قبل</dd></div>
                    <div><dt><kbd>Enter</kbd></dt><dd>صفحه‌ی جزئیات نوبت</dd></div>
                    <div><dt><kbd>T</kbd></dt><dd>تأیید نوبت انتخاب‌شده (با پرسش)</dd></div>
                </dl>
            @endif
            @if ($settingsItems)
                <h3>تنظیمات سالن</h3>
                <dl>
                    @foreach ($settingsItems as $item)
                        <div><dt>{!! $kbd($item['shortcut']) !!}</dt><dd>{{ $item['label'] }}</dd></div>
                    @endforeach
                </dl>
            @endif
        </section>
        <section>
            <h3>رفتن به صفحه</h3>
            <dl>
                @foreach ($goItems as $item)
                    <div><dt>{!! $kbd($item['shortcut']) !!}</dt><dd>{{ $item['label'] }}</dd></div>
                @endforeach
            </dl>
        </section>
    </div>
</dialog>
<div class="adm-seq" data-adm-seq hidden aria-live="polite"></div>
<script @nonce>
    (function () {
        var KEY = 'mahru-admin-shortcuts';
        var dialog = document.querySelector('[data-adm-shortcuts]');
        var toggle = document.querySelector('[data-adm-shortcuts-toggle]');
        var seqBox = document.querySelector('[data-adm-seq]');
        function enabled() { try { return localStorage.getItem(KEY) !== 'off'; } catch (e) { return true; } }
        window.mahruShortcuts = { enabled: enabled, open: openHelp };
        if (toggle) {
            toggle.checked = enabled();
            toggle.addEventListener('change', function () { try { localStorage.setItem(KEY, toggle.checked ? 'on' : 'off'); } catch (e) {} });
        }
        function openHelp() { if (dialog && !dialog.open) { toggle && (toggle.checked = enabled()); dialog.showModal(); } }
        document.querySelectorAll('[data-adm-shortcuts-open]').forEach(function (el) {
            el.addEventListener('click', function (e) { e.preventDefault(); openHelp(); });
        });
        document.querySelectorAll('[data-adm-shortcuts-close]').forEach(function (el) {
            el.addEventListener('click', function () { dialog.close(); });
        });
        if (dialog) dialog.addEventListener('click', function (e) { if (e.target === dialog) dialog.close(); });

        var map = {};
        document.querySelectorAll('[data-shortcut][href]').forEach(function (a) {
            var s = a.getAttribute('data-shortcut');
            if (!map[s]) map[s] = a.getAttribute('href');
        });

        function typing(el) {
            if (!el) return false;
            var tag = el.tagName;
            return tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || el.isContentEditable;
        }
        function letter(code) { return /^Key[A-Z]$/.test(code) ? code.slice(3).toLowerCase() : null; }

        var prefix = null, timer = null;
        function clearPrefix() { prefix = null; clearTimeout(timer); if (seqBox) seqBox.hidden = true; }

        document.addEventListener('keydown', function (e) {
            if (e.ctrlKey || e.metaKey || e.altKey) return;
            if (typing(e.target) || (dialog && dialog.open && e.code !== 'Escape')) return;
            if (!enabled()) return;
            if (document.querySelector('#search-modal:not(.hidden)')) return;

            if (e.code === 'Slash' && e.shiftKey) { e.preventDefault(); clearPrefix(); openHelp(); return; }
            if (e.code === 'Slash' && !e.shiftKey) {
                e.preventDefault(); clearPrefix();
                if (typeof toggleSearchModal === 'function') toggleSearchModal();
                return;
            }
            var l = letter(e.code);
            if (!l || e.shiftKey) { clearPrefix(); return; }

            if (prefix) {
                var target = map[prefix + ' ' + l];
                clearPrefix();
                if (target) { e.preventDefault(); window.location.href = target; }
                return;
            }
            if (l === 'g' || l === 's') {
                prefix = l;
                if (seqBox) { seqBox.textContent = l.toUpperCase() + ' …'; seqBox.hidden = false; }
                timer = setTimeout(clearPrefix, 1500);
                e.preventDefault();
                return;
            }
            if (l === 'n') {
                var nb = document.querySelector('[data-new-booking]');
                if (nb) { e.preventDefault(); window.location.href = nb.getAttribute('href'); }
            }
        });
    })();
</script>
