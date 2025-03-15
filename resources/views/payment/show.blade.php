@extends('layouts.app')

@section('title', 'پرداخت نوبت')

@section('content')
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-6 hover-shadow">
            <div class="border-b pb-4 mb-6 flex items-center">
                <svg class="w-6 h-6 ml-2 text-pink-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                    <line x1="1" y1="10" x2="23" y2="10"></line>
                </svg>
                <div>
                    <h1 class="text-2xl font-bold">پرداخت نوبت</h1>
                    <p class="text-gray-500">شماره نوبت: {{ $booking->id }}</p>
                </div>
            </div>

            <div class="mb-6">
                <h2 class="text-lg font-bold mb-4 flex items-center">
                    <svg class="w-5 h-5 ml-1 text-pink-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                    جزئیات نوبت
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-gray-50 p-4 rounded-lg">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 ml-2 text-gray-400 mt-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                        </svg>
                        <div>
                            <span class="text-gray-600 block mb-1">خدمت:</span>
                            <span class="font-medium">
                                @if($booking->service)
                                    {{ $booking->service->name }}
                                @else
                                    خدمت نامشخص
                                @endif
                            </span>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <svg class="w-5 h-5 ml-2 text-gray-400 mt-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="8.5" cy="7" r="4"></circle>
                            <polyline points="17 11 19 13 23 9"></polyline>
                        </svg>
                        <div>
                            <span class="text-gray-600 block mb-1">متخصص:</span>
                            <span class="font-medium">
                                @if($booking->specialist)
                                    {{ $booking->specialist->name }}
                                @else
                                    متخصص نامشخص
                                @endif
                            </span>
                        </div>
                    </div>
                    <div class="flex items-start md:col-span-2">
                        <svg class="w-5 h-5 ml-2 text-gray-400 mt-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        <div>
                            <span class="text-gray-600 block mb-1">تاریخ و ساعت:</span>
                            <span class="font-medium persian-number" dir="ltr">
                                {{ verta($booking->booking_time)->format('Y/m/d H:i') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-blue-50 p-5 rounded-lg mb-6">
                <h3 class="font-bold mb-4 flex items-center text-blue-700">
                    <svg class="w-5 h-5 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="1" x2="12" y2="23"></line>
                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                    </svg>
                    جزئیات پرداخت
                </h3>
                <div class="space-y-3">
                    <div class="flex justify-between items-center border-b border-blue-100 pb-2">
                        <span class="text-blue-700">مبلغ کل:</span>
                        <span class="persian-number">
                            @if($booking->service)
                                {{ number_format($booking->service->price) }} تومان
                            @else
                                مبلغ نامشخص
                            @endif
                        </span>
                    </div>
                    <div class="flex justify-between items-center text-lg">
                        <span class="text-blue-700 font-bold">مبلغ پیش پرداخت:</span>
                        <span class="text-blue-700 font-bold persian-number">{{ number_format($booking->prepayment_amount) }} تومان</span>
                    </div>
                </div>
            </div>

            <form action="{{ route('payment.process', ['booking' => $booking->id]) }}" method="POST">
                @csrf
                <button type="submit" class="w-full bg-gradient-to-r from-pink-500 to-purple-600 text-white py-3 rounded-lg font-bold hover:opacity-90 transition-colors flex items-center justify-center">
                    <svg class="w-5 h-5 ml-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                        <line x1="1" y1="10" x2="23" y2="10"></line>
                    </svg>
                    پرداخت و تایید نوبت
                </button>
            </form>

            <div class="mt-4 text-center">
                <a href="javascript:history.back()" class="text-pink-600 hover:text-pink-700 inline-flex items-center">
                    <svg class="w-4 h-4 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    بازگشت به صفحه قبل
                </a>
            </div>
        </div>
    </div>
@endsection
