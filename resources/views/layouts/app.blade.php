<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl" class="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @auth
        <meta name="user-logged-in" content="true">
    @endauth
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

    <title>@yield('title') | راستا</title>
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Naskh+Arabic:wght@500;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --rasta-gold: #C9A24B;
            --rasta-gold-light: #E6CD8A;
            --rasta-cream: #F8F3E9;
            --rasta-dark: #1A1410;
            --rasta-brown: #2E2117;
        }

        .font-serif-fa {
            font-family: 'Noto Naskh Arabic', 'Vazirmatn', serif;
        }

        body {
            background-color: var(--rasta-dark);
            color: var(--rasta-cream);
        }

        .persian-number {
            -moz-font-feature-settings: "ss02";
            -webkit-font-feature-settings: "ss02";
            font-feature-settings: "ss02";
        }

        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: var(--rasta-brown); }
        ::-webkit-scrollbar-thumb { background: var(--rasta-gold); border-radius: 10px; }

        .fade-in { animation: fadeIn 0.5s ease-in-out; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .hover-shadow { transition: box-shadow 0.3s, transform 0.3s; }
        .hover-shadow:hover {
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5);
            transform: translateY(-2px);
        }

        /* Glass navbar */
        #main-navbar {
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            background-color: rgba(26, 20, 16, 0.25);
            transition: background-color 0.4s ease, box-shadow 0.4s ease;
            border-bottom: 1px solid rgba(201, 162, 75, 0.15);
        }
        #main-navbar.scrolled {
            background-color: rgba(26, 20, 16, 0.9);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.3);
        }

        .nav-link { transition: color 0.25s ease; }
        .nav-link:hover { color: var(--rasta-gold-light); }

        .btn-gold {
            background: linear-gradient(135deg, var(--rasta-gold-light), var(--rasta-gold));
            color: var(--rasta-dark);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .btn-gold:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px -5px rgba(201, 162, 75, 0.5);
        }

        .btn-glass {
            background: rgba(255,255,255,0.06);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255,255,255,0.18);
            transition: background 0.3s ease, transform 0.3s ease;
        }
        .btn-glass:hover { background: rgba(255,255,255,0.14); }
    </style>
    @stack('styles')
</head>
<body class="font-vazir min-h-screen flex flex-col" dir="rtl">

{{-- =========================================================
     (Sticky Glass Navbar)
========================================================= --}}
<header id="main-navbar" class="fixed top-0 inset-x-0 z-50">
    <nav class="container mx-auto px-4 h-20 flex items-center justify-between">
        <a href="{{ route('home') }}" class="flex items-center gap-2 shrink-0">
            <svg class="w-7 h-7 text-[var(--rasta-gold-light)]" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
            </svg>
            <span class="text-xl md:text-2xl font-serif-fa font-bold text-[var(--rasta-gold-light)]">راستا</span>
        </a>

        <div class="hidden md:flex items-center gap-6 text-sm">
            <a href="{{ route('services.index') }}" class="nav-link">خدمات</a>
            <a href="{{ route('blog.index') }}" class="nav-link">وبلاگ</a>

            @auth
                @if(auth()->user()->hasRole('specialists'))
                    <a href="{{ route('specialist.profile.show') }}" class="nav-link flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        پروفایل من
                    </a>
                    <a href="{{ route('specialist.my-dashboard') }}" class="nav-link flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM14 5a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 16a1 1 0 011-1h4a1 1 0 011 1v3a1 1 0 01-1 1H5a1 1 0 01-1-1v-3zM14 16a1 1 0 011-1h4a1 1 0 011 1v3a1 1 0 01-1 1h-4a1 1 0 01-1-1v-3z" />
                        </svg>
                        پنل کاری
                    </a>
                @else
                    <a href="{{ route('loyalty.index') }}" class="nav-link flex items-center gap-1 relative">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        امتیازات من
                        @php
                            try {
                                // Performance fix: Previously, this SUM would hit the database directly on every single page that
                                // the user viewed (according to Telescope, it was even
                                // repeated in /services and /bookings/create).
                                // Since loyalty points don't change instantaneously, they are cached for 5 minutes.
                                // Whenever the user's points change (redeemReward, add points
                                // new turn, etc.) Cache::forget("user:{id}:loyalty_points")
                                // must be called to ensure that the cache is not invalidated.
                                $userPoints = \Illuminate\Support\Facades\Cache::remember(
                                    'user:' . auth()->id() . ':loyalty_points',
                                    now()->addMinutes(5),
                                    fn () => \App\Models\LoyaltyPoint::where('user_id', auth()->id())->sum('points')
                                );
                            } catch (\Exception $e) {
                                $userPoints = 0;
                            }
                        @endphp
                        @if($userPoints > 0)
                            <span class="absolute -top-2 -left-3 bg-[var(--rasta-gold)] text-[var(--rasta-dark)] text-[10px] px-1.5 py-0.5 rounded-full persian-number min-w-[20px] text-center font-bold">
                                {{ number_format($userPoints) }}
                            </span>
                        @endif
                    </a>
                    <a href="{{ route('wallet.index') }}" class="nav-link flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                        کیف پول
                    </a>
                    <a href="{{ route('bookings.index') }}" class="nav-link">نوبت‌های من</a>
                    <a href="{{ route('profile.show') }}" class="nav-link">پروفایل</a>
                @endif

                <form action="{{ route('logout') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="nav-link">خروج</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="nav-link">ورود</a>
                <a href="{{ route('register') }}" class="btn-gold rounded-full px-5 py-2 text-sm font-semibold">
                    ثبت نام
                </a>
            @endauth
        </div>

        <button id="mobile-menu-button" class="md:hidden p-2 rounded-lg border border-[var(--rasta-gold)]/30" aria-label="باز کردن منو">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </button>
    </nav>

    <div id="mobile-menu" class="hidden md:hidden bg-[var(--rasta-dark)]/95 border-t border-[var(--rasta-gold)]/15">
        <div class="container mx-auto px-4 py-4 flex flex-col gap-1 text-sm">
            <a href="{{ route('services.index') }}" class="block py-2 nav-link">خدمات</a>
            <a href="{{ route('blog.index') }}" class="block py-2 nav-link">وبلاگ</a>
            @auth
                @if(auth()->user()->hasRole('specialists'))
                    <a href="{{ route('specialist.profile.show') }}" class="block py-2 nav-link">پروفایل من</a>
                    <a href="{{ route('specialist.my-dashboard') }}" class="block py-2 nav-link">پنل کاری</a>
                @else
                    <a href="{{ route('loyalty.index') }}" class="block py-2 nav-link flex items-center">
                        امتیازات من
                        @if(isset($userPoints) && $userPoints > 0)
                            <span class="mr-2 text-[var(--rasta-gold-light)] text-sm persian-number">({{ number_format($userPoints) }})</span>
                        @endif
                    </a>
                    <a href="{{ route('wallet.index') }}" class="block py-2 nav-link">کیف پول</a>
                    <a href="{{ route('bookings.index') }}" class="block py-2 nav-link">نوبت‌های من</a>
                    <a href="{{ route('profile.show') }}" class="block py-2 nav-link">پروفایل</a>
                @endif
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="block w-full text-right py-2 nav-link">خروج</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="block py-2 nav-link">ورود</a>
                <a href="{{ route('register') }}" class="block py-2 text-[var(--rasta-gold-light)] font-bold">ثبت نام</a>
            @endauth
        </div>
    </div>
</header>

<div id="announcement-banner-mount" class="container mx-auto px-4 pt-24 space-y-3" dir="rtl"
     data-announcements-url="{{ route('api.announcements.active') }}"></div>

@push('scripts')
<script>
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
        { min: 100, bg: 'bg-red-100 border-red-500', text: 'text-red-900', icon: 'text-red-600', path: 'M12 9v3.75m-9.303 3.376C1.83 17.502 2.626 19 3.928 19h16.144c1.302 0 2.098-1.498 1.231-2.874L13.06 4.75c-.65-1.05-2.17-1.05-2.82 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z' },
        { min: 71, bg: 'bg-orange-100 border-orange-500', text: 'text-orange-900', icon: 'text-orange-600', path: 'M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z' },
        { min: 31, bg: 'bg-yellow-100 border-yellow-500', text: 'text-yellow-900', icon: 'text-yellow-600', path: 'M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z' },
        { min: -Infinity, bg: 'bg-blue-100 border-blue-500', text: 'text-blue-900', icon: 'text-blue-600', path: 'M11.25 11.25l.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z' },
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
            card.className = tier.bg + ' border-r-4 rounded-lg p-4 shadow-md relative fade-in';
            card.setAttribute('data-announcement-id', announcement.id);

            var badge = '';
            if (announcement.priority >= 31) {
                var typeLabel = announcement.type && TYPE_LABELS[announcement.type]
                    ? '<span class="inline-block px-2 py-1 bg-white bg-opacity-50 rounded text-xs">' + TYPE_LABELS[announcement.type] + '</span>'
                    : '';
                badge = '<div class="mt-2 flex items-center gap-2">'
                    + '<span class="inline-block px-2 py-1 bg-white bg-opacity-50 rounded text-xs font-semibold">اولویت بالا: ' + announcement.priority + '</span>'
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
    @if(session('success'))
        <div class="bg-emerald-900/30 border-r-4 border-[var(--rasta-gold)] p-4 text-emerald-200 rounded mb-4 flex items-start">
            <svg class="h-5 w-5 ml-2 text-[var(--rasta-gold-light)] mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-900/30 border-r-4 border-red-500 p-4 text-red-200 rounded mb-4 flex items-start">
            <svg class="h-5 w-5 ml-2 text-red-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
            <div>{{ session('error') }}</div>
        </div>
    @endif

    @if(session('info'))
        <div class="bg-sky-900/30 border-r-4 border-sky-400 p-4 text-sky-200 rounded mb-4 flex items-start">
            <svg class="h-5 w-5 ml-2 text-sky-300 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
            </svg>
            <div>{{ session('info') }}</div>
        </div>
    @endif

    @yield('content')
</main>

{{-- =========================================================
     National footer
========================================================= --}}
<footer class="bg-[var(--rasta-brown)] border-t border-[var(--rasta-gold)]/10 mt-auto">
    <div class="container mx-auto px-4 py-12">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
            <div>
                <h3 class="font-serif-fa text-xl font-bold text-[var(--rasta-gold-light)] mb-3">راستا</h3>
                <p class="text-[var(--rasta-cream)]/60 text-sm leading-7">
                    سالن زیبایی راستا با بیش از ۱۰ سال سابقه درخشان، آماده ارائه بهترین خدمات زیبایی، آرایش و مراقبت پوست و مو به شما عزیزان است.
                </p>
            </div>

            <div>
                <h3 class="font-bold mb-4 flex items-center text-[var(--rasta-cream)]">
                    <svg class="w-5 h-5 ml-1 text-[var(--rasta-gold-light)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                    تماس با ما
                </h3>
                <div class="text-[var(--rasta-cream)]/60 text-sm space-y-2">
                    <p class="flex items-center">
                        <svg class="w-4 h-4 ml-2 text-[var(--rasta-gold)]/70" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        آدرس: تهران، خیابان ولیعصر
                    </p>
                    <p class="flex items-center persian-number">
                        <svg class="w-4 h-4 ml-2 text-[var(--rasta-gold)]/70" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                        </svg>
                        تلفن: 021-12345678
                    </p>
                    <p class="flex items-center">
                        <svg class="w-4 h-4 ml-2 text-[var(--rasta-gold)]/70" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        ایمیل: info@rasta-salon.ir
                    </p>
                </div>
            </div>

            <div>
                <h3 class="font-bold mb-4 flex items-center text-[var(--rasta-cream)]">
                    <svg class="w-5 h-5 ml-1 text-[var(--rasta-gold-light)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    ساعات کاری
                </h3>
                <div class="text-[var(--rasta-cream)]/60 text-sm space-y-2 persian-number">
                    <p>شنبه تا چهارشنبه: ۹ صبح تا ۹ شب</p>
                    <p>پنجشنبه: ۹ صبح تا ۵ عصر</p>
                    <p>جمعه: تعطیل</p>
                </div>
            </div>
        </div>

        <div class="border-t border-[var(--rasta-gold)]/10 mt-8 pt-6 text-center text-[var(--rasta-cream)]/40 text-sm">
            <p>© {{ date('Y') }} سالن زیبایی راستا. تمامی حقوق محفوظ است.</p>
        </div>
    </div>
</footer>

<script>
    document.getElementById('mobile-menu-button')?.addEventListener('click', function() {
        document.getElementById('mobile-menu').classList.toggle('hidden');
    });

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

@stack('scripts')
</body>
</html>
