<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl" class="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @auth
        <meta name="user-logged-in" content="true">
    @endauth
    @include('partials.favicons')

    <title>@yield('title') | {{ $currentSalonName }}</title>
    {{-- سئو (۲۰۲۶-۱۱-۲۳): صفحه‌های عمومی سالن تأییدشده canonical/description/OG می‌گیرند و خانه JSON-LD ‏BeautySalon؛
         صفحه‌های مشتری واردشده و پیش‌نمایش سالن احرازنشده noindex. description صفحه: @section('description') --}}
    @php $seoSalon = app(\App\Support\CurrentSalon::class)->get(); @endphp
    @include('partials.seo', [
        'seoTitle' => trim($__env->yieldContent('title')).' | '.$currentSalonName,
        'seoDescription' => trim($__env->yieldContent('description')) ?: ($seoSalon ? \App\Support\Seo\StructuredData::salonDescription($seoSalon) : null),
        'seoImage' => $seoSalon ? \App\Support\Seo\StructuredData::salonImage($seoSalon) : null,
        'seoJsonLd' => $seoSalon && request()->routeIs('home') ? [\App\Support\Seo\StructuredData::salon($seoSalon)] : [],
    ])
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])

    @include('partials.mahru-theme')
    <style>
        /* بسته‌ی ۴ ظاهری: منوی «⋯» ردیف «نوبت‌های من» (components/customer/row-menu) */
        .cu-menu{position:relative}
        .cu-menu>summary{list-style:none}
        .cu-menu>summary::-webkit-details-marker{display:none}
        .cu-menu__btn{width:38px;height:38px;display:inline-flex;align-items:center;justify-content:center;border-radius:999px;cursor:pointer;border:1px solid var(--m-line2);color:rgb(var(--m-tx));background:transparent}
        .cu-menu__btn:hover,.cu-menu[open]>.cu-menu__btn{border-color:rgb(var(--m-gold));background:rgb(var(--m-gold) / .1)}
        .cu-menu__panel{position:absolute;z-index:45;top:calc(100% + 6px);left:0;width:15rem;padding:.35rem;display:flex;flex-direction:column;background:rgb(var(--m-bg2));border:1px solid var(--m-line2);border-radius:14px;box-shadow:var(--m-sh-soft)}
        .cu-menu__item{display:block;width:100%;text-align:right;padding:.55rem .7rem;border-radius:9px;font:inherit;font-size:.88rem;color:rgb(var(--m-tx));background:transparent;border:0;cursor:pointer;text-decoration:none}
        .cu-menu__item:hover,.cu-menu__item:focus-visible{background:rgb(var(--m-gold) / .1);outline:none}
        .cu-menu__item--danger{color:rgb(var(--m-bad));font-weight:700}
        .cu-menu__item--danger:hover,.cu-menu__item--danger:focus-visible{background:rgb(var(--m-bad) / .1)}
        .cu-menu__note{display:block;padding:0 .7rem .45rem;font-size:.75rem;line-height:1.7;color:rgb(var(--m-tx) / .6)}
        .cu-menu__sep{height:1px;background:var(--m-line2);margin:.35rem .25rem}
    </style>
    @stack('styles')
    @include('partials.ui-handlers')
</head>
<body class="font-vazir min-h-screen flex flex-col {{ auth()->check() && \App\Support\Customer\CustomerMenu::isCustomer(auth()->user()) ? 'pb-20 md:pb-0' : '' }}" dir="rtl">
@if (! empty($salonUnverifiedPreview))
    {{-- بسته‌ی مجوز 5د: کادر سالنِ هنوز تأییدنشده پیش‌نمایش می‌بیند --}}
    <div class="bg-m-warn/20 border-b border-m-warn/40 text-center text-sm py-2 px-4" data-unverified-preview role="status">
        پیش‌نمایش برای کادر سالن: تا تأیید احراز سالن، این صفحه برای مشتری‌ها باز نیست و رزرو ممکن نیست.
        <a href="{{ \App\Support\CentralUrl::route('admin.verification.show') }}" class="underline">احراز سالن</a>
    </div>
@endif

{{-- =========================================================
     (Sticky Glass Navbar)
========================================================= --}}
@php
    // بسته‌ی ۴ ظاهری: منوی حساب مشتری از App\Support\Customer\CustomerMenu (یک منبع برای منوی حساب، تب‌ها و منوی پایین موبایل)
    $__isCustomer = auth()->check() && \App\Support\Customer\CustomerMenu::isCustomer(auth()->user());
    $__accountTabs = $__isCustomer ? \App\Support\Customer\CustomerMenu::tabs(auth()->user()) : [];
    $__showAccountTabs = $__isCustomer && \App\Support\Customer\CustomerMenu::isAccountPage();
    $__navCategories = \App\Support\SalonNavCategories::get();
    // دوره‌های درمانی (بسته‌ی ب): فقط با امکان پلن سالن
    $__navSalon = app(\App\Support\CurrentSalon::class)->get();
    $__coursesOn = $__navSalon?->hasFeature(\App\Support\Features\Feature::TreatmentCourses) ?? false;
    $__hasCourses = $__navSalon && auth()->check() && \App\Models\CustomerCourse::query()->where('user_id', auth()->id())->where('status', '!=', 'cancelled')->exists();
    // بسته‌ی ۵الف: هر چیدمان سربرگ و پانویس خودش را دارد (partials/site-header/*، partials/site-footer)
    $__siteAppearance = app(\App\Support\Themes\CurrentAppearance::class)->get();
    $__siteLayout = $__siteAppearance->isDefaultSite() ? 'mahru' : $__siteAppearance->layout;
    $__headerVariant = in_array($__siteLayout, ['signature', 'app'], true) ? $__siteLayout : 'default';
@endphp
@include('partials.site-header.'.$__headerVariant)


<div id="announcement-banner-mount" class="container mx-auto px-4 pt-24 space-y-3" dir="rtl"
     data-announcements-url="{{ route('announcements.active') }}"></div>

@include('partials.booking-paused-banner', ['panel' => 'site'])
@include('partials.terms-update-banner')


@push('scripts')
<script @nonce>
/**
 * ⭐ Blade + vanilla JS replacement for the removed AnnouncementBanner.jsx — same behavior:
 * fetches active announcements, renders by priority tier (>=100 red / >=71 orange / >=31 yellow /
 * else blue), a dismiss button on everything except priority>=100 (critical announcements can't
 * be dismissed), and persists dismissals in localStorage under the same 'dismissedAnnouncements'
 * key the old component used (so a returning visitor's prior dismissals still apply).
 */
(function () {
    var mount = document.getElementById('announcement-banner-mount');
    if (!mount) return;

    var TIERS = [
        { min: 100, bg: 'bg-m-bad/10 border-m-bad', text: 'text-m-tx', icon: 'text-m-bad', path: 'M12 9v3.75m-9.303 3.376C1.83 17.502 2.626 19 3.928 19h16.144c1.302 0 2.098-1.498 1.231-2.874L13.06 4.75c-.65-1.05-2.17-1.05-2.82 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z' },
        { min: 71, bg: 'bg-m-warn/10 border-m-warn', text: 'text-m-tx', icon: 'text-m-warn', path: 'M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z' },
        { min: 31, bg: 'bg-m-gold/10 border-m-gold', text: 'text-m-tx', icon: 'text-m-gold', path: 'M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z' },
        { min: -Infinity, bg: 'bg-m-card border-m-gold/40', text: 'text-m-tx', icon: 'text-m-gold2', path: 'M11.25 11.25l.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z' },
    ];

    var TYPE_LABELS = { general: '📢 عمومی', maintenance: '🔧 تعمیرات', promotion: '🎉 تبلیغاتی' };

    function tierFor(priority) {
        for (var i = 0; i < TIERS.length; i++) {
            if (priority >= TIERS[i].min) return TIERS[i];
        }
        return TIERS[TIERS.length - 1];
    }

    function getDismissed() {
        try {
            return JSON.parse(localStorage.getItem('dismissedAnnouncements') || '[]');
        } catch (e) {
            return [];
        }
    }

    function dismiss(id) {
        var dismissed = getDismissed();
        dismissed.push(id);
        localStorage.setItem('dismissedAnnouncements', JSON.stringify(dismissed));
        var el = mount.querySelector('[data-announcement-id="' + id + '"]');
        if (el) el.remove();
    }

    function render(announcements) {
        var dismissed = getDismissed();
        var visible = announcements.filter(function (a) { return dismissed.indexOf(a.id) === -1; });

        visible.forEach(function (announcement) {
            var tier = tierFor(announcement.priority);

            var card = document.createElement('div');
            card.className = tier.bg + ' border-r-4 rounded-2xl p-4 relative fade-in';
            card.setAttribute('data-announcement-id', announcement.id);

            var badge = '';
            if (announcement.priority >= 31) {
                var typeLabel = announcement.type && TYPE_LABELS[announcement.type]
                    ? '<span class="inline-block px-2 py-1 bg-m-tx/10 rounded-full text-xs">' + TYPE_LABELS[announcement.type] + '</span>'
                    : '';
                badge = '<div class="mt-2 flex items-center gap-2">'
                    + '<span class="inline-block px-2 py-1 bg-m-tx/10 rounded-full text-xs font-semibold">اولویت بالا: ' + announcement.priority + '</span>'
                    + typeLabel
                    + '</div>';
            }

            var dismissButton = '';
            if (announcement.priority < 100) {
                dismissButton = '<button type="button" class="announcement-dismiss ' + tier.icon + ' hover:opacity-70 transition-opacity flex-shrink-0" title="بستن این اطلاعیه">'
                    + '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>'
                    + '</button>';
            }

            card.innerHTML = '<div class="flex items-start gap-3">'
                + '<svg class="h-6 w-6 ' + tier.icon + ' flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="' + tier.path + '" /></svg>'
                + '<div class="flex-1">'
                + '<h3 class="font-bold text-lg ' + tier.text + ' mb-1"></h3>'
                + '<p class="' + tier.text + ' text-sm whitespace-pre-line"></p>'
                + badge
                + '</div>'
                + dismissButton
                + '</div>';

            // title/content set via textContent (not innerHTML) so announcement text can never
            // inject markup — the same trust boundary React's JSX escaping gave the old version.
            card.querySelector('h3').textContent = announcement.title;
            card.querySelector('p').textContent = announcement.content;

            var closeBtn = card.querySelector('.announcement-dismiss');
            if (closeBtn) {
                closeBtn.addEventListener('click', function () { dismiss(announcement.id); });
            }

            mount.appendChild(card);
        });
    }

    fetch(mount.dataset.announcementsUrl)
        .then(function (response) { return response.ok ? response.json() : []; })
        .then(render)
        .catch(function (error) { console.error('Error fetching announcements:', error); });
})();
</script>
@endpush

<main class="container mx-auto px-4 pb-8 flex-grow fade-in @unless(trim($__env->yieldContent('full-width'))) pt-4 @endunless">
    @if ($__showAccountTabs)
        <div class="md:grid md:grid-cols-[15.5rem_minmax(0,1fr)] md:gap-6 md:items-start" data-account-layout>
            @include('partials.customer-sidebar')
            <div class="min-w-0">
    @endif
    @if(session('success'))
        <div class="bg-m-ok/30 border-r-4 border-m-gold p-4 text-m-ok rounded mb-4 flex items-start">
            <svg class="h-5 w-5 ml-2 text-m-gold2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-m-bad/30 border-r-4 border-m-bad p-4 text-m-bad rounded mb-4 flex items-start">
            <svg class="h-5 w-5 ml-2 text-m-bad mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
            <div>{{ session('error') }}</div>
        </div>
    @endif

    @if(session('info'))
        <div class="bg-m-info/30 border-r-4 border-m-info p-4 text-m-info rounded mb-4 flex items-start">
            <svg class="h-5 w-5 ml-2 text-m-info mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
            </svg>
            <div>{{ session('info') }}</div>
        </div>
    @endif

    @yield('content')
    @if ($__showAccountTabs)
            </div>
        </div>
    @endif
</main>

{{-- =========================================================
     National footer
========================================================= --}}
@if ($__isCustomer)
    {{-- بسته‌ی ۴ ظاهری: منوی پایین ثابت موبایل «خانه / نوبت‌ها / [+ رزرو] / کیف پول / پروفایل» در همه‌ی صفحه‌ها برای مشتری واردشده --}}
    <nav class="md:hidden fixed bottom-0 inset-x-0 z-40 border-t border-m-gold/15 bg-m-bg2/95 backdrop-blur grid grid-cols-5" aria-label="منوی پایین" data-customer-bottom>
        @foreach (\App\Support\Customer\CustomerMenu::bottom($__accountTabs) as $__item)
            @if ($__item['key'] === 'book')
                <a href="{{ $__item['url'] }}" class="flex flex-col items-center justify-center gap-0.5 py-1.5 text-[11px] font-bold text-m-gold2" @if ($__item['active']) aria-current="page" @endif>
                    <span class="w-11 h-11 -mt-5 rounded-full btn-gold flex items-center justify-center shadow-lg" aria-hidden="true">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" d="{{ $__item['icon'] }}" /></svg>
                    </span>
                    {{ $__item['label'] }}
                </a>
            @else
                <a href="{{ $__item['url'] }}" @if ($__item['active']) aria-current="page" @endif
                   class="relative flex flex-col items-center justify-center gap-1 py-2.5 text-[11px] {{ $__item['active'] ? 'text-m-gold2 font-bold' : 'text-m-tx/70' }}">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $__item['icon'] }}" /></svg>
                    {{ $__item['label'] }}
                    @if ($__item['badge'] > 0)
                        <span class="absolute text-[10px] font-bold rounded-full px-1.5 bg-m-gold text-m-ongold" style="top:4px; right:calc(50% - 24px);" data-account-badge>{{ fa_num($__item['badge']) }}</span>
                    @endif
                </a>
            @endif
        @endforeach
    </nav>
@endif
@include($__siteLayout === 'mahru' ? 'partials.site-footer.default' : 'partials.site-footer.palette')

<script @nonce>
    // نقشه‌ی فوتر فقط با کلیک بار می‌شود
    document.querySelector('[data-location-map-load]')?.addEventListener('click', function () {
        var box = document.querySelector('[data-location-map]');
        if (!box.firstChild) {
            var frame = document.createElement('iframe');
            frame.src = box.dataset.src;
            frame.title = 'نقشه‌ی محل سالن';
            frame.referrerPolicy = 'no-referrer';
            frame.className = 'absolute inset-0 w-full h-full border-0';
            box.appendChild(frame);
        }
        var open = box.classList.toggle('hidden') === false;
        this.setAttribute('aria-expanded', open ? 'true' : 'false');
        this.textContent = open ? 'بستن نقشه' : 'نمایش نقشه';
    });

    document.getElementById('mobile-menu-button')?.addEventListener('click', function() {
        document.getElementById('mobile-menu').classList.toggle('hidden');
    });

    // منوی دسته‌بندی خدمات هدر (بسته‌ی «ح»، ۱۱): کلیک یا نگه داشتن نشانگر؛ کلیک بیرون و Esc می‌بندد
    (function () {
        // بسته‌ی ۴ ظاهری: منوی «⋯» ردیف‌ها — پنل fixed کنار دکمه، فقط یکی باز، بستن با کلیک بیرون/Escape
        var menus = document.querySelectorAll('[data-cu-menu-root]');
        if (!menus.length) return;
        function closeAll(except) { menus.forEach(function (m) { if (m !== except) m.open = false; }); }
        function place(menu) {
            var r = menu.querySelector('summary').getBoundingClientRect(), panel = menu.querySelector('.cu-menu__panel');
            panel.style.position = 'fixed';
            var w = panel.offsetWidth, h = panel.offsetHeight, top = r.bottom + 6;
            if (top + h > window.innerHeight - 8 && r.top - h - 6 > 8) top = r.top - h - 6;
            panel.style.left = Math.max(8, Math.min(r.left, window.innerWidth - w - 8)) + 'px';
            panel.style.top = top + 'px';
        }
        menus.forEach(function (menu) {
            menu.addEventListener('toggle', function () {
                if (!menu.open) return;
                closeAll(menu); place(menu);
                var first = menu.querySelector('[role=menuitem]');
                if (first) first.focus({ preventScroll: true });
            });
        });
        document.addEventListener('click', function (e) { if (!e.target.closest('[data-cu-menu-root]')) closeAll(null); });
        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Escape') return;
            var open = document.querySelector('[data-cu-menu-root][open]');
            if (open) { open.open = false; open.querySelector('summary').focus(); }
        });
        window.addEventListener('scroll', function () {
            var open = document.querySelector('[data-cu-menu-root][open]');
            if (open) place(open);
        }, true);
    })();
    (function () {
        // بسته‌ی ۴ ظاهری: منوی حساب سربرگ (<details>) با کلیک بیرون و Escape بسته می‌شود
        var menu = document.querySelector('[data-account-menu]');
        if (!menu) return;
        document.addEventListener('click', function (e) { if (menu.open && !menu.contains(e.target)) menu.open = false; });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && menu.open) { menu.open = false; menu.querySelector('summary').focus(); } });
    })();
    (function () {
        var box = document.querySelector('[data-nav-categories]');
        if (!box) return;
        var btn = box.querySelector('[data-nav-categories-btn]'), panel = box.querySelector('[data-nav-categories-panel]'), chevron = box.querySelector('[data-nav-categories-chevron]'), timer;
        function set(open) {
            panel.classList.toggle('hidden', !open);
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (chevron) chevron.style.transform = open ? 'rotate(180deg)' : '';
        }
        btn.addEventListener('click', function () { set(panel.classList.contains('hidden')); });
        box.addEventListener('mouseenter', function () { clearTimeout(timer); set(true); });
        box.addEventListener('mouseleave', function () { timer = setTimeout(function () { set(false); }, 180); });
        document.addEventListener('click', function (e) { if (!box.contains(e.target)) set(false); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') set(false); });
    })();

    const navbar = document.getElementById('main-navbar');
    const onScroll = () => navbar.classList.toggle('scrolled', window.scrollY > 30);
    window.addEventListener('scroll', onScroll);
    onScroll();

    setTimeout(() => {
        document.querySelectorAll('[class*="border-r-4"]').forEach(alert => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s';
            setTimeout(() => alert.remove(), 500);
        });
    }, 5000);
</script>

{{-- بسته‌ی ۶: PWA (service worker، بنر نصب آیفون) --}}
@include('partials.pwa-client', ['pwaMode' => 'salon', 'pwaGuideUrl' => route('salon.app')])
@stack('scripts')
@include('partials.password-reveal')
</body>
</html>
