<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'سالن زیبایی') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
</head>
<body class="antialiased">
<div class="relative min-h-screen bg-gray-100">
    <div class="max-w-7xl mx-auto p-6 lg:p-8">
        <div class="text-center">
            <h1 class="text-4xl font-bold text-gray-900">سالن زیبایی</h1>
            <p class="mt-4 text-lg text-gray-600">خوش آمدید</p>
        </div>

        <div class="mt-16">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 lg:gap-8">
                <a href="{{ route('login') }}" class="scale-100 p-6 bg-white rounded-lg shadow-lg hover:scale-[1.02] transition-all duration-300">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">ورود</h2>
                        <p class="mt-4 text-gray-500 text-sm leading-relaxed">
                            اگر قبلاً ثبت‌نام کرده‌اید، وارد شوید
                        </p>
                    </div>
                </a>

                <a href="{{ route('register') }}" class="scale-100 p-6 bg-white rounded-lg shadow-lg hover:scale-[1.02] transition-all duration-300">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">ثبت نام</h2>
                        <p class="mt-4 text-gray-500 text-sm leading-relaxed">
                            برای رزرو نوبت، حساب کاربری بسازید
                        </p>
                    </div>
                </a>
            </div>
        </div>

        <div class="mt-16">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="p-6 bg-white rounded-lg shadow-lg">
                    <h3 class="text-lg font-semibold text-gray-900">رزرو آنلاین</h3>
                    <p class="mt-2 text-gray-500">به راحتی و در هر ساعتی نوبت خود را رزرو کنید</p>
                </div>
                <div class="p-6 bg-white rounded-lg shadow-lg">
                    <h3 class="text-lg font-semibold text-gray-900">متخصصین مجرب</h3>
                    <p class="mt-2 text-gray-500">بهترین خدمات توسط متخصصین با تجربه</p>
                </div>
                <div class="p-6 bg-white rounded-lg shadow-lg">
                    <h3 class="text-lg font-semibold text-gray-900">تنوع خدمات</h3>
                    <p class="mt-2 text-gray-500">انواع خدمات آرایشی و زیبایی</p>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
