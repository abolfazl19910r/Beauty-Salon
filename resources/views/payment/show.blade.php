@extends('layouts.app')

@section('title', 'پرداخت نوبت')

@section('content')
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="border-b pb-4 mb-6">
                <h1 class="text-2xl font-bold">پرداخت نوبت</h1>
                <p class="text-gray-500">شماره نوبت: {{ $booking->id }}</p>
            </div>

            <!-- اطلاعات نوبت -->
            <div class="mb-6">
                <h2 class="text-lg font-bold mb-4">جزئیات نوبت</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <span class="text-gray-600">خدمت:</span>
                        <span class="font-medium">{{ $booking->service->name }}</span>
                    </div>
                    <div>
                        <span class="text-gray-600">متخصص:</span>
                        <span class="font-medium">{{ $booking->specialist->name }}</span>
                    </div>
                    <div>
                        <span class="text-gray-600">تاریخ و ساعت:</span>
                        <span class="font-medium" dir="ltr">
                        {{ verta($booking->booking_time)->format('Y/m/d H:i') }}
                    </span>
                    </div>
                </div>
            </div>

            <!-- جزئیات پرداخت -->
            <div class="bg-gray-50 p-4 rounded mb-6">
                <h3 class="font-bold mb-4">جزئیات پرداخت</h3>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span>مبلغ کل:</span>
                        <span>{{ number_format($booking->service->price) }} تومان</span>
                    </div>
                    <div class="flex justify-between text-green-600 font-bold">
                        <span>مبلغ پیش پرداخت:</span>
                        <span>{{ number_format($booking->prepayment_amount) }} تومان</span>
                    </div>
                </div>
            </div>

            <!-- دکمه پرداخت -->
            <form action="{{ route('payment.process', $booking) }}" method="POST">
                @csrf
                <button type="submit" class="w-full bg-green-500 text-white py-3 rounded-lg font-bold">
                    پرداخت و تایید نوبت
                </button>
            </form>

            <div class="mt-4 text-center">
                <a href="{{ route('bookings.show', $booking) }}" class="text-gray-600">
                    بازگشت به جزئیات نوبت
                </a>
            </div>
        </div>
    </div>
@endsection
