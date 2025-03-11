@extends('layouts.app')

@section('title', 'پرداخت موفق')

@section('content')
    <div class="max-w-md mx-auto bg-white rounded-lg shadow-sm hover-shadow p-6 text-center fade-in">
        <div class="text-green-500 mb-6">
            <svg class="w-20 h-20 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>
        <h1 class="text-2xl font-bold mb-4 bg-gradient-to-r from-pink-500 to-purple-600 bg-clip-text text-transparent">پرداخت با موفقیت انجام شد</h1>
        <p class="text-gray-600 mb-2">رزرو شما با موفقیت ثبت شد.</p>
        <p class="text-gray-600 mb-6">پیامک تاییدیه برای شما ارسال خواهد شد.</p>

        <div class="bg-gray-50 p-5 rounded-lg mb-6 text-right">
            <h2 class="font-bold mb-4 text-center">اطلاعات پرداخت</h2>
            <div class="space-y-2 persian-number">
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">شماره پیگیری:</span>
                    <span class="font-medium text-pink-700" dir="ltr">{{ $booking->payment_ref }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">مبلغ پرداختی:</span>
                    <span class="font-medium">{{ number_format($booking->prepayment_amount) }} تومان</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">تاریخ پرداخت:</span>
                    <span class="font-medium" dir="ltr">{{ verta($booking->paid_at)->format('Y/m/d H:i') }}</span>
                </div>
            </div>
        </div>

        <div class="space-x-4 space-x-reverse">
            <a href="{{ route('bookings.show', $booking) }}"
               class="inline-block bg-gradient-to-r from-pink-500 to-purple-600 text-white px-6 py-3 rounded-lg hover:opacity-90 transition-opacity">
                مشاهده جزئیات نوبت
            </a>
            <a href="{{ route('home') }}"
               class="inline-block bg-gray-200 text-gray-700 px-6 py-3 rounded-lg hover:bg-gray-300 transition-colors">
                بازگشت به صفحه اصلی
            </a>
        </div>
    </div>
@endsection
