@extends('layouts.app')

@section('title', 'جزئیات نوبت')

@section('content')
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="border-b pb-4 mb-4">
                <h1 class="text-2xl font-bold">جزئیات نوبت</h1>
                <p class="text-gray-500">شماره نوبت: {{ $booking->id }}</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h2 class="text-lg font-bold mb-4">اطلاعات نوبت</h2>
                    <div class="space-y-3">
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
                        <div>
                            <span class="text-gray-600">وضعیت:</span>
                            @switch($booking->status)
                                @case('pending')
                                    <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-sm">
                                    در انتظار تایید
                                </span>
                                    @break
                                @case('confirmed')
                                    <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-sm">
                                    تایید شده
                                </span>
                                    @break
                                @case('cancelled')
                                    <span class="bg-red-100 text-red-800 px-2 py-1 rounded text-sm">
                                    لغو شده
                                </span>
                                    @break
                            @endswitch
                        </div>
                        <div>
                            <span class="text-gray-600">مدت زمان:</span>
                            <span class="font-medium">{{ $booking->service->duration }} دقیقه</span>
                        </div>
                    </div>
                </div>

                <div>
                    <h2 class="text-lg font-bold mb-4">اطلاعات پرداخت</h2>
                    <div class="space-y-3">
                        <div>
                            <span class="text-gray-600">مبلغ کل:</span>
                            <span class="font-medium">{{ number_format($booking->service->price) }} تومان</span>
                        </div>
                        <div>
                            <span class="text-gray-600">پیش پرداخت:</span>
                            <span class="font-medium">{{ number_format($booking->prepayment_amount) }} تومان</span>
                        </div>
                        <div>
                            <span class="text-gray-600">وضعیت پرداخت:</span>
                            @if($booking->payment_status == 'paid')
                                <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-sm">
                                پرداخت شده
                            </span>
                            @else
                                <span class="bg-red-100 text-red-800 px-2 py-1 rounded text-sm">
                                پرداخت نشده
                            </span>
                            @endif
                        </div>
                        @if($booking->payment_ref)
                            <div>
                                <span class="text-gray-600">شماره پیگیری:</span>
                                <span class="font-medium" dir="ltr">{{ $booking->payment_ref }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="mt-8">
                <div id="booking-actions" data-booking="{{ json_encode($booking) }}"></div>

                <div class="mt-4">
                    <a href="{{ route('bookings.index') }}"
                       class="bg-gray-200 text-gray-700 px-4 py-2 rounded">
                        بازگشت به لیست نوبت‌ها
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
