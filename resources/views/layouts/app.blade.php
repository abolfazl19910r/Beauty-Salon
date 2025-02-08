<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title') | سالن زیبایی</title>

    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <script src="https://unpkg.com/persian-date/dist/persian-date.min.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
</head>
<body class="bg-gray-50 font-vazir">
<!-- Header -->
<header class="bg-white shadow">
    <nav class="container mx-auto px-4 py-4">
        <div class="flex justify-between items-center">
            <div class="flex items-center">
                <a href="{{ route('home') }}" class="text-xl font-bold text-gray-800">سالن زیبایی</a>
            </div>

            <div class="flex items-center space-x-4 space-x-reverse">
                <a href="{{ route('services.index') }}" class="text-gray-600 hover:text-gray-900">خدمات</a>

                @auth
                    <a href="{{ route('bookings.index') }}" class="text-gray-600 hover:text-gray-900">نوبت‌های من</a>
                    <a href="{{ route('profile.show') }}" class="text-gray-600 hover:text-gray-900">پروفایل</a>

                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="text-gray-600 hover:text-gray-900">خروج</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="text-gray-600 hover:text-gray-900">ورود</a>
                    <a href="{{ route('register') }}" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
                        ثبت نام
                    </a>
                @endauth
            </div>
        </div>
    </nav>
</header>

<!-- Main Content -->
<main class="container mx-auto px-4 py-8">
    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif

    @yield('content')
</main>

<!-- Footer -->
<footer class="bg-white border-t mt-12">
    <div class="container mx-auto px-4 py-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div>
                <h3 class="text-lg font-bold mb-4">درباره ما</h3>
                <p class="text-gray-600">
                    سالن زیبایی ما با بیش از 10 سال سابقه درخشان، آماده ارائه بهترین خدمات به شما عزیزان است.
                </p>
            </div>

            <div>
                <h3 class="text-lg font-bold mb-4">تماس با ما</h3>
                <div class="text-gray-600">
                    <p>آدرس: تهران، خیابان ولیعصر</p>
                    <p>تلفن: 021-12345678</p>
                    <p>ایمیل: info@beautysalon.com</p>
                </div>
            </div>

            <div>
                <h3 class="text-lg font-bold mb-4">ساعات کاری</h3>
                <div class="text-gray-600">
                    <p>شنبه تا چهارشنبه: 9 صبح تا 9 شب</p>
                    <p>پنجشنبه: 9 صبح تا 5 عصر</p>
                    <p>جمعه: تعطیل</p>
                </div>
            </div>
        </div>
    </div>
</footer>

@stack('scripts')
</body>
</html>
