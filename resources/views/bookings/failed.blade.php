@extends('layouts.app')

@section('title', 'خطا در پرداخت')

@section('content')
    <div class="max-w-md mx-auto bg-white rounded-lg shadow p-6 text-center">
        <div class="text-red-500 mb-4">
            <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
        <h1 class="text-2xl font-bold mb-4">خطا در پرداخت</h1>
        <p class="text-gray-600 mb-6">{{ session('error') ?? 'متاسفانه پرداخت با خطا مواجه شد.' }}</p>

        <div class="space-x-4 space-x-reverse">
            <a href="{{ route('payment.show', $booking) }}"
               class="inline-block bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">
                تلاش مجدد
            </a>
            <a href="{{ route('home') }}"
               class="inline-block bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300">
                بازگشت به صفحه اصلی
            </a>
        </div>
    </div>
@endsection
