@extends('layouts.app')

@section('title', 'داشبورد رزروها')

@section('content')
    <div class="max-w-7xl mx-auto py-6">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">مدیریت نوبت‌های رزرو شده</h1>

        <a href="{{ route('specialist.profile.show') }}"
           class="flex items-center justify-center px-4 py-2 bg-pink-600 hover:bg-pink-700 text-white rounded-lg transition-colors shadow-md mb-4 inline-flex">
            <svg class="w-4 h-4 ml-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
            بازگشت به پروفایل شخصی
        </a>

        @if($bookings->isEmpty())
            <div class="p-6 text-center bg-white rounded-lg shadow">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                <p class="text-gray-500">نوبت فعالی برای انجام وجود ندارد.</p>
            </div>
        @else
            <div class="space-y-4">
                @foreach($bookings as $booking)
                    <div class="bg-white p-5 rounded-lg shadow hover:shadow-md transition-all duration-200 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2">
                                <h3 class="text-lg font-bold text-gray-900">{{ $booking->service->name }}</h3>
                                @switch($booking->status)
                                    @case('pending')
                                        <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-medium">
                                            در انتظار تایید
                                        </span>
                                        @break
                                    @case('confirmed')
                                        <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-medium">
                                            تایید شده
                                        </span>
                                        @break
                                    @case('completed')
                                        <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">
                                            ✓ انجام شده
                                        </span>
                                        @break
                                    @case('cancelled')
                                        <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-xs font-medium">
                                            ✗ لغو شده
                                        </span>
                                        @break
                                    @default
                                        <span class="px-3 py-1 bg-gray-100 text-gray-800 rounded-full text-xs font-medium">
                                            {{ $booking->status }}
                                        </span>
                                @endswitch
                            </div>

                            <div class="space-y-1 text-sm text-gray-600">
                                <p class="flex items-center">
                                    <svg class="w-4 h-4 ml-1 text-pink-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="12" cy="7" r="4"></circle>
                                    </svg>
                                    <span class="font-medium">مشتری:</span> {{ $booking->user->name }}
                                </p>

                                <p class="flex items-center">
                                    <svg class="w-4 h-4 ml-1 text-pink-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                        <line x1="16" y1="2" x2="16" y2="6"></line>
                                        <line x1="8" y1="2" x2="8" y2="6"></line>
                                        <line x1="3" y1="10" x2="21" y2="10"></line>
                                    </svg>
                                    <span class="font-medium">تاریخ:</span>
                                    @if(isset($booking->booking_time))
                                        {{ \Morilog\Jalali\Jalalian::forge($booking->booking_time)->format('%A، %d %B Y') }}
                                    @else
                                        تاریخ نامشخص
                                    @endif
                                </p>

                                <p class="flex items-center">
                                    <svg class="w-4 h-4 ml-1 text-pink-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <polyline points="12 6 12 12 16 14"></polyline>
                                    </svg>
                                    <span class="font-medium">ساعت:</span>
                                    @if(isset($booking->booking_time))
                                        {{ \Carbon\Carbon::parse($booking->booking_time)->format('H:i') }}
                                    @else
                                        نامشخص
                                    @endif
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row gap-2 w-full md:w-auto">
                            @if($booking->status === 'pending')
                                <form method="POST" action="{{ route('specialist.bookings.complete', $booking) }}">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit"
                                            class="w-full sm:w-auto bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition flex items-center justify-center gap-1">
                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="20 6 9 17 4 12"></polyline>
                                        </svg>
                                        تایید و اتمام
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('specialist.bookings.cancel', $booking) }}">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit"
                                            onclick="return confirm('آیا از لغو این نوبت اطمینان دارید؟')"
                                            class="w-full sm:w-auto bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition flex items-center justify-center gap-1">
                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <line x1="18" y1="6" x2="6" y2="18"></line>
                                            <line x1="6" y1="6" x2="18" y2="18"></line>
                                        </svg>
                                        لغو نوبت
                                    </button>
                                </form>

                            @elseif($booking->status === 'confirmed')
                                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-2 rounded-lg flex items-center gap-2">
                                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                    </svg>
                                    <span class="text-sm font-medium">تایید شده</span>
                                </div>

                                <form method="POST" action="{{ route('specialist.bookings.cancel', $booking) }}">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit"
                                            onclick="return confirm('آیا از لغو این نوبت تایید شده اطمینان دارید؟')"
                                            class="w-full sm:w-auto bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition flex items-center justify-center gap-1">
                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <line x1="18" y1="6" x2="6" y2="18"></line>
                                            <line x1="6" y1="6" x2="18" y2="18"></line>
                                        </svg>
                                        لغو نوبت
                                    </button>
                                </form>

                            @elseif($booking->status === 'cancelled')
                                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-2 rounded-lg flex items-center gap-2">
                                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="15" y1="9" x2="9" y2="15"></line>
                                        <line x1="9" y1="9" x2="15" y2="15"></line>
                                    </svg>
                                    <span class="text-sm font-medium">لغو شده</span>
                                </div>

                                <form method="POST" action="{{ route('specialist.bookings.complete', $booking) }}">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit"
                                            onclick="return confirm('آیا میخواهید این نوبت لغو شده را مجدداً فعال و تایید کنید؟')"
                                            class="w-full sm:w-auto bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition flex items-center justify-center gap-1">
                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="20 6 9 17 4 12"></polyline>
                                        </svg>
                                        بازگشت و تایید
                                    </button>
                                </form>

                            @elseif($booking->status === 'completed')
                                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg flex items-center gap-2">
                                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                    </svg>
                                    <span class="text-sm font-medium">این نوبت با موفقیت انجام شده است</span>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            @if($bookings->hasPages())
                <div class="mt-6">
                    {{ $bookings->links() }}
                </div>
            @endif
        @endif
    </div>
@endsection
