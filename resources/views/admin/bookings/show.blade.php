@extends('layouts.admin')

@section('content')
    <div class="container mx-auto px-4 py-6">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold">جزئیات رزرو #{{ $booking->id }}</h1>
                <a href="{{ route('admin.bookings.index') }}" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                    بازگشت به لیست
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-gray-50 p-4 rounded">
                    <h2 class="text-xl font-semibold mb-4">اطلاعات مشتری</h2>
                    <div class="space-y-3">
                        @if($booking->user)
                            <p><span class="font-medium">نام:</span> {{ $booking->user->name }}</p>
                            <p><span class="font-medium">شماره تماس:</span> {{ $booking->user->phone }}</p>
                            <p><span class="font-medium">ایمیل:</span> {{ $booking->user->email }}</p>
                        @else
                            <p class="text-red-600">اطلاعات کاربر در دسترس نیست</p>
                        @endif
                    </div>
                </div>

                <div class="bg-gray-50 p-4 rounded">
                    <h2 class="text-xl font-semibold mb-4">جزئیات نوبت</h2>
                    <div class="space-y-3">
                        <p><span class="font-medium">تاریخ و ساعت:</span> {{ verta($booking->booking_time)->format('Y/m/d H:i') }}</p>

                        @if($booking->service)
                            <p><span class="font-medium">خدمت:</span> {{ $booking->service->name }}</p>
                            <p><span class="font-medium">قیمت:</span> {{ number_format($booking->prepayment_amount) }} تومان</p>
                        @else
                            <p class="text-red-600">اطلاعات خدمت در دسترس نیست</p>
                        @endif

                        @if($booking->specialist)
                            <p><span class="font-medium">متخصص:</span> {{ $booking->specialist->name }}</p>
                        @else
                            <p class="text-red-600">اطلاعات متخصص در دسترس نیست</p>
                        @endif
                    </div>
                </div>

                <div class="bg-gray-50 p-4 rounded">
                    <h2 class="text-xl font-semibold mb-4">وضعیت</h2>
                    <div class="space-y-3">
                        <p>
                            <span class="font-medium">وضعیت نوبت:</span>
                            <span class="px-2 py-1 rounded text-sm {{ $booking->getStatusBadgeAttribute() }}">
                            {{ $booking->getStatusTextAttribute() }}
                        </span>
                        </p>
                        <p>
                            <span class="font-medium">وضعیت پرداخت:</span>
                            <span class="px-2 py-1 rounded text-sm
                            @if($booking->payment_status == 'paid') bg-green-100 text-green-800
                            @else bg-red-100 text-red-800 @endif">
                            {{ $booking->payment_status == 'paid' ? 'پرداخت شده' : 'پرداخت نشده' }}
                        </span>
                        </p>
                        <p><span class="font-medium">تاریخ ثبت:</span> {{ verta($booking->created_at)->format('Y/m/d H:i') }}</p>
                    </div>
                </div>

                <div class="bg-gray-50 p-4 rounded">
                    <h2 class="text-xl font-semibold mb-4">عملیات</h2>
                    <div class="flex space-x-4 space-x-reverse">
                        @if($booking->status == 'pending')
                            <form action="{{ route('admin.bookings.update', $booking) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="status" value="confirmed">
                                <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                                    تایید رزرو
                                </button>
                            </form>
                        @endif

                        @if($booking->status != 'cancelled')
                            <form action="{{ route('admin.bookings.update', $booking) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="status" value="cancelled">
                                <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">
                                    لغو رزرو
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                @if($booking->payment_status == 'paid')
                    <div class="bg-gray-50 p-4 rounded">
                        <h2 class="text-xl font-semibold mb-4">اطلاعات مالی</h2>
                        <div class="space-y-3">
                            <p><span class="font-medium">مبلغ پرداختی:</span> {{ number_format($booking->prepayment_amount) }} تومان</p>
                            <p><span class="font-medium">شماره پیگیری:</span> {{ $booking->payment_ref ?? 'ندارد' }}</p>
                            @if($booking->discount_amount)
                                <p><span class="font-medium">کد تخفیف:</span> {{ $booking->discount_code }}</p>
                                <p><span class="font-medium">مبلغ تخفیف:</span> {{ number_format($booking->discount_amount) }} تومان</p>
                            @endif
                            @if($booking->refund_status)
                                <p>
                                    <span class="font-medium">وضعیت استرداد:</span>
                                    <span class="px-2 py-1 rounded text-sm" style="background-color: {{ $booking->getRefundStatusColorAttribute() }}">
                                {{ $booking->getRefundStatusTextAttribute() }}
                            </span>
                                </p>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
