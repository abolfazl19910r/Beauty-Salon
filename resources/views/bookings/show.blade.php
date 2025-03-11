@extends('layouts.app')

@section('title', 'جزئیات نوبت')

@section('content')
    <div class="max-w-4xl mx-auto fade-in">
        <div class="bg-white rounded-lg shadow-sm hover-shadow p-6">
            <div class="border-b pb-4 mb-6">
                <h1 class="text-2xl font-bold bg-gradient-to-r from-pink-500 to-purple-600 bg-clip-text text-transparent">جزئیات نوبت</h1>
                <p class="text-gray-500 mt-2 flex items-center">
                    <svg class="w-5 h-5 ml-1 text-pink-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path>
                        <line x1="7" y1="7" x2="7.01" y2="7"></line>
                    </svg>
                    شماره نوبت: {{ $booking->id }}
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-gray-50 p-5 rounded-lg">
                    <h2 class="text-lg font-bold mb-4 flex items-center text-pink-600">
                        <svg class="w-5 h-5 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                        اطلاعات نوبت
                    </h2>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-600">خدمت:</span>
                            <span class="font-medium">{{ $booking->service->name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">متخصص:</span>
                            <span class="font-medium">{{ $booking->specialist->name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">تاریخ و ساعت:</span>
                            <span class="font-medium persian-number" dir="ltr">
                                {{ verta($booking->booking_time)->format('Y/m/d H:i') }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">وضعیت:</span>
                            @switch($booking->status)
                                @case('pending')
                                    <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full text-xs inline-flex items-center">
                                        <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        در انتظار تایید
                                    </span>
                                    @break
                                @case('confirmed')
                                    <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs inline-flex items-center">
                                        <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        تایید شده
                                    </span>
                                    @break
                                @case('cancelled')
                                    <span class="bg-red-100 text-red-800 px-2 py-1 rounded-full text-xs inline-flex items-center">
                                        <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        لغو شده
                                    </span>
                                    @break
                            @endswitch
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">مدت زمان:</span>
                            <span class="font-medium persian-number">{{ $booking->service->duration }} دقیقه</span>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 p-5 rounded-lg">
                    <h2 class="text-lg font-bold mb-4 flex items-center text-purple-600">
                        <svg class="w-5 h-5 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                            <line x1="1" y1="10" x2="23" y2="10"></line>
                        </svg>
                        اطلاعات پرداخت
                    </h2>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-600">مبلغ کل:</span>
                            <span class="font-medium persian-number">{{ number_format($booking->service->price) }} تومان</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">پیش پرداخت:</span>
                            <span class="font-medium persian-number">{{ number_format($booking->prepayment_amount) }} تومان</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">وضعیت پرداخت:</span>
                            @if($booking->payment_status == 'paid')
                                <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs inline-flex items-center">
                                    <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    پرداخت شده
                                </span>
                            @else
                                <span class="bg-red-100 text-red-800 px-2 py-1 rounded-full text-xs inline-flex items-center">
                                    <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                    پرداخت نشده
                                </span>
                            @endif
                        </div>
                        @if($booking->payment_ref)
                            <div class="flex justify-between">
                                <span class="text-gray-600">شماره پیگیری:</span>
                                <span class="font-medium" dir="ltr">{{ $booking->payment_ref }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="mt-8">
                <div id="booking-actions" data-booking="{{ json_encode($booking) }}" class="mb-6"></div>

                <div class="flex justify-between">
                    <a href="{{ route('bookings.index') }}"
                       class="inline-flex items-center bg-gray-200 text-gray-700 px-5 py-2 rounded-lg hover:bg-gray-300 transition-colors">
                        <svg class="w-5 h-5 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="19" y1="12" x2="5" y2="12"></line>
                            <polyline points="12 19 5 12 12 5"></polyline>
                        </svg>
                        بازگشت به لیست نوبت‌ها
                    </a>

                    @if($booking->payment_status == 'unpaid')
                        <a href="{{ route('payment.show', $booking) }}"
                           class="bg-gradient-to-r from-pink-500 to-purple-600 text-white px-5 py-2 rounded-lg hover:opacity-90 transition-opacity inline-flex items-center">
                            <svg class="w-5 h-5 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                <line x1="1" y1="10" x2="23" y2="10"></line>
                            </svg>
                            پرداخت نوبت
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
