<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') | پنل مدیریت سالن زیبایی</title>

    {{-- Tailwind CSS --}}
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
</head>
<body class="bg-gray-100">
{{-- Sidebar --}}
<div class="flex">
    <aside class="w-64 min-h-screen bg-gray-800 text-white">
        <div class="p-4">
            <h2 class="text-xl font-bold">پنل مدیریت</h2>
        </div>
        <nav class="mt-4">
            <a href="{{ route('admin.dashboard') }}"
               class="block px-4 py-2 {{ request()->routeIs('admin.dashboard') ? 'bg-gray-900' : 'hover:bg-gray-700' }}">
                داشبورد
            </a>
            <a href="{{ route('admin.reports') }}"
               class="block px-4 py-2 {{ request()->routeIs('admin.reports*') ? 'bg-gray-900' : 'hover:bg-gray-700' }}">
                گزارشات
            </a>
            <a href="{{ route('admin.services.index') }}"
               class="block px-4 py-2 {{ request()->routeIs('admin.services*') ? 'bg-gray-900' : 'hover:bg-gray-700' }}">
                مدیریت خدمات
            </a>
            <a href="{{ route('admin.specialists.index') }}"
               class="block px-4 py-2 {{ request()->routeIs('admin.specialists*') ? 'bg-gray-900' : 'hover:bg-gray-700' }}">
                مدیریت متخصصین
            </a>
            <a href="{{ route('admin.bookings.index') }}"
               class="block px-4 py-2 {{ request()->routeIs('admin.bookings*') ? 'bg-gray-900' : 'hover:bg-gray-700' }}">
                مدیریت نوبت‌ها
            </a>
        </nav>
    </aside>

    {{-- Main Content --}}
    <main class="flex-1">
        <div class="p-8">
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
        </div>
    </main>
</div>

{{-- Scripts --}}
@stack('scripts')
</body>
</html>
