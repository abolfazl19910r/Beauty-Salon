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

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])

    <style>
        .persian-number {
            -moz-font-feature-settings: "ss02";
            -webkit-font-feature-settings: "ss02";
            font-feature-settings: "ss02";
        }

        /* Animations */
        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Card hover effects */
        .hover-shadow {
            transition: box-shadow 0.3s, transform 0.3s;
        }

        .hover-shadow:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }
    </style>
</head>
<body class="font-vazir text-gray-900 antialiased bg-gray-50">
<div class="min-h-screen flex flex-col justify-center items-center pt-6 sm:pt-0">
    <div class="fade-in">
        <a href="{{ route('home') }}" class="flex items-center justify-center mb-6">
            <svg class="w-12 h-12 text-pink-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
            </svg>
            <span class="text-2xl font-bold mr-2 bg-gradient-to-r from-pink-500 to-purple-600 bg-clip-text text-transparent">سالن زیبایی</span>
        </a>
    </div>

    <div class="w-full sm:max-w-md mt-6 px-6 py-6 bg-white shadow-md overflow-hidden sm:rounded-lg hover-shadow fade-in">
        {{ $slot }}
    </div>

    <div class="mt-8 text-center text-gray-500 text-sm fade-in">
        <p>© {{ date('Y') }} سالن زیبایی. تمامی حقوق محفوظ است.</p>
    </div>
</div>
</body>
</html>
