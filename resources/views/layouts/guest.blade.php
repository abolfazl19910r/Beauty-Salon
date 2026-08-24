<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl" class="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

    <title>{{ config('app.name', 'راستا') }}</title>


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

        .btn-gold {
            background: linear-gradient(135deg, var(--rasta-gold-light), var(--rasta-gold));
            color: var(--rasta-dark);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .btn-gold:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px -5px rgba(201, 162, 75, 0.5);
        }

        .auth-card {
            background-color: var(--rasta-brown);
            border: 1px solid rgba(201, 162, 75, 0.15);
        }

        /* Auth shared input styling */
        .input-gold {
            background-color: rgba(248, 243, 233, 0.04);
            border: 1px solid rgba(201, 162, 75, 0.25);
            color: var(--rasta-cream);
            border-radius: 0.5rem;
        }
        .input-gold::placeholder {
            color: rgba(248, 243, 233, 0.35);
        }
        .input-gold:focus {
            border-color: var(--rasta-gold);
            box-shadow: 0 0 0 3px rgba(201, 162, 75, 0.2);
            outline: none;
        }
    </style>
</head>
<body class="font-vazir antialiased min-h-screen flex flex-col" dir="rtl">

<div class="min-h-screen flex flex-col justify-center items-center px-4 py-10">
    <div class="fade-in mb-2">
        <a href="{{ route('home') }}" class="flex items-center justify-center gap-2">
            <svg class="w-9 h-9 text-[var(--rasta-gold-light)]" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
            </svg>
            <span class="text-2xl font-serif-fa font-bold text-[var(--rasta-gold-light)]">راستا</span>
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
            <div class="bg-red-900/30 border-r-4 border-red-500 p-4 text-red-200 rounded mb-5 flex items-start text-sm">
                <svg class="h-5 w-5 ml-2 text-red-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
                <div>{{ session('error') }}</div>
            </div>
        @endif

        {{ $slot }}
    </div>

    <div class="mt-8 text-center text-[var(--rasta-cream)]/40 text-sm fade-in">
        <p>© {{ date('Y') }} سالن زیبایی راستا. تمامی حقوق محفوظ است.</p>
    </div>
</div>
</body>
</html>
