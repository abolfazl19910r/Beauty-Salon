@extends('layouts.app')

@section('title', 'پروفایل کاربری')

@section('content')
    <div class="max-w-7xl mx-auto">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="md:col-span-1">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-bold">اطلاعات شخصی</h2>
                        <a href="{{ route('profile.edit') }}"
                           class="text-blue-500 hover:text-blue-700 text-sm">
                            ویرایش پروفایل
                        </a>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-gray-600 text-sm mb-1">نام</label>
                            <div class="text-gray-900">{{ auth()->user()->name }}</div>
                        </div>

                        <div>
                            <label class="block text-gray-600 text-sm mb-1">شماره تماس</label>
                            <div class="text-gray-900" dir="ltr">{{ auth()->user()->phone }}</div>
                        </div>

                        <div>
                            <label class="block text-gray-600 text-sm mb-1">ایمیل</label>
                            <div class="text-gray-900" dir="ltr">{{ auth()->user()->email }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="md:col-span-2">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-bold">نوبت‌های من</h2>
                        <a href="{{ route('bookings.create') }}"
                           class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 text-sm">
                            رزرو نوبت جدید
                        </a>
                    </div>

                    @if($bookings->isEmpty())
                        <div class="text-center py-12 bg-gray-50 rounded-lg">
                            <p class="text-gray-500 mb-4">شما هنوز نوبتی رزرو نکرده‌اید</p>
                            <a href="{{ route('services.index') }}"
                               class="text-blue-500 hover:text-blue-700">
                                مشاهده لیست خدمات
                            </a>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-4 py-2 text-right">خدمت</th>
                                    <th class="px-4 py-2 text-right">متخصص</th>
                                    <th class="px-4 py-2 text-right">تاریخ</th>
                                    <th class="px-4 py-2 text-right">وضعیت</th>
                                    <th class="px-4 py-2 text-right">عملیات</th>
                                </tr>
                                </thead>
                                <tbody class="divide-y">
                                @foreach($bookings as $booking)
                                    <tr>
                                        <td class="px-4 py-3">{{ $booking->service->name }}</td>
                                        <td class="px-4 py-3">{{ $booking->specialist->name }}</td>
                                        <td class="px-4 py-3" dir="ltr">
                                            {{ verta($booking->booking_time)->format('Y/m/d H:i') }}
                                        </td>
                                        <td class="px-4 py-3">
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
                                        <td class="px-4 py-3">
                                            <div class="flex gap-2">
                                                <a href="{{ route('bookings.show', $booking) }}"
                                                   class="text-blue-500 hover:text-blue-700 text-sm">
                                                    جزئیات
                                                </a>

                                                @if($booking->status != 'cancelled' && $booking->booking_time > now())
                                                    <a href="{{ route('bookings.reschedule', $booking) }}"
                                                       class="text-green-500 hover:text-green-700 text-sm">
                                                        تغییر زمان
                                                    </a>

                                                    <form action="{{ route('bookings.cancel', $booking) }}"
                                                          method="POST" class="inline">
                                                        @csrf
                                                        @method('PUT')
                                                        <button type="submit"
                                                                class="text-red-500 hover:text-red-700 text-sm"
                                                                onclick="return confirm('آیا از لغو نوبت اطمینان دارید؟')">
                                                            لغو نوبت
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

                        <div class="mt-4">
                            {{ $bookings->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
