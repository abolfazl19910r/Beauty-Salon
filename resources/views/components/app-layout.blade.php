<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'پنل مدیریت') }}</title>

    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
</head>
<body class="bg-gray-50">
<div class="min-h-screen flex">
    @include('layouts.admin-navigation')

    <div class="flex-1 flex flex-col">
        @isset($header)
            <header class="bg-white shadow-sm border-b">
                <div class="px-6 py-4">
                    {{ $header }}
                </div>
            </header>
        @endisset

        <main class="flex-1 p-6">
            {{ $slot }}
        </main>
    </div>
</div>

@stack('scripts')
</body>
</html>
