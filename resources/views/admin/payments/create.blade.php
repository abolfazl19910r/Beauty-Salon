@extends('layouts.admin')

@section('title', 'ثبت پرداخت')

@section('content')
    <div class="container mx-auto px-6">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold flex items-center">
                <svg class="w-6 h-6 ml-2 text-blue-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                    <line x1="1" y1="10" x2="23" y2="10"></line>
                </svg>
                ثبت پرداخت
            </h1>

            <a href="{{ $booking ? route('admin.bookings.show', $booking) : route('admin.bookings.index') }}" class="inline-flex items-center px-4 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                <svg class="w-5 h-5 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 12H5"></path>
                    <path d="M12 19l-7-7 7-7"></path>
                </svg>
                بازگشت
            </a>
        </div>

        <div class="bg-white rounded-lg shadow-lg hover:shadow-xl transition-shadow duration-300 p-6">
            @if($booking)
                <div class="mb-6 p-4 bg-blue-50 rounded-lg">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-gray-600">مشتری: <span class="font-medium">{{ $booking->user->name ?? 'نامشخص' }}</span></p>
                            <p class="text-gray-600">خدمت: <span class="font-medium">{{ $booking->service?->name ?? '—' ?? 'نامشخص' }}</span></p>
                            <p class="text-gray-600">متخصص: <span class="font-medium">{{ $booking->specialist?->name ?? '—' ?? 'نامشخص' }}</span></p>
                        </div>
                        <div>
                            <p class="text-gray-600">تاریخ نوبت: <span class="font-medium">{{ $booking->booking_time ? verta($booking->booking_time)->format('Y/m/d H:i') : 'نامشخص' }}</span></p>
                            <p class="text-gray-600">وضعیت: <span class="font-medium">{{ $booking->status == 'confirmed' ? 'تایید شده' : ($booking->status == 'pending' ? 'در انتظار تایید' : 'لغو شده') }}</span></p>
                            <p class="text-gray-600">مبلغ: <span class="font-medium">{{ $booking->prepayment_amount ? number_format($booking->prepayment_amount) . ' تومان' : 'نامشخص' }}</span></p>
                        </div>
                    </div>
                </div>
            @endif

            <form action="{{ route('admin.payments.store') }}" method="POST" class="space-y-6">
                @csrf

                @if($booking)
                    <input type="hidden" name="booking_id" value="{{ $booking->id }}">
                @else
                    <div>
                        <label for="booking_id" class="block mb-2 text-sm font-medium text-gray-700">انتخاب نوبت</label>
                        <select id="booking_id" name="booking_id" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" required>
                            <option value="">انتخاب نوبت</option>
                            <!-- لیست نوبت‌ها -->
                        </select>
                        @error('booking_id')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                @endif

                <div>
                    <label for="amount" class="block mb-2 text-sm font-medium text-gray-700">مبلغ (تومان)</label>
                    <input type="number" id="amount" name="amount" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" required value="{{ $booking ? $booking->prepayment_amount : '' }}">
                    @error('amount')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="payment_method" class="block mb-2 text-sm font-medium text-gray-700">روش پرداخت</label>
                    <select id="payment_method" name="payment_method" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                        <option value="cash">نقدی</option>
                        <option value="card">کارت خوان</option>
                        <option value="online">آنلاین</option>
                        <option value="transfer">انتقال وجه</option>
                    </select>
                    @error('payment_method')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="reference" class="block mb-2 text-sm font-medium text-gray-700">شماره پیگیری/مرجع</label>
                    <input type="text" id="reference" name="reference" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    @error('reference')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="notes" class="block mb-2 text-sm font-medium text-gray-700">توضیحات</label>
                    <textarea id="notes" name="notes" rows="3" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"></textarea>
                    @error('notes')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-between pt-4 border-t">
                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg shadow hover:shadow-md transition-all duration-200 flex items-center">
                        <svg class="w-5 h-5 ml-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                            <line x1="1" y1="10" x2="23" y2="10"></line>
                        </svg>
                        ثبت پرداخت
                    </button>
                    <a href="{{ $booking ? route('admin.bookings.show', $booking) : route('admin.bookings.index') }}"
                       class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-2 rounded-lg hover:shadow-md transition-all duration-200 flex items-center">
                        <svg class="w-5 h-5 ml-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 12H5"></path>
                            <path d="M12 19l-7-7 7-7"></path>
                        </svg>
                        انصراف
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection
