@extends('layouts.app')

@section('title', 'نوبت‌های من')

@section('content')
    <div class="max-w-7xl mx-auto fade-in">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold bg-gradient-to-r from-pink-500 to-purple-600 bg-clip-text text-transparent">نوبت‌های من</h1>
            <a href="{{ route('bookings.create') }}"
               class="bg-gradient-to-r from-pink-500 to-purple-600 text-white px-5 py-2 rounded-lg hover:opacity-90 transition-opacity flex items-center">
                <svg class="w-5 h-5 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                رزرو نوبت جدید
            </a>
        </div>

        <div class="bg-white rounded-lg shadow-sm hover-shadow">
            <div class="p-4 border-b">
                <form action="{{ route('bookings.index') }}" method="GET" class="flex flex-wrap gap-4">
                    <select name="status" class="border rounded-lg px-3 py-2 focus:border-pink-500 focus:ring focus:ring-pink-200 transition-colors">
                        <option value="">همه وضعیت‌ها</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>
                            در انتظار تایید
                        </option>
                        <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>
                            تایید شده
                        </option>
                        <option value="pending_payment" {{ request('status') == 'pending_payment' ? 'selected' : '' }}>
                            در انتظار پرداخت
                        </option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>
                            لغو شده
                        </option>
                    </select>

                    <input type="date" name="date" class="border rounded-lg px-3 py-2 focus:border-pink-500 focus:ring focus:ring-pink-200 transition-colors"
                           value="{{ request('date') }}">

                    <button type="submit" class="bg-gradient-to-r from-pink-500 to-purple-600 text-white px-4 py-2 rounded-lg hover:opacity-90 transition-opacity flex items-center">
                        <svg class="w-5 h-5 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                        </svg>
                        فیلتر
                    </button>
                </form>
            </div>

            @if($bookings->isEmpty())
                <div class="p-12 text-center text-gray-500">
                    <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <p>نوبتی یافت نشد.</p>
                    <a href="{{ route('bookings.create') }}" class="mt-4 inline-block text-pink-500 hover:text-pink-600">
                        رزرو نوبت جدید
                    </a>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                        <tr class="bg-gray-50">
                            <th class="px-6 py-3 text-right">خدمت</th>
                            <th class="px-6 py-3 text-right">متخصص</th>
                            <th class="px-6 py-3 text-right">تاریخ و ساعت</th>
                            <th class="px-6 py-3 text-right">وضعیت پرداخت</th>
                            <th class="px-6 py-3 text-right">وضعیت</th>
                            <th class="px-6 py-3 text-right">عملیات</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y">
                        @foreach($bookings as $booking)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4">{{ $booking->service->name }}</td>
                                <td class="px-6 py-4">{{ $booking->specialist->name }}</td>
                                <td class="px-6 py-4 persian-number" dir="ltr">
                                    {{ verta($booking->booking_time)->format('Y/m/d H:i') }}
                                </td>
                                <td class="px-6 py-4">
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
                                </td>
                                <td class="px-6 py-4">
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
                                        @case('pending_payment')
                                            <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs inline-flex items-center">
                                                <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                                </svg>
                                                در انتظار پرداخت
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
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-2 space-x-reverse">
                                        <a href="{{ route('bookings.show', $booking) }}"
                                           class="text-blue-500 hover:text-blue-700 transition-colors" title="مشاهده جزئیات">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                        </a>

                                        @if($booking->payment_status == 'unpaid' && in_array($booking->status, ['pending_payment', 'confirmed']))
                                            <a href="{{ route('payment.show', $booking) }}"
                                               class="text-green-500 hover:text-green-700 transition-colors" title="پرداخت">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                                </svg>
                                            </a>
                                        @endif

                                        @if($booking->status == 'confirmed' && $booking->booking_time > now())
                                            <a href="{{ route('bookings.reschedule', $booking) }}"
                                               class="text-yellow-500 hover:text-yellow-700 transition-colors" title="تغییر زمان">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                            </a>
                                        @endif

                                        @if(in_array($booking->status, ['pending', 'confirmed', 'pending_payment']) && $booking->booking_time > now()->addHours(24))
                                            <form action="{{ route('bookings.cancel', $booking) }}" method="POST" class="inline">
                                                @csrf
                                                @method('PUT')
                                                <button type="submit" class="text-red-500 hover:text-red-700 transition-colors" title="لغو نوبت"
                                                        onclick="return confirm('آیا مطمئن هستید؟')">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="p-4">
                    {{ $bookings->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
