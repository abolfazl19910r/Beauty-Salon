@extends('layouts.admin')

@section('title', 'اطلاعات متخصص')

@section('content')
    <div class="max-w-4xl mx-auto">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-blue-500 text-white rounded-full flex items-center justify-center text-xl font-bold ml-3">
                    {{ substr($specialist->name, 0, 1) }}
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-800 mb-1">{{ $specialist->name }}</h1>
                    <p class="text-sm text-gray-500">متخصص زیبایی</p>
                </div>
            </div>
            <div class="mt-4 md:mt-0 flex flex-wrap gap-2">
                <a href="{{ route('admin.specialists.edit', $specialist) }}"
                   class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    ویرایش اطلاعات
                </a>
                <a href="{{ route('admin.specialists.schedules.edit', $specialist) }}"
                   class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    برنامه کاری
                </a>
                <a href="{{ route('admin.specialists.leaves.index', $specialist) }}"
                   class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    مرخصی‌ها
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-1 space-y-6">
                <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden">
                    <div class="p-4 border-b border-gray-100">
                        <h2 class="text-lg font-semibold text-gray-800">اطلاعات تماس</h2>
                    </div>
                    <div class="p-4 space-y-3">
                        <div>
                            <div class="text-sm text-gray-500 mb-1">شماره تماس</div>
                            <div class="flex items-center text-gray-800">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                                <span dir="ltr">{{ $specialist->phone }}</span>
                            </div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500 mb-1">ایمیل</div>
                            <div class="flex items-center text-gray-800">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                <span dir="ltr">{{ $specialist->email }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden">
                    <div class="p-4 border-b border-gray-100">
                        <h2 class="text-lg font-semibold text-gray-800">خدمات قابل ارائه</h2>
                    </div>
                    <div class="p-4">
                        @forelse($specialist->services as $service)
                            <div class="flex justify-between items-center py-2 border-b border-gray-100 last:border-b-0">
                                <div class="flex items-center">
                                    <span class="w-2 h-2 bg-green-500 rounded-full ml-2"></span>
                                    <span>{{ $service->name }}</span>
                                </div>
                                <span class="text-gray-600 text-sm">{{ number_format($service->price) }} تومان</span>
                            </div>
                        @empty
                            <div class="py-8 text-center text-gray-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-gray-300 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                <p>هیچ خدمتی ثبت نشده است</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden">
                    <div class="p-4 border-b border-gray-100 flex justify-between items-center">
                        <h2 class="text-lg font-semibold text-gray-800">برنامه کاری هفتگی</h2>
                        <a href="{{ route('admin.specialists.schedules.edit', $specialist) }}" class="text-blue-600 hover:text-blue-800 text-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                            </svg>
                            ویرایش
                        </a>
                    </div>
                    <div class="p-4">
                        <div class="grid grid-cols-7 gap-2">
                            @php
                                $days = [
                                    'یکشنبه',
                                    'دوشنبه',
                                    'سه‌شنبه',
                                    'چهارشنبه',
                                    'پنج‌شنبه',
                                    'جمعه',
                                    'شنبه'
                                ];
                            @endphp

                            @foreach($days as $index => $day)
                                @php
                                    $schedule = $specialist->schedules()
                                        ->where('day_of_week', $index)
                                        ->where('is_active', true)
                                        ->first();
                                @endphp
                                <div class="p-3 {{ $schedule ? 'bg-green-50 border border-green-100' : 'bg-red-50 border border-red-100' }} rounded-lg text-center hover:shadow-sm transition-all duration-200">
                                    <div class="font-semibold mb-2 text-sm">{{ $day }}</div>
                                    @if($schedule)
                                        <div class="text-xs">
                                            <div class="text-green-600 font-medium">
                                                {{ \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }}
                                                تا
                                                {{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }}
                                            </div>
                                        </div>
                                    @else
                                        <div class="text-xs text-red-500 font-medium">تعطیل</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden">
                    <div class="p-4 border-b border-gray-100 flex justify-between items-center">
                        <h2 class="text-lg font-semibold text-gray-800">مرخصی‌های فعلی</h2>
                        <a href="{{ route('admin.specialists.leaves.index', $specialist) }}" class="text-blue-600 hover:text-blue-800 text-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                            </svg>
                            مدیریت
                        </a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                            <tr class="text-sm text-gray-600 bg-gray-50">
                                <th class="px-3 py-3 text-right font-medium">تاریخ شروع</th>
                                <th class="px-3 py-3 text-right font-medium">تاریخ پایان</th>
                                <th class="px-3 py-3 text-right font-medium">دلیل</th>
                                <th class="px-3 py-3 text-right font-medium">وضعیت</th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                            @php
                                $currentLeaves = $specialist->leaves()
                                    ->where('end_date', '>=', now())
                                    ->where('status', 'approved')
                                    ->get();
                            @endphp
                            @forelse($currentLeaves as $leave)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-3 py-3 text-sm">
                                        {{ verta($leave->start_date)->format('Y/m/d') }}
                                    </td>
                                    <td class="px-3 py-3 text-sm">
                                        {{ verta($leave->end_date)->format('Y/m/d') }}
                                    </td>
                                    <td class="px-3 py-3 text-sm">
                                        {{ $leave->reason ?: 'بدون توضیحات' }}
                                    </td>
                                    <td class="px-3 py-3 text-sm">
                                        <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs font-medium">
                                            تایید شده
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-3 py-6 text-center text-gray-500">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-8 w-8 text-gray-300 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <p>هیچ مرخصی فعالی وجود ندارد</p>
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden">
                    <div class="p-4 border-b border-gray-100">
                        <h2 class="text-lg font-semibold text-gray-800">نوبت‌های امروز</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                            <tr class="text-sm text-gray-600 bg-gray-50">
                                <th class="px-3 py-3 text-right font-medium">ساعت</th>
                                <th class="px-3 py-3 text-right font-medium">مشتری</th>
                                <th class="px-3 py-3 text-right font-medium">خدمت</th>
                                <th class="px-3 py-3 text-right font-medium">وضعیت</th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                            @php
                                $todayBookings = $specialist->bookings()
                                    ->whereDate('booking_time', today())
                                    ->with(['user', 'service'])
                                    ->orderBy('booking_time')
                                    ->get();
                            @endphp
                            @forelse($todayBookings as $booking)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-3 py-3 text-sm font-medium">
                                        {{ \Carbon\Carbon::parse($booking->booking_time)->format('H:i') }}
                                    </td>
                                    <td class="px-3 py-3 text-sm">
                                        <span class="flex items-center">
                                            <span class="w-6 h-6 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-xs font-bold ml-2">
                                                {{ substr($booking->user->name, 0, 1) }}
                                            </span>
                                            {{ $booking->user->name }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-3 text-sm">{{ $booking->service->name }}</td>
                                    <td class="px-3 py-3 text-sm">
                                        @switch($booking->status)
                                            @case('pending')
                                                <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full text-xs font-medium">
                                                    در انتظار تایید
                                                </span>
                                                @break
                                            @case('confirmed')
                                                <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs font-medium">
                                                    تایید شده
                                                </span>
                                                @break
                                            @case('cancelled')
                                                <span class="bg-red-100 text-red-800 px-2 py-1 rounded-full text-xs font-medium">
                                                    لغو شده
                                                </span>
                                                @break
                                        @endswitch
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-3 py-6 text-center text-gray-500">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-8 w-8 text-gray-300 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <p>هیچ نوبتی برای امروز ثبت نشده است</p>
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
