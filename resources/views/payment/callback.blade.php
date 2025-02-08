@extends('layouts.app')

@section('title', 'نتیجه پرداخت')

@section('content')
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow p-6 text-center">
            @if($success)
                <div class="text-green-500 mb-4">
                    <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold mb-4">پرداخت با موفقیت انجام شد</h1>
                <div class="bg-gray-50 p-4 rounded mb-6">
                    <p class="mb-2">شماره نوبت: {{ $booking->id }}</p>
                    <p class="mb-2">مبلغ پرداخت شده: {{ number_format($booking->prepayment_amount) }} تومان</p>
                    <p>شماره پیگیری: <span dir="ltr">{{ $booking->payment_ref }}</span></p>
                </div>
                <p class="text-gray-600 mb-8">
                    پیامک تاییدیه برای شما ارسال خواهد شد.
                </p>
            @else
                <div class="text-red-500 mb-4">
                    <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold mb-4">خطا در پرداخت</h1>
                <p class="text-gray-600 mb-8">
                    {{ $error_message ?? 'متاسفانه پرداخت با خطا مواجه شد.' }}
                </p>
            @endif

            <div class="space-x-4 space-x-reverse">
                @if($success)
                    <a href="{{ route('bookings.show', $booking) }}"
                       class="bg-blue-500 text-white px-6 py-2 rounded inline-block">
                        مشاهده جزئیات نوبت
                    </a>
                @else
                    <a href="{{ route('payment.show', $booking) }}"
                       class="bg-blue-500 text-white px-6 py-2 rounded inline-block">
                        تلاش مجدد
                    </a>
                @endif

                <a href="{{ route('bookings.index') }}"
                   class="bg-gray-200 text-gray-700 px-6 py-2 rounded inline-block">
                    لیست نوبت‌ها
                </a>
            </div>
        </div>
    </div>
@endsection
