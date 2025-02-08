@extends('layouts.app')

@section('title', 'خطا در پرداخت')

@section('content')
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow p-6 text-center">
            <div class="text-red-500 mb-4">
                <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <h1 class="text-2xl font-bold mb-4">تراکنش ناموفق</h1>
            <p class="text-gray-600 mb-8">
                {{ $error_message ?? 'متاسفانه در پردازش پرداخت شما مشکلی پیش آمده است.' }}
            </p>

            <div class="mb-8 text-right bg-gray-50 p-4 rounded">
                <h2 class="font-bold mb-2">راهنمایی:</h2>
                <ul class="list-disc list-inside space-y-2 text-gray-600">
                    <li>از اتصال اینترنت خود اطمینان حاصل کنید</li>
                    <li>موجودی کافی در حساب خود را بررسی کنید</li>
                    <li>در صورت کسر وجه، تا 72 ساعت آینده به حساب شما برگشت داده می‌شود</li>
                    <li>در صورت نیاز به پیگیری با پشتیبانی تماس بگیرید</li>
                </ul>
            </div>

            <div class="space-x-4 space-x-reverse">
                <a href="{{ route('payment.show', $booking) }}"
                   class="bg-blue-500 text-white px-6 py-2 rounded inline-block">
                    تلاش مجدد
                </a>

                <a href="{{ route('bookings.show', $booking) }}"
                   class="bg-gray-200 text-gray-700 px-6 py-2 rounded inline-block">
                    بازگشت به جزئیات نوبت
                </a>
            </div>
        </div>
    </div>
@endsection
