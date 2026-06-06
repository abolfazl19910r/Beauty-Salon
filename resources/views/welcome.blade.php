<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl" class="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazir-font@v30.1.0/dist/font-face.css" rel="stylesheet" type="text/css" />
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

    <title>{{ config('app.name', 'سالن زیبایی') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.jsx'])

    <style>
        .persian-number {
            -moz-font-feature-settings: "ss02";
            -webkit-font-feature-settings: "ss02";
            font-feature-settings: "ss02";
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

        .card-hover {
            transition: all 0.3s ease;
        }

        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px -10px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="antialiased font-vazir bg-gray-50">
<div class="relative min-h-screen fade-in">
    <div class="absolute inset-0 bg-gradient-to-br from-pink-50 to-purple-50 -z-10"></div>

    <header class="py-6">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    <svg class="w-8 h-8 text-pink-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                    </svg>
                    <h1 class="text-2xl font-bold ml-2 bg-gradient-to-r from-pink-500 to-purple-600 bg-clip-text text-transparent">سالن زیبایی</h1>
                </div>
            </div>
        </div>
    </header>

    <main class="py-12">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-bold mb-4 text-gray-900">به سالن زیبایی ما خوش آمدید</h2>
                <p class="mt-4 text-xl text-gray-600 max-w-3xl mx-auto">تجربه آرامش و زیبایی در محیطی دلنشین با بهترین متخصصین و امکانات</p>
            </div>

            <div class="mt-12">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 lg:gap-12">
                    <a href="{{ route('login') }}" class="bg-white rounded-2xl shadow-lg hover-shadow p-8 card-hover transition-all duration-300">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 p-4 bg-pink-100 rounded-full">
                                <svg class="w-8 h-8 text-pink-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                                    <polyline points="10 17 15 12 10 7"></polyline>
                                    <line x1="15" y1="12" x2="3" y2="12"></line>
                                </svg>
                            </div>
                            <div class="mr-4">
                                <h3 class="text-2xl font-bold text-gray-900 mb-2">ورود به حساب کاربری</h3>
                                <p class="text-gray-600">اگر قبلاً ثبت‌نام کرده‌اید، وارد شوید تا از امکانات سایت استفاده کنید.</p>
                            </div>
                        </div>
                    </a>

                    <a href="{{ route('register') }}" class="bg-white rounded-2xl shadow-lg hover-shadow p-8 card-hover transition-all duration-300">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 p-4 bg-purple-100 rounded-full">
                                <svg class="w-8 h-8 text-purple-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="8.5" cy="7" r="4"></circle>
                                    <line x1="20" y1="8" x2="20" y2="14"></line>
                                    <line x1="23" y1="11" x2="17" y2="11"></line>
                                </svg>
                            </div>
                            <div class="mr-4">
                                <h3 class="text-2xl font-bold text-gray-900 mb-2">ایجاد حساب کاربری</h3>
                                <p class="text-gray-600">برای رزرو آنلاین و استفاده از تخفیف‌های ویژه، حساب کاربری ایجاد کنید.</p>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            <div class="mt-20">
                <h3 class="text-2xl font-bold text-center mb-10">چرا سالن زیبایی ما؟</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="bg-white p-6 rounded-xl shadow-md hover-shadow text-center card-hover">
                        <div class="bg-gradient-to-r from-pink-500 to-purple-600 w-16 h-16 mx-auto rounded-full flex items-center justify-center mb-4">
                            <svg class="w-8 h-8 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h4 class="text-lg font-bold mb-2">رزرو آنلاین</h4>
                        <p class="text-gray-600">به راحتی و در هر ساعتی از شبانه‌روز نوبت خود را رزرو کنید</p>
                    </div>

                    <div class="bg-white p-6 rounded-xl shadow-md hover-shadow text-center card-hover">
                        <div class="bg-gradient-to-r from-pink-500 to-purple-600 w-16 h-16 mx-auto rounded-full flex items-center justify-center mb-4">
                            <svg class="w-8 h-8 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="8.5" cy="7" r="4"></circle>
                                <path d="M20 8v6"></path>
                                <path d="M23 11h-6"></path>
                            </svg>
                        </div>
                        <h4 class="text-lg font-bold mb-2">متخصصین مجرب</h4>
                        <p class="text-gray-600">بهره‌مندی از خدمات متخصصین با تجربه و حرفه‌ای</p>
                    </div>

                    <div class="bg-white p-6 rounded-xl shadow-md hover-shadow text-center card-hover">
                        <div class="bg-gradient-to-r from-pink-500 to-purple-600 w-16 h-16 mx-auto rounded-full flex items-center justify-center mb-4">
                            <svg class="w-8 h-8 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="12" y1="1" x2="12" y2="23"></line>
                                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                            </svg>
                        </div>
                        <h4 class="text-lg font-bold mb-2">قیمت مناسب</h4>
                        <p class="text-gray-600">ارائه خدمات با کیفیت در قیمت‌های رقابتی</p>
                    </div>
                </div>
            </div>

            <div class="mt-24 text-center">
                <a href="{{ route('home') }}" class="inline-block bg-gradient-to-r from-pink-500 to-purple-600 text-white px-8 py-3 rounded-lg font-bold hover:opacity-90 transition-colors">
                    مشاهده وبسایت اصلی
                </a>
            </div>
        </div>
    </main>

    <footer class="mt-12 py-6 border-t">
        <div class="max-w-7xl mx-auto px-6 lg:px-8 text-center text-gray-500">
            <p>© {{ date('Y') }} سالن زیبایی. تمامی حقوق محفوظ است.</p>
        </div>
    </footer>
</div>
</body>
</html>
