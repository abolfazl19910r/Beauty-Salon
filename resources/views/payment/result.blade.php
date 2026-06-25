@extends('layouts.app')

@section('title', $success ? 'پرداخت موفق' : 'پرداخت ناموفق')

@section('content')
    <div class="max-w-2xl mx-auto py-8 fade-in">
        @if($success)
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <div class="bg-gradient-to-r from-green-500 to-emerald-600 p-6 text-center">
                    <div class="inline-flex items-center justify-center w-20 h-20 bg-white rounded-full mb-4">
                        <svg class="w-12 h-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h1 class="text-3xl font-bold text-white mb-2">پرداخت موفق!</h1>
                    <p class="text-green-100">نوبت شما با موفقیت ثبت شد</p>
                </div>
                <div class="p-6 border-b bg-gray-50">
                    <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                        <svg class="w-6 h-6 ml-2 text-purple-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                            <line x1="1" y1="10" x2="23" y2="10"></line>
                        </svg>
                        اطلاعات پرداخت
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-white p-4 rounded-lg border border-gray-200">
                            <span class="text-sm text-gray-600 block mb-1">شماره پیگیری:</span>
                            <span class="font-bold text-lg text-purple-700" dir="ltr">
                                @if($booking->payment_reference)
                                    {{ $booking->payment_reference }}
                                @else
                                    #{{ $booking->id }}
                                @endif
                            </span>
                        </div>

                        <div class="bg-white p-4 rounded-lg border border-gray-200">
                            <span class="text-sm text-gray-600 block mb-1">مبلغ پرداختی:</span>
                            <span class="font-bold text-lg text-green-700 persian-number">
                                {{ number_format($booking->prepayment_amount) }} تومان
                            </span>
                        </div>

                        <div class="bg-white p-4 rounded-lg border border-gray-200">
                            <span class="text-sm text-gray-600 block mb-1">تاریخ پرداخت:</span>
                            <span class="font-medium text-gray-900 persian-number" dir="ltr">
                                {{ verta($booking->paid_at)->format('Y/m/d H:i') }}
                            </span>
                        </div>

                        <div class="bg-white p-4 rounded-lg border border-gray-200">
                            <span class="text-sm text-gray-600 block mb-1">کد نوبت:</span>
                            <span class="font-bold text-lg text-pink-700 persian-number">
                                #{{ $booking->id }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="p-6 border-b">
                    <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                        <svg class="w-6 h-6 ml-2 text-pink-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        جزئیات نوبت
                    </h2>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center py-2 border-b">
                            <span class="text-gray-600">خدمت:</span>
                            <span class="font-medium text-gray-900">{{ $booking->service?->name ?? '—' }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b">
                            <span class="text-gray-600">متخصص:</span>
                            <span class="font-medium text-gray-900">{{ $booking->specialist?->name ?? '—' }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b">
                            <span class="text-gray-600">تاریخ:</span>
                            <span class="font-medium text-gray-900 persian-number" dir="ltr">
                                {{ verta($booking->booking_time)->format('Y/m/d') }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b">
                            <span class="text-gray-600">ساعت:</span>
                            <span class="font-medium text-gray-900" dir="ltr">
                                {{ verta($booking->booking_time)->format('H:i') }}
                            </span>
                        </div>
                        @if($booking->service->duration)
                            <div class="flex justify-between items-center py-2">
                                <span class="text-gray-600">مدت زمان:</span>
                                <span class="font-medium text-gray-900 persian-number">
                                    {{ $booking->service->duration }} دقیقه
                                </span>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="p-6 bg-blue-50">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            @if($booking->status === 'confirmed')
                                <svg class="w-8 h-8 text-green-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                </svg>
                            @else
                                <svg class="w-8 h-8 text-yellow-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="12" y1="8" x2="12" y2="12"></line>
                                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                </svg>
                            @endif
                        </div>
                        <div class="mr-3">
                            @if($booking->status === 'confirmed')
                                <h3 class="text-lg font-semibold text-green-800">نوبت شما تایید شد!</h3>
                                <p class="text-sm text-green-700 mt-1">
                                    نوبت شما به صورت خودکار تایید شده است. لطفاً 15 دقیقه قبل از وقت حضور داشته باشید.
                                </p>
                            @else
                                <h3 class="text-lg font-semibold text-yellow-800">در انتظار تایید متخصص</h3>
                                <p class="text-sm text-yellow-700 mt-1">
                                    نوبت شما ثبت شد و در انتظار تایید متخصص است. پس از تایید، پیامک برای شما ارسال خواهد شد.
                                </p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="p-6 bg-gray-50 flex flex-col sm:flex-row gap-3">
                    <a href="{{ route('bookings.show', $booking) }}"
                       class="flex-1 text-center bg-gradient-to-r from-pink-500 to-purple-600 text-white px-6 py-3 rounded-lg hover:opacity-90 transition-opacity inline-flex items-center justify-center">
                        <svg class="w-5 h-5 ml-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        مشاهده جزئیات نوبت
                    </a>
                    <a href="{{ route('home') }}"
                       class="flex-1 text-center bg-gray-200 text-gray-700 px-6 py-3 rounded-lg hover:bg-gray-300 transition-colors inline-flex items-center justify-center">
                        <svg class="w-5 h-5 ml-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                        </svg>
                        بازگشت به صفحه اصلی
                    </a>
                </div>
            </div>

        @else
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <div class="bg-gradient-to-r from-red-500 to-rose-600 p-6 text-center">
                    <div class="inline-flex items-center justify-center w-20 h-20 bg-white rounded-full mb-4">
                        <svg class="w-12 h-12 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </div>
                    <h1 class="text-3xl font-bold text-white mb-2">پرداخت ناموفق</h1>
                    <p class="text-red-100">متأسفانه پرداخت شما انجام نشد</p>
                </div>

                <div class="p-6 border-b">
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <p class="text-red-800 text-center">
                            {{ $error_message ?? 'خطا در انجام پرداخت' }}
                        </p>
                    </div>
                </div>

                @if($booking)
                    <div class="p-6 border-b">
                        <h2 class="text-lg font-bold text-gray-800 mb-4">اطلاعات نوبت:</h2>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">خدمت:</span>
                                <span class="font-medium">{{ $booking->service?->name ?? '—' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">متخصص:</span>
                                <span class="font-medium">{{ $booking->specialist?->name ?? '—' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">تاریخ و ساعت:</span>
                                <span class="font-medium persian-number" dir="ltr">
                                    {{ verta($booking->booking_time)->format('Y/m/d H:i') }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">مبلغ قابل پرداخت:</span>
                                <span class="font-bold text-red-600 persian-number">
                                    {{ number_format($booking->prepayment_amount) }} تومان
                                </span>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="p-6 bg-gray-50 flex flex-col sm:flex-row gap-3">
                    @if($booking)
                        <a href="{{ route('payment.show', $booking) }}"
                           class="flex-1 text-center bg-gradient-to-r from-pink-500 to-purple-600 text-white px-6 py-3 rounded-lg hover:opacity-90 transition-opacity inline-flex items-center justify-center">
                            <svg class="w-5 h-5 ml-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                <line x1="1" y1="10" x2="23" y2="10"></line>
                            </svg>
                            تلاش مجدد برای پرداخت
                        </a>
                    @endif
                    <a href="{{ route('home') }}"
                       class="flex-1 text-center bg-gray-200 text-gray-700 px-6 py-3 rounded-lg hover:bg-gray-300 transition-colors inline-flex items-center justify-center">
                        <svg class="w-5 h-5 ml-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                        </svg>
                        بازگشت به صفحه اصلی
                    </a>
                </div>
            </div>
        @endif
    </div>
@endsection
