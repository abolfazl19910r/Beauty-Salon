@extends('layouts.specialist')

@section('title', 'جزئیات نوبت رزرو شده')

@section('content')
    <div class="fade-in max-w-4xl mx-auto">
        <div class="mb-6 flex justify-between items-center">
            <a href="{{ route('specialist.bookings') }}" class="inline-flex items-center text-gray-600 hover:text-pink-600 transition">
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                بازگشت به لیست
            </a>
        </div>

        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                <div>
                    <span class="text-xs text-gray-500">شماره پیگیری: #{{ $booking->id }}</span>
                    <h1 class="text-xl font-bold text-gray-800 mt-1">{{ $booking->service->name }}</h1>
                </div>
                <div>
                    @php $status = trim($booking->status); @endphp
                    @if($status == 'pending')
                        <span class="px-4 py-2 bg-yellow-100 text-yellow-800 rounded-lg font-bold text-sm">⏳ در انتظار تایید</span>
                    @elseif($status == 'confirmed')
                        <span class="px-4 py-2 bg-blue-100 text-blue-800 rounded-lg font-bold text-sm">✅ تایید شده</span>
                    @elseif($status == 'completed')
                        <span class="px-4 py-2 bg-green-100 text-green-800 rounded-lg font-bold text-sm">✔️ انجام شده</span>
                    @elseif($status == 'cancelled')
                        <span class="px-4 py-2 bg-red-100 text-red-800 rounded-lg font-bold text-sm">❌ لغو شده</span>
                    @elseif($status == 'pending_payment')
                        <span class="px-4 py-2 bg-orange-100 text-orange-800 rounded-lg font-bold text-sm">💳 در انتظار پرداخت</span>
                    @endif
                </div>
            </div>

            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-gray-700 border-b pb-2">اطلاعات مشتری</h3>
                        <p class="text-gray-600"><strong>نام مشتری:</strong> {{ $booking->user->name }}</p>
                        <p class="text-gray-600"><strong>شماره تماس:</strong> <span class="dir-ltr">{{ $booking->user->phone }}</span></p>
                    </div>

                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-gray-700 border-b pb-2">زمان و وضعیت مالی</h3>
                        <p class="text-gray-600"><strong>تاریخ نوبت:</strong> {{ verta($booking->booking_time)->format('l، d F Y') }}</p>
                        <p class="text-gray-600"><strong>ساعت:</strong> {{ \Carbon\Carbon::parse($booking->booking_time)->format('H:i') }}</p>
                        <p class="text-gray-600">
                            <strong>وضعیت پرداخت:</strong>
                            @switch($booking->payment_status)
                                @case('paid') <span class="text-green-600 font-bold">✅ پرداخت شده</span> @break
                                @case('pending_payment') <span class="text-yellow-600 font-bold">⏳ در انتظار پرداخت</span> @break
                                @default <span class="text-red-600 font-bold">❌ پرداخت نشده</span>
                            @endswitch
                        </p>
                    </div>
                </div>

                @php
                    $isAutoConfirm = $specialist->auto_confirm_bookings ?? false;
                    $canShowButtons = !in_array($status, ['completed', 'cancelled', 'pending_payment']);
                @endphp

                @if($canShowButtons)
                    <div class="mt-10 pt-6 border-t flex flex-wrap gap-4 justify-end">
                        @if($status == 'pending')
                            <form action="{{ route('specialist.bookings.complete', $booking->id) }}" method="POST" class="inline">
                                @csrf @method('PUT')
                                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-8 py-3 rounded-lg font-bold transition shadow-md">
                                    ✅ پذیرش نوبت
                                </button>
                            </form>
                            <button onclick="document.getElementById('cancelModal').classList.remove('hidden')"
                                    class="bg-red-500 hover:bg-red-600 text-white px-8 py-3 rounded-lg font-bold transition shadow-md">
                                ❌ لغو این نوبت
                            </button>
                        @endif

                        @if($status == 'confirmed')
                            <form action="{{ route('specialist.bookings.mark-completed', $booking->id) }}" method="POST" class="inline">
                                @csrf @method('PUT')
                                <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-8 py-3 rounded-lg font-bold transition shadow-md">
                                    ✔️ انجام شده
                                </button>
                            </form>

                            <button onclick="document.getElementById('cancelModal').classList.remove('hidden')"
                                    class="bg-red-500 hover:bg-red-600 text-white px-8 py-3 rounded-lg font-bold transition shadow-md">
                                ❌ لغو این نوبت
                            </button>
                        @endif
                    </div>
                @endif

                @if($status == 'completed')
                    <div class="mt-10 pt-6 border-t">
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-center">
                            <p class="text-green-800 font-bold">✔️ انجام شده</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div id="cancelModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl p-6 max-w-md w-full shadow-2xl">
            <h3 class="text-xl font-bold text-gray-900 mb-2">❌ لغو نوبت</h3>
            <p class="text-gray-500 mb-4">آیا از لغو نوبت "{{ $booking->service->name }}" اطمینان دارید؟</p>
            <form action="{{ route('specialist.bookings.cancel', $booking->id) }}" method="POST">
                @csrf @method('PUT')
                <div class="mb-5">
                    <label class="block text-sm font-medium text-gray-700 mb-2">دلیل لغو (برای اطلاع به مشتری):</label>
                    <textarea name="cancel_reason" required class="w-full border rounded-lg p-3" rows="3" placeholder="مثلاً: تداخل در برنامه کاری..."></textarea>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="document.getElementById('cancelModal').classList.add('hidden')" class="flex-1 bg-gray-100 py-2 rounded-lg hover:bg-gray-200">انصراف</button>
                    <button type="submit" class="flex-1 bg-red-600 text-white py-2 rounded-lg hover:bg-red-700">تایید لغو نوبت</button>
                </div>
            </form>
        </div>
    </div>
@endsection
