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
                                $userPoints = \App\Models\LoyaltyPoint::where('user_id', auth()->id())
                                    ->selectRaw('SUM(CASE WHEN type = "earned" THEN points ELSE -points END) as total')
                                    ->value('total') ?? 0;
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

<div id="announcement-banner" class="container mx-auto px-4 pt-24"></div>

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
