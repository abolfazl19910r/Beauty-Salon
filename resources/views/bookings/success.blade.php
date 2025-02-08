@extends('layouts.app')

@section('title', 'پرداخت موفق')

@section('content')
    <div class="max-w-md mx-auto bg-white rounded-lg shadow p-6 text-center">
        <div class="text-green-500 mb-4">
            <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>
        <h1 class="text-2xl font-bold mb-4">پرداخت با موفقیت انجام شد</h1>
        <p class="text-gray-600 mb-2">رزرو شما با موفقیت ثبت شد.</p>
        <p class="text-gray-600 mb-6">پیامک تاییدیه برای شما ارسال خواهد شد.</p>

        <div class="bg-gray-50 p-4 rounded-lg mb-6">
            <h2 class="font-bold mb-2">اطلاعات پرداخت:</h2>
            <p class="mb-1">شماره پیگیری: {{ $booking->payment_ref }}</p>
            <p class="mb-1">مبلغ پرداختی: {{ number_format($booking->prepayment_amount) }} تومان</p>
            <p>تاریخ پرداخت: {{ verta($booking->paid_at)->format('Y/m/d H:i') }}</p>
        </div>

        <div class="space-x-4 space-x-reverse">
            <a href="{{ route('bookings.show', $booking) }}"
               class="inline-block bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">
                مشاهده جزئیات نوبت
            </a>
            <a href="{{ route('home') }}"
               class="inline-block bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300">
                بازگشت به صفحه اصلی
            </a>
        </div>
    </div>
@endsection
