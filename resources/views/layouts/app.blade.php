<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl" class="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @auth
        <meta name="user-logged-in" content="true">
    @endauth
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">

    <title>@yield('title') | سالن زیبایی</title>
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])

    <style>
        .persian-number {
            -moz-font-feature-settings: "ss02";
            -webkit-font-feature-settings: "ss02";
            font-feature-settings: "ss02";
        }

        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(243, 244, 246, 0.8);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(156, 163, 175, 0.5);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: rgba(107, 114, 128, 0.7);
        }

        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .hover-shadow {
            transition: box-shadow 0.3s, transform 0.3s;
        }

        .hover-shadow:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }
    </style>
</head>
<body class="bg-gray-50 font-vazir min-h-screen flex flex-col">
<header class="bg-white shadow sticky top-0 z-10">
    <nav class="container mx-auto px-4 py-4">
        <div class="flex justify-between items-center">
            <div class="flex items-center">
                <a href="{{ route('home') }}" class="flex items-center">
                    <svg class="w-8 h-8 ml-2 text-pink-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                    </svg>
                    <span class="text-xl font-bold bg-gradient-to-r from-pink-500 to-purple-600 bg-clip-text text-transparent">سالن زیبایی</span>
                </a>
            </div>

            <div class="hidden md:flex items-center space-x-4 space-x-reverse">
                <a href="{{ route('services.index') }}" class="text-gray-600 hover:text-pink-500 transition-colors px-3 py-2">خدمات</a>

                @auth
                    @if(auth()->user()->hasRole('specialists'))
                        <a href="{{ route('specialist.profile.show') }}" class="text-gray-600 hover:text-pink-500 transition-colors px-3 py-2 flex items-center">
                            <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            پروفایل من
                        </a>
                        <a href="{{ route('specialist.my-dashboard') }}" class="text-gray-600 hover:text-pink-500 transition-colors px-3 py-2 flex items-center">
                            <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM14 5a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 16a1 1 0 011-1h4a1 1 0 011 1v3a1 1 0 01-1 1H5a1 1 0 01-1-1v-3zM14 16a1 1 0 011-1h4a1 1 0 011 1v3a1 1 0 01-1 1h-4a1 1 0 01-1-1v-3z" />
                            </svg>
                            پنل کاری
                        </a>
                    @else
                        <a href="{{ route('loyalty.index') }}" class="text-gray-600 hover:text-pink-500 transition-colors px-3 py-2 flex items-center group relative">
                            <svg class="w-4 h-4 ml-1 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
                                <span class="absolute -top-1 -left-1 bg-pink-500 text-white text-xs px-1.5 py-0.5 rounded-full persian-number min-w-[20px] text-center">
                                    {{ number_format($userPoints) }}
                                </span>
                            @endif
                        </a>
                        <a href="{{ route('wallet.index') }}" class="text-gray-600 hover:text-pink-500 transition-colors px-3 py-2 flex items-center">
                            <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                            کیف پول
                        </a>
                        <a href="{{ route('bookings.index') }}" class="text-gray-600 hover:text-pink-500 transition-colors px-3 py-2">نوبت‌های من</a>
                        <a href="{{ route('profile.show') }}" class="text-gray-600 hover:text-pink-500 transition-colors px-3 py-2">پروفایل</a>
                    @endif

                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="text-gray-600 hover:text-pink-500 transition-colors px-3 py-2">خروج</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="text-gray-600 hover:text-pink-500 transition-colors px-3 py-2">ورود</a>
                    <a href="{{ route('register') }}" class="bg-gradient-to-r from-pink-500 to-purple-600 text-white px-4 py-2 rounded-lg hover:opacity-90 transition-opacity">
                        ثبت نام
                    </a>
                @endauth
            </div>

            <div class="md:hidden">
                <button id="mobile-menu-button" class="text-gray-500 hover:text-gray-700 focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </div>
        </div>

        <div id="mobile-menu" class="hidden md:hidden mt-4 pb-2 fade-in">
            <a href="{{ route('services.index') }}" class="block py-2 text-gray-600 hover:text-pink-500">خدمات</a>
            @auth
                @if(auth()->user()->hasRole('specialists'))
                    <a href="{{ route('specialist.profile.show') }}" class="block py-2 text-gray-600 hover:text-pink-500">پروفایل من</a>
                    <a href="{{ route('specialist.my-dashboard') }}" class="block py-2 text-gray-600 hover:text-pink-500">پنل کاری</a>
                @else
                    <a href="{{ route('loyalty.index') }}" class="block py-2 text-gray-600 hover:text-pink-500 flex items-center">
                        <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        امتیازات من
                        @if(isset($userPoints) && $userPoints > 0)
                            <span class="mr-2 text-pink-500 text-sm persian-number">({{ number_format($userPoints) }})</span>
                        @endif
                    </a>
                    <a href="{{ route('wallet.index') }}" class="block py-2 text-gray-600 hover:text-pink-500">کیف پول</a>
                    <a href="{{ route('bookings.index') }}" class="block py-2 text-gray-600 hover:text-pink-500">نوبت‌های من</a>
                    <a href="{{ route('profile.show') }}" class="block py-2 text-gray-600 hover:text-pink-500">پروفایل</a>
                @endif
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="block w-full text-right py-2 text-gray-600 hover:text-pink-500">خروج</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="block py-2 text-gray-600 hover:text-pink-500">ورود</a>
                <a href="{{ route('register') }}" class="block py-2 text-pink-500 font-bold">ثبت نام</a>
            @endauth
        </div>
    </nav>
</header>

<div id="announcement-banner" class="container mx-auto px-4 pt-4"></div>

<main class="container mx-auto px-4 py-8 flex-grow fade-in">
    @if(session('success'))
        <div class="bg-green-50 border-r-4 border-green-500 p-4 text-green-700 rounded mb-4 flex items-start">
            <svg class="h-5 w-5 ml-2 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 border-r-4 border-red-500 p-4 text-red-700 rounded mb-4 flex items-start">
            <svg class="h-5 w-5 ml-2 text-red-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
            <div>{{ session('error') }}</div>
        </div>
    @endif

    @if(session('info'))
        <div class="bg-blue-50 border-r-4 border-blue-500 p-4 text-blue-700 rounded mb-4 flex items-start">
            <svg class="h-5 w-5 ml-2 text-blue-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
            </svg>
            <div>{{ session('info') }}</div>
        </div>
    @endif

    @yield('content')
</main>

<footer class="bg-white border-t mt-auto">
    <div class="container mx-auto px-4 py-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div>
                <h3 class="text-lg font-bold mb-4 flex items-center">
                    <svg class="w-5 h-5 ml-1 text-pink-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    درباره ما
                </h3>
                <p class="text-gray-600">
                    سالن زیبایی ما با بیش از 10 سال سابقه درخشان، آماده ارائه بهترین خدمات به شما عزیزان است.
                </p>
            </div>

            <div>
                <h3 class="text-lg font-bold mb-4 flex items-center">
                    <svg class="w-5 h-5 ml-1 text-pink-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                    تماس با ما
                </h3>
                <div class="text-gray-600 space-y-2">
                    <p class="flex items-center">
                        <svg class="w-4 h-4 ml-2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        آدرس: تهران، خیابان ولیعصر
                    </p>
                    <p class="flex items-center">
                        <svg class="w-4 h-4 ml-2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                        </svg>
                        تلفن: 021-12345678
                    </p>
                    <p class="flex items-center">
                        <svg class="w-4 h-4 ml-2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        ایمیل: info@beautysalon.com
                    </p>
                </div>
            </div>

            <div>
                <h3 class="text-lg font-bold mb-4 flex items-center">
                    <svg class="w-5 h-5 ml-1 text-pink-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    ساعات کاری
                </h3>
                <div class="text-gray-600 space-y-2">
                    <p class="flex items-center">
                        <svg class="w-4 h-4 ml-2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        شنبه تا چهارشنبه: 9 صبح تا 9 شب
                    </p>
                    <p class="flex items-center">
                        <svg class="w-4 h-4 ml-2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        پنجشنبه: 9 صبح تا 5 عصر
                    </p>
                    <p class="flex items-center">
                        <svg class="w-4 h-4 ml-2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        جمعه: تعطیل
                    </p>
                </div>
            </div>
        </div>

        <div class="border-t mt-8 pt-6 text-center text-gray-500 text-sm">
            <p>© {{ date('Y') }} سالن زیبایی. تمامی حقوق محفوظ است.</p>
        </div>
    </div>
</footer>

<script>
    document.getElementById('mobile-menu-button')?.addEventListener('click', function() {
        const menu = document.getElementById('mobile-menu');
        menu.classList.toggle('hidden');
    });

    setTimeout(() => {
        const alerts = document.querySelectorAll('[class*="border-r-4"]');
        alerts.forEach(alert => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s';
            setTimeout(() => alert.remove(), 500);
        });
    }, 5000);
</script>

@stack('scripts')
</body>
</html>
