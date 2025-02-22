@extends('layouts.app')

@section('title', 'نوبت‌های من')

@section('content')
    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">نوبت‌های من</h1>
            <a href="{{ route('bookings.create') }}"
               class="bg-green-500 text-white px-4 py-2 rounded">
                رزرو نوبت جدید
            </a>
        </div>

        <div class="bg-white rounded-lg shadow">
            <div class="p-4 border-b">
                <form action="{{ route('bookings.index') }}" method="GET" class="flex gap-4">
                    <select name="status" class="border rounded px-3 py-2">
                        <option value="">همه وضعیت‌ها</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>
                            در انتظار تایید
                        </option>
                        <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>
                            تایید شده
                        </option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>
                            لغو شده
                        </option>
                    </select>

                    <input type="date" name="date" class="border rounded px-3 py-2"
                           value="{{ request('date', date('Y-m-d')) }}">

                    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">
                        فیلتر
                    </button>
                </form>
            </div>

            @if($bookings->isEmpty())
                <div class="p-8 text-center text-gray-500">
                    نوبتی یافت نشد.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                        <tr class="bg-gray-50">
                            <th class="px-6 py-3">خدمت</th>
                            <th class="px-6 py-3">متخصص</th>
                            <th class="px-6 py-3">تاریخ و ساعت</th>
                            <th class="px-6 py-3">وضعیت پرداخت</th>
                            <th class="px-6 py-3">وضعیت</th>
                            <th class="px-6 py-3">عملیات</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y">
                        @foreach($bookings as $booking)
                            <tr>
                                <td class="px-6 py-4">{{ $booking->service->name }}</td>
                                <td class="px-6 py-4">{{ $booking->specialist->name }}</td>
                                <td class="px-6 py-4" dir="ltr">
                                    {{ verta($booking->booking_time)->format('Y/m/d H:i') }}
                                </td>
                                <td class="px-6 py-4">
                                    @if($booking->payment_status == 'paid')
                                        <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-sm">
                                        پرداخت شده
                                    </span>
                                    @else
                                        <span class="bg-red-100 text-red-800 px-2 py-1 rounded text-sm">
                                        پرداخت نشده
                                    </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
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
                                </td>
                                <td class="px-6 py-4">
                                    <a href="{{ route('bookings.show', $booking) }}"
                                       class="text-blue-500">مشاهده جزئیات</a>

                                    @if($booking->payment_status == 'unpaid')
                                        <a href="{{ route('payment.show', $booking) }}"
                                           class="text-green-500 mr-2">پرداخت</a>
                                    @endif

                                    @if($booking->status != 'cancelled' && $booking->booking_time > now())
                                        <a href="{{ route('bookings.reschedule', $booking) }}"
                                           class="text-yellow-500 mr-2">تغییر زمان</a>

                                        <form action="{{ route('bookings.cancel', $booking) }}"
                                              method="POST" class="inline">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit" class="text-red-500 mr-2"
                                                    onclick="return confirm('آیا مطمئن هستید؟')">
                                                لغو نوبت
                                            </button>
                                        </form>
                                    @endif
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
