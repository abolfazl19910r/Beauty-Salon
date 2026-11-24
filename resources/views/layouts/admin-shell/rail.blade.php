{{--
    ⭐ بسته‌ی «و» — قالب پنل مدیر «ریل آیکونی».
    بسته‌ی ۲ب (پیش‌نمایش تأییدشده‌ی ۱۱-۲۴): ریل ۷۶px — نشان سالن، آیکون‌های «کار روزانه» با نشان شمار، جداکننده، ۴ آیکون گروه؛
    هر آیکون aria-label + title. کلیک / Tab / نگه‌داشتن نشانگر روی گروه ← پنل کشویی کنار ریل با نام گروه، «N مورد»، همه‌ی
    آیتم‌ها (آیتم جاری برجسته) و میانبر هر آیتم؛ Esc می‌بندد. پایین ریل «اشتراک و صورتحساب» و خروج. روی موبایل کشوی کناری.
--}}
@php
    $groupIcons = ['salon' => 'layers', 'marketing' => 'heart', 'finance' => 'pie', 'settings' => 'shield'];
    $svg = fn (string $inner, string $class = 'w-5 h-5') => '<svg class="'.$class.' shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$inner.'</svg>';
    $fixed = collect($menu)->firstWhere('collapsible', false);
    $groups = collect($menu)->where('collapsible', true)->values();
@endphp
<div class="adm-rail-slot hidden md:block">
    <nav class="adm-rail" data-nav-scroll aria-label="منوی پنل مدیر" data-adm-rail>
        <a href="{{ route('admin.dashboard') }}" class="adm-rail__brand" aria-label="پنل مدیریت {{ $adminSalon?->name }}" title="{{ $adminSalon?->name ?? 'پنل مدیریت' }}">
            @if ($adminSalon)
                <x-brand-emblem class="w-10 h-10 rounded-xl" pad="3px" />
            @else
                <span class="adm-rail__mark">م</span>
            @endif
        </a>

        @foreach ($fixed['items'] ?? [] as $item)
            <a href="{{ $item['url'] }}" data-menu-key="{{ $item['key'] }}" @if ($item['shortcut']) data-shortcut="{{ $item['shortcut'] }}" @endif
               aria-label="{{ $item['label'] }}" title="{{ $item['label'] }}{{ $item['shortcut'] ? ' ('.strtoupper($item['shortcut']).')' : '' }}"
               class="adm-rail__icon {{ $item['active'] ? 'is-active' : '' }}" @if ($item['active']) aria-current="page" @endif>
                {!! $svg($item['icon']) !!}
                @if ($item['count'] > 0)<span class="adm-rail__count" aria-hidden="true">{{ fa_num($item['count']) }}</span>@endif
                @if ($item['badge'])<span data-notif-badge class="adm-rail__count hidden" aria-hidden="true"></span>@endif
            </a>
        @endforeach

        <span class="adm-rail__sep" role="separator"></span>

        @foreach ($groups as $group)
            @php $groupActive = collect($group['items'])->contains('active', true); @endphp
            <button type="button" class="adm-rail__icon adm-rail__gbtn {{ $groupActive ? 'is-current' : '' }}"
                    data-rail-group="{{ $group['key'] }}" aria-expanded="false" aria-controls="rail-panel-{{ $group['key'] }}"
                    aria-label="{{ $group['label'] }}، {{ fa_num(count($group['items'])) }} مورد" title="{{ $group['label'] }}">
                {!! $svg(\App\Support\Admin\AdminMenu::ICONS[$groupIcons[$group['key']] ?? 'grid']) !!}
                @if (collect($group['items'])->sum('count') > 0)<span class="adm-rail__dot" aria-hidden="true"></span>@endif
            </button>
        @endforeach

        <span class="flex-1"></span>
        <a href="{{ route('admin.billing.index') }}" class="adm-rail__icon {{ request()->routeIs('admin.billing.*') ? 'is-active' : '' }}"
           aria-label="اشتراک و صورتحساب" title="اشتراک و صورتحساب">{!! $svg(\App\Support\Admin\AdminMenu::ICONS['receipt']) !!}</a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="adm-rail__icon adm-rail__logout" aria-label="خروج" title="خروج">{!! $svg(\App\Support\Admin\AdminMenu::ICONS['logout']) !!}</button>
        </form>
    </nav>

    @foreach ($groups as $group)
        <div id="rail-panel-{{ $group['key'] }}" class="adm-rail-panel" data-rail-panel="{{ $group['key'] }}" aria-label="{{ $group['label'] }}" hidden>
            <div class="adm-rail-panel__head"><b>{{ $group['label'] }}</b><span>{{ fa_num(count($group['items'])) }} مورد</span></div>
            @foreach ($group['items'] as $item)
                <a href="{{ $item['url'] }}" data-menu-key="{{ $item['key'] }}" @if ($item['shortcut']) data-shortcut="{{ $item['shortcut'] }}" @endif
                   class="adm-rail-panel__item {{ $item['active'] ? 'is-active' : '' }}" @if ($item['active']) aria-current="page" @endif>
                    <span class="flex-1">{{ $item['label'] }}@if ($item['lock']) <span title="در پلن سالن شما نیست">🔒</span>@endif</span>
                    @if ($item['count'] > 0)<span class="adm-count">{{ fa_num($item['count']) }}</span>@endif
                    @if ($item['shortcut'])<kbd dir="ltr" class="adm-rail-panel__kbd">{{ strtoupper($item['shortcut']) }}</kbd>@endif
                </a>
            @endforeach
            <p class="adm-rail-panel__note">با نگه‌داشتن نشانگر یا Tab باز می‌شود؛ <kbd>Esc</kbd> می‌بندد.</p>
        </div>
    @endforeach
</div>
<script @nonce>
    // پنل‌های کشویی ریل (بسته‌ی ۲ب): کلیک، Tab (فوکوس) یا نگه‌داشتن نشانگر؛ فقط یکی باز؛ Esc و کلیک بیرون می‌بندد
    (function () {
        var buttons = document.querySelectorAll('[data-rail-group]');
        if (!buttons.length) return;
        var openKey = null, hideTimer = null, suppressFocus = false;
        function panel(key) { return document.querySelector('[data-rail-panel="' + key + '"]'); }
        function btn(key) { return document.querySelector('[data-rail-group="' + key + '"]'); }
        function close() {
            if (!openKey) return;
            var p = panel(openKey), b = btn(openKey);
            if (p) p.hidden = true;
            if (b) { b.setAttribute('aria-expanded', 'false'); b.classList.remove('is-open'); }
            openKey = null;
        }
        function open(key) {
            clearTimeout(hideTimer);
            if (openKey === key) return;
            close();
            var p = panel(key), b = btn(key);
            if (!p || !b) return;
            var r = b.getBoundingClientRect();
            p.hidden = false;
            var top = Math.max(8, Math.min(r.top - 8, window.innerHeight - p.offsetHeight - 8));
            p.style.top = top + 'px';
            p.style.right = (window.innerWidth - r.left + 8) + 'px';
            b.setAttribute('aria-expanded', 'true'); b.classList.add('is-open');
            openKey = key;
        }
        function scheduleClose() { clearTimeout(hideTimer); hideTimer = setTimeout(close, 250); }
        buttons.forEach(function (b) {
            var key = b.getAttribute('data-rail-group');
            b.addEventListener('click', function () { openKey === key ? close() : open(key); });
            b.addEventListener('mouseenter', function () { open(key); });
            b.addEventListener('mouseleave', scheduleClose);
            b.addEventListener('focus', function () { if (suppressFocus) { suppressFocus = false; return; } open(key); });
            var p = panel(key);
            if (p) {
                p.addEventListener('mouseenter', function () { clearTimeout(hideTimer); });
                p.addEventListener('mouseleave', scheduleClose);
                p.addEventListener('focusout', function (e) { if (!p.contains(e.relatedTarget) && e.relatedTarget !== b) scheduleClose(); });
            }
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && openKey) { var b = btn(openKey); close(); if (b && document.activeElement !== b) { suppressFocus = true; b.focus({ preventScroll: true }); } }
            // از دکمه‌ی گروه با Tab به اولین آیتم پنل
            if (e.key === 'Tab' && !e.shiftKey && openKey && document.activeElement === btn(openKey)) {
                var first = panel(openKey).querySelector('a');
                if (first) { e.preventDefault(); first.focus(); }
            }
        });
        document.addEventListener('click', function (e) {
            if (openKey && !e.target.closest('[data-rail-panel]') && !e.target.closest('[data-rail-group]')) close();
        });
        window.addEventListener('resize', close);
    })();
</script>
