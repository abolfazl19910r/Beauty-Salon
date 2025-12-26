<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl" class="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @auth
        <meta name="user-logged-in" content="true">
    @endauth
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazir-font@v30.1.0/dist/font-face.css" rel="stylesheet" type="text/css" />
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">

    <title>@yield('title') | سالن زیبایی</title>

    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <script src="https://unpkg.com/persian-date/dist/persian-date.min.js"></script>
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
                    <svg class="w-8 h-8 ml-2 text-pink-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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
                            <svg class="w-4 h-4 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            پروفایل من
                        </a>
                        <a href="{{ route('specialist.my-dashboard') }}" class="text-gray-600 hover:text-pink-500 transition-colors px-3 py-2 flex items-center">
                            <svg class="w-4 h-4 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="7" height="7"></rect>
                                <rect x="14" y="3" width="7" height="7"></rect>
                                <rect x="14" y="14" width="7" height="7"></rect>
                                <rect x="3" y="14" width="7" height="7"></rect>
                            </svg>
                            پنل کاری
                        </a>
                    @else
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
            <svg class="h-5 w-5 ml-2 text-green-500 mt-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 border-r-4 border-red-500 p-4 text-red-700 rounded mb-4 flex items-start">
            <svg class="h-5 w-5 ml-2 text-red-500 mt-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
            <div>{{ session('error') }}</div>
        </div>
    @endif

    @yield('content')
</main>

<footer class="bg-white border-t mt-auto">
    <div class="container mx-auto px-4 py-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div>
                <h3 class="text-lg font-bold mb-4 flex items-center">
                    <svg class="w-5 h-5 ml-1 text-pink-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    درباره ما
                </h3>
                <p class="text-gray-600">
                    سالن زیبایی ما با بیش از 10 سال سابقه درخشان، آماده ارائه بهترین خدمات به شما عزیزان است.
                </p>
            </div>

            <div>
                <h3 class="text-lg font-bold mb-4 flex items-center">
                    <svg class="w-5 h-5 ml-1 text-pink-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                    </svg>
                    تماس با ما
                </h3>
                <div class="text-gray-600 space-y-2">
                    <p class="flex items-center">
                        <svg class="w-4 h-4 ml-2 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                        آدرس: تهران، خیابان ولیعصر
                    </p>
                    <p class="flex items-center">
                        <svg class="w-4 h-4 ml-2 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                        </svg>
                        تلفن: 021-12345678
                    </p>
                    <p class="flex items-center">
                        <svg class="w-4 h-4 ml-2 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                            <polyline points="22,6 12,13 2,6"></polyline>
                        </svg>
                        ایمیل: info@beautysalon.com
                    </p>
                </div>
            </div>

            <div>
                <h3 class="text-lg font-bold mb-4 flex items-center">
                    <svg class="w-5 h-5 ml-1 text-pink-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    ساعات کاری
                </h3>
                <div class="text-gray-600 space-y-2">
                    <p class="flex items-center">
                        <svg class="w-4 h-4 ml-2 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        شنبه تا چهارشنبه: 9 صبح تا 9 شب
                    </p>
                    <p class="flex items-center">
                        <svg class="w-4 h-4 ml-2 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        پنجشنبه: 9 صبح تا 5 عصر
                    </p>
                    <p class="flex items-center">
                        <svg class="w-4 h-4 ml-2 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
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
    document.getElementById('mobile-menu-button').addEventListener('click', function() {
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
