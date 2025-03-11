@extends('layouts.app')

@section('title', 'خطا در پرداخت')

@section('content')
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-6 text-center hover-shadow">
            <div class="text-red-500 mb-6">
                <svg class="w-20 h-20 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <h1 class="text-2xl font-bold mb-4">تراکنش ناموفق</h1>
            <p class="text-gray-600 mb-6">
                {{ $error_message ?? 'متاسفانه در پردازش پرداخت شما مشکلی پیش آمده است.' }}
            </p>

            <div class="mb-8 text-right bg-gray-50 p-5 rounded-lg">
                <h2 class="font-bold mb-3 flex items-center">
                    <svg class="w-5 h-5 ml-1 text-blue-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    راهنمایی:
                </h2>
                <ul class="list-disc list-inside space-y-2 text-gray-600 pr-2">
                    <li>از اتصال اینترنت خود اطمینان حاصل کنید</li>
                    <li>موجودی کافی در حساب خود را بررسی کنید</li>
                    <li>در صورت کسر وجه، تا 72 ساعت آینده به حساب شما برگشت داده می‌شود</li>
                    <li>در صورت نیاز به پیگیری با پشتیبانی تماس بگیرید</li>
                </ul>
            </div>

            <div class="space-x-4 space-x-reverse">
                <a href="{{ route('payment.show', $booking) }}"
                   class="bg-gradient-to-r from-pink-500 to-purple-600 text-white px-6 py-3 rounded-lg inline-block hover:opacity-90 transition-colors">
                    تلاش مجدد
                </a>

                <a href="{{ route('bookings.show', $booking) }}"
                   class="bg-gray-200 text-gray-700 px-6 py-3 rounded-lg inline-block hover:bg-gray-300 transition-colors">
                    بازگشت به جزئیات نوبت
                </a>
            </div>
        </div>
    </div>
@endsection
