@extends('layouts.app')

@section('title', 'خطا در پرداخت')

@section('content')
    <div class="max-w-md mx-auto bg-white rounded-lg shadow-sm hover-shadow p-6 text-center fade-in">
        <div class="text-red-500 mb-6">
            <svg class="w-20 h-20 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
        <h1 class="text-2xl font-bold mb-4 bg-gradient-to-r from-pink-500 to-purple-600 bg-clip-text text-transparent">خطا در پرداخت</h1>
        <p class="text-gray-600 mb-8">{{ session('error') ?? 'متاسفانه پرداخت با خطا مواجه شد.' }}</p>

        <div class="space-x-4 space-x-reverse">
            <a href="{{ route('payment.show', $booking) }}"
               class="inline-block bg-gradient-to-r from-pink-500 to-purple-600 text-white px-6 py-3 rounded-lg hover:opacity-90 transition-opacity">
                تلاش مجدد
            </a>
            <a href="{{ route('home') }}"
               class="inline-block bg-gray-200 text-gray-700 px-6 py-3 rounded-lg hover:bg-gray-300 transition-colors">
                بازگشت به صفحه اصلی
            </a>
        </div>
    </div>
@endsection
