@extends('layouts.specialist')

@section('title', 'پروفایل من')

@section('content')
    <div class="max-w-7xl mx-auto py-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">پروفایل من</h1>
            <a href="{{ route('specialist.profile.edit') }}"
               class="bg-gradient-to-r from-pink-500 to-purple-600 text-white px-4 py-2 rounded-lg hover:opacity-90 transition-opacity flex items-center">
                <svg class="w-4 h-4 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
                ویرایش پروفایل
            </a>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow hover:shadow-md transition-all duration-200 overflow-hidden">
                    <div class="p-5 border-b border-gray-100 bg-gradient-to-r from-pink-50 to-purple-50">
                        <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                            <svg class="w-5 h-5 ml-2 text-pink-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            اطلاعات شخصی
                        </h2>
                    </div>

                    <div class="p-5 space-y-4">
                        <div>
                            <label class="block text-gray-500 text-sm mb-1">نام</label>
                            <div class="text-gray-900 font-medium">{{ $user->name }}</div>
                        </div>

                        <div>
                            <label class="block text-gray-500 text-sm mb-1">شماره تماس</label>
                            <div class="text-gray-900 font-medium" dir="ltr">{{ $user->phone }}</div>
                        </div>

                        @if($user->email)
                            <div>
                                <label class="block text-gray-500 text-sm mb-1">ایمیل</label>
                                <div class="text-gray-900 font-medium" dir="ltr">{{ $user->email }}</div>
                            </div>
                        @endif

                        @if($specialist)
                            <div class="pt-4 border-t">
                                <label class="block text-gray-500 text-sm mb-2">نقش</label>
                                <span class="px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-sm font-medium">
                                    متخصص
                                </span>
                            </div>

                            @if($specialist->specialty)
                                <div>
                                    <label class="block text-gray-500 text-sm mb-1">تخصص</label>
                                    <div class="text-gray-900">{{ $specialist->specialty }}</div>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>

            <div class="lg:col-span-2">
                @if($upcomingBookings->count() > 0)
                    <div class="bg-white rounded-lg shadow hover:shadow-md transition-all duration-200 overflow-hidden mb-6">
                        <div class="p-5 border-b border-gray-100 bg-blue-50">
                            <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                                <svg class="w-5 h-5 ml-2 text-blue-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                </svg>
                                نوبت‌های آینده من
                            </h2>
                        </div>

                        <div class="p-5 space-y-4">
                            @foreach($upcomingBookings as $booking)
                                <div class="border border-blue-100 rounded-lg p-4 hover:shadow-md transition-all">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <h3 class="font-semibold text-gray-800 mb-2">{{ $booking->service->name }}</h3>
                                            <div class="space-y-1 text-sm">
                                                <p class="text-gray-600">
                                                    <i class="fas fa-user text-pink-500"></i>
                                                    متخصص: {{ $booking->specialist->name }}
                                                </p>
                                                <p class="text-gray-600">
                                                    <i class="fas fa-calendar text-pink-500"></i>
                                                    {{ $booking->booking_date_persian }}
                                                </p>
                                                <p class="text-gray-600">
                                                    <i class="fas fa-clock text-pink-500"></i>
                                                    {{ \Carbon\Carbon::parse($booking->booking_time)->format('H:i') }}
                                                </p>
                                            </div>
                                        </div>

                                        <div class="text-left">
                                            @if($booking->status === 'confirmed')
                                                <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">
                                            تایید شده
                                        </span>
                                            @elseif($booking->status === 'pending')
                                                <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-medium">
                                            در انتظار
                                        </span>
                                            @endif

                                            <div class="mt-2 text-sm font-bold text-gray-800">
                                                {{ number_format($booking->prepayment_amount) }} تومان
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="bg-white rounded-lg shadow hover:shadow-md transition-all duration-200 overflow-hidden">
                    <div class="p-5 border-b border-gray-100">
                        <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                            <svg class="w-5 h-5 ml-2 text-purple-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            تاریخچه نوبت‌های من
                        </h2>
                    </div>

                    <div class="p-5">
                        @if($myBookings->isEmpty())
                            <div class="text-center py-12 bg-gray-50 rounded-lg">
                                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                </svg>
                                <p class="text-gray-500 mb-4">شما هنوز نوبتی رزرو نکرده‌اید</p>
                                <a href="{{ route('services.index') }}"
                                   class="text-pink-500 hover:text-pink-600 transition-colors inline-flex items-center">
                                    <span>مشاهده لیست خدمات</span>
                                    <svg class="w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="5" y1="12" x2="19" y2="12"></line>
                                        <polyline points="12 5 19 12 12 19"></polyline>
                                    </svg>
                                </a>
                            </div>
                        @else
                            <div class="space-y-3">
                                @foreach($myBookings as $booking)
                                    <div class="border rounded-lg p-4 hover:shadow-sm transition-all">
                                        <div class="flex justify-between items-start">
                                            <div class="flex-1">
                                                <h3 class="font-semibold text-gray-800">{{ $booking->service->name }}</h3>
                                                <div class="mt-2 space-y-1 text-sm text-gray-600">
                                                    <p>
                                                        <i class="fas fa-user"></i>
                                                        {{ $booking->specialist->name }}
                                                    </p>
                                                    <p>
                                                        <i class="fas fa-calendar"></i>
                                                        {{ $booking->booking_date_persian }}
                                                        -
                                                        <i class="fas fa-clock"></i>
                                                        {{ \Carbon\Carbon::parse($booking->booking_time)->format('H:i') }}
                                                    </p>
                                                </div>
                                            </div>

                                            <div class="text-left">
                                                @switch($booking->status)
                                                    @case('pending')
                                                        <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-medium">
                                                        در انتظار
                                                    </span>
                                                        @break
                                                    @case('confirmed')
                                                        <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">
                                                        تایید شده
                                                    </span>
                                                        @break
                                                    @case('completed')
                                                        <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-medium">
                                                        انجام شده
                                                    </span>
                                                        @break
                                                    @case('cancelled')
                                                        <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-xs font-medium">
                                                        لغو شده
                                                    </span>
                                                        @break
                                                @endswitch

                                                <div class="mt-2 text-sm font-bold text-gray-800">
                                                    {{ number_format($booking->prepayment_amount) }} تومان
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="mt-4">
                                {{ $myBookings->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
@endsection
