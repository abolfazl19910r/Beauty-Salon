<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl" class="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('partials.favicons')

    <title>{{ $currentSalonName }}</title>


    @vite(['resources/css/app.css', 'resources/js/app.jsx'])

    @include('partials.mahru-theme')
</head>
<body class="font-vazir antialiased min-h-screen flex flex-col" dir="rtl">

<div class="fixed top-4 left-4 z-50">@include('partials.mahru-theme-toggle')</div>

<div class="min-h-screen flex flex-col justify-center items-center px-4 py-10">
    <div class="fade-in mb-2">
        <a href="{{ route('home') }}" class="flex items-center justify-center gap-2">
            <x-brand-emblem class="h-12 w-auto max-w-[160px]" />
            <span class="text-2xl font-serif-fa font-bold text-m-gold2">{{ $currentSalonName }}</span>
        </a>
    </div>

    <div class="w-full sm:max-w-md mt-6 px-6 py-8 auth-card shadow-2xl overflow-hidden sm:rounded-2xl hover-shadow fade-in">
        {{-- ⭐ Wired up (post-test-writing-phase): every other layout in the project
             (layouts/app.blade.php, layouts/admin.blade.php, layouts/specialist.blade.php)
             already shows a session('error') flash banner; this guest layout — used by
             login/register/forgot-password/reset-password/login-verify/register-verify —
             was the only one missing it. Needed so a general, non-field-specific message
             (like a throttle:auth 429 "too many attempts" notice, which isn't tied to any
             single form field on these pages) is actually visible to the user, instead of
             silently doing nothing. --}}
        @if(session('error'))
            <div class="bg-m-bad/30 border-r-4 border-m-bad p-4 text-m-bad rounded mb-5 flex items-start text-sm">
                <svg class="h-5 w-5 ml-2 text-m-bad mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
                <div>{{ session('error') }}</div>
            </div>
        @endif

        {{ $slot }}
    </div>

    <div class="mt-8 text-center text-m-tx/40 text-sm fade-in">
        <p>© {{ date('Y') }} {{ $currentSalonTitle }}. تمامی حقوق محفوظ است.</p>
    </div>
</div>
@include('partials.password-reveal')
{{-- بسته‌ی ۶: service worker (ورود/ثبت‌نام داخل سایت نصب‌شده)؛ بنر نصب آیفون در این صفحه‌ها نه --}}
@include('partials.pwa-client', ['pwaMode' => 'guest', 'pwaBanner' => false])
</body>
</html>
