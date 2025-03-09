<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl" class="rtl">
<head>
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazir-font@v30.1.0/dist/font-face.css" rel="stylesheet" type="text/css" />
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') | پنل مدیریت سالن زیبایی</title>

    <!-- Styles -->
    <link rel="stylesheet" href="https://unpkg.com/persian-datepicker@latest/dist/css/persian-datepicker.min.css">
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
</head>
<body class="bg-gray-100 rtl">
<!-- Sidebar and Main Content Container -->
<div class="flex h-screen">
    <!-- Sidebar -->
    <aside class="w-64 min-h-screen bg-gray-800 text-white">
        <div class="p-4">
            <h2 class="text-xl font-bold">پنل مدیریت</h2>
        </div>
        <nav class="mt-4">
            <a href="{{ route('admin.dashboard') }}"
               class="block px-4 py-2 {{ request()->routeIs('admin.dashboard') ? 'bg-gray-900' : 'hover:bg-gray-700' }} transition-colors">
                داشبورد
            </a>
            <a href="{{ route('admin.reports.index') }}"
               class="block px-4 py-2 {{ request()->routeIs('admin.reports*') ? 'bg-gray-900' : 'hover:bg-gray-700' }} transition-colors">
                گزارشات
            </a>
            <a href="{{ route('admin.services.index') }}"
               class="block px-4 py-2 {{ request()->routeIs('admin.services*') ? 'bg-gray-900' : 'hover:bg-gray-700' }} transition-colors">
                مدیریت خدمات
            </a>
            <a href="{{ route('admin.specialists.index') }}"
               class="block px-4 py-2 {{ request()->routeIs('admin.specialists*') ? 'bg-gray-900' : 'hover:bg-gray-700' }} transition-colors">
                مدیریت متخصصین
            </a>
            <a href="{{ route('admin.bookings.index') }}"
               class="block px-4 py-2 {{ request()->routeIs('admin.bookings*') ? 'bg-gray-900' : 'hover:bg-gray-700' }} transition-colors">
                مدیریت نوبت‌ها
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 overflow-x-hidden overflow-y-auto">
        <!-- Notifications -->
        @if(session('success'))
            <div class="m-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="m-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif

        <!-- Main Content Area -->
        <div class="p-6">
            @yield('content')
        </div>
    </main>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://unpkg.com/persian-date/dist/persian-date.min.js"></script>
<script src="https://unpkg.com/persian-datepicker/dist/js/persian-datepicker.min.js"></script>

<!-- Custom Scripts -->
@stack('scripts')

<!-- Debug Info (only in development) -->
@if(config('app.debug'))
    <script>
        console.log('Layout initialized');
        console.log('Route:', '{{ request()->route()->getName() }}');
    </script>
@endif
</body>
</html>
