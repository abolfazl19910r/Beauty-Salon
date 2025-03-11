@extends('layouts.app')

@section('title', 'پروفایل کاربری')

@section('content')
    <div class="max-w-7xl mx-auto">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="md:col-span-1">
                <div class="bg-white rounded-lg shadow p-6 hover-shadow">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold flex items-center">
                            <svg class="w-5 h-5 ml-2 text-pink-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            اطلاعات شخصی
                        </h2>
                        <a href="{{ route('profile.edit') }}"
                           class="text-pink-500 hover:text-pink-600 transition-colors flex items-center text-sm">
                            <svg class="w-4 h-4 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                            ویرایش پروفایل
                        </a>
                    </div>

                    <div class="space-y-5">
                        <div>
                            <label class="block text-gray-500 text-sm mb-1">نام</label>
                            <div class="text-gray-900 font-medium">{{ auth()->user()->name }}</div>
                        </div>

                        <div>
                            <label class="block text-gray-500 text-sm mb-1">شماره تماس</label>
                            <div class="text-gray-900 font-medium persian-number" dir="ltr">{{ auth()->user()->phone }}</div>
                        </div>

                        <div>
                            <label class="block text-gray-500 text-sm mb-1">ایمیل</label>
                            <div class="text-gray-900 font-medium" dir="ltr">{{ auth()->user()->email ?: 'ثبت نشده' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="md:col-span-2">
                <div class="bg-white rounded-lg shadow p-6 hover-shadow">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold flex items-center">
                            <svg class="w-5 h-5 ml-2 text-pink-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                <line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                            نوبت‌های من
                        </h2>
                        <a href="{{ route('bookings.create') }}"
                           class="bg-gradient-to-r from-pink-500 to-purple-600 text-white px-4 py-2 rounded-lg hover:opacity-90 transition-opacity text-sm flex items-center">
                            <svg class="w-4 h-4 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                            رزرو نوبت جدید
                        </a>
                    </div>

                    @if($bookings->isEmpty())
                        <div class="text-center py-12 bg-gray-50 rounded-lg">
                            <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                <line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                            <p class="text-gray-500 mb-4">شما هنوز نوبتی رزرو نکرده‌اید</p>
                            <a href="{{ route('services.index') }}"
                               class="text-pink-500 hover:text-pink-600 transition-colors inline-flex items-center">
                                <span>مشاهده لیست خدمات</span>
                                <svg class="w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                    <polyline points="12 5 19 12 12 19"></polyline>
                                </svg>
                            </a>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-4 py-3 text-right">خدمت</th>
                                    <th class="px-4 py-3 text-right">متخصص</th>
                                    <th class="px-4 py-3 text-right">تاریخ</th>
                                    <th class="px-4 py-3 text-right">وضعیت</th>
                                    <th class="px-4 py-3 text-right">عملیات</th>
                                </tr>
                                </thead>
                                <tbody class="divide-y">
                                @foreach($bookings as $booking)
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-4 py-3">{{ $booking->service->name }}</td>
                                        <td class="px-4 py-3">{{ $booking->specialist->name }}</td>
                                        <td class="px-4 py-3 persian-number" dir="ltr">
                                            {{ verta($booking->booking_time)->format('Y/m/d H:i') }}
                                        </td>
                                        <td class="px-4 py-3">
                                            @switch($booking->status)
                                                @case('pending')
                                                    <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full text-xs">
                                                        در انتظار تایید
                                                    </span>
                                                    @break
                                                @case('confirmed')
                                                    <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs">
                                                        تایید شده
                                                    </span>
                                                    @break
                                                @case('cancelled')
                                                    <span class="bg-red-100 text-red-800 px-2 py-1 rounded-full text-xs">
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
