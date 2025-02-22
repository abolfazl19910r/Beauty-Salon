@extends('layouts.admin')

@section('title', 'اطلاعات متخصص')

@section('content')
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold">اطلاعات {{ $specialist->name }}</h1>
                <div class="space-x-2 space-x-reverse">
                    <a href="{{ route('admin.specialists.edit', $specialist) }}"
                       class="bg-blue-500 text-white px-4 py-2 rounded">
                        ویرایش اطلاعات
                    </a>
                    <a href="{{ route('admin.specialists.schedules.edit', $specialist) }}"
                       class="bg-green-500 text-white px-4 py-2 rounded">
                        مدیریت برنامه کاری
                    </a>
                    <a href="{{ route('admin.specialists.leaves.index', $specialist) }}"
                       class="bg-purple-500 text-white px-4 py-2 rounded">
                        مدیریت مرخصی‌ها
                    </a>
                </div>
            </div>

            <!-- اطلاعات پایه -->
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div class="p-4 bg-gray-50 rounded">
                    <div class="font-bold mb-2">شماره تماس</div>
                    <div dir="ltr">{{ $specialist->phone }}</div>
                </div>
                <div class="p-4 bg-gray-50 rounded">
                    <div class="font-bold mb-2">ایمیل</div>
                    <div>{{ $specialist->email }}</div>
                </div>
            </div>

            <!-- لیست خدمات -->
            <div class="mb-8">
                <h2 class="text-xl font-bold mb-4">خدمات</h2>
                <div class="grid grid-cols-2 gap-4">
                    @forelse($specialist->services as $service)
                        <div class="p-4 bg-gray-50 rounded flex justify-between items-center">
                            <span>{{ $service->name }}</span>
                            <span class="text-gray-600">{{ number_format($service->price) }} تومان</span>
                        </div>
                    @empty
                        <div class="col-span-2 p-4 bg-gray-50 rounded text-center text-gray-500">
                            هیچ خدمتی ثبت نشده است
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- برنامه کاری -->
            <div class="mb-8">
                <h2 class="text-xl font-bold mb-4">برنامه کاری</h2>
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
                        <div class="p-3 bg-gray-50 rounded text-center">
                            <div class="font-bold mb-2">{{ $day }}</div>
                            @if($schedule)
                                <div class="text-sm">
                                    <div class="text-green-600">
                                        {{ \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }}
                                        تا
                                        {{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }}
                                    </div>
                                </div>
                            @else
                                <div class="text-sm text-red-500">تعطیل</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- مرخصی‌های فعلی -->
            <div class="mb-8">
                <h2 class="text-xl font-bold mb-4">مرخصی‌های فعلی</h2>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                        <tr class="bg-gray-50">
                            <th class="px-4 py-2 text-right">تاریخ شروع</th>
                            <th class="px-4 py-2 text-right">تاریخ پایان</th>
                            <th class="px-4 py-2 text-right">دلیل</th>
                            <th class="px-4 py-2 text-right">وضعیت</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y">
                        @php
                            $currentLeaves = $specialist->leaves()
                                ->where('end_date', '>=', now())
                                ->where('status', 'approved')
                                ->get();
                        @endphp
                        @forelse($currentLeaves as $leave)
                            <tr>
                                <td class="px-4 py-2">
                                    {{ verta($leave->start_date)->format('Y/m/d') }}
                                </td>
                                <td class="px-4 py-2">
                                    {{ verta($leave->end_date)->format('Y/m/d') }}
                                </td>
                                <td class="px-4 py-2">{{ $leave->reason }}</td>
                                <td class="px-4 py-2">
                                        <span class="bg-green-100 text-green-800 px-2 py-1 rounded">
                                            تایید شده
                                        </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-4 text-center text-gray-500">
                                    هیچ مرخصی فعالی وجود ندارد
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- نوبت‌های امروز -->
            <div>
                <h2 class="text-xl font-bold mb-4">نوبت‌های امروز</h2>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                        <tr class="bg-gray-50">
                            <th class="px-4 py-2 text-right">ساعت</th>
                            <th class="px-4 py-2 text-right">مشتری</th>
                            <th class="px-4 py-2 text-right">خدمت</th>
                            <th class="px-4 py-2 text-right">وضعیت</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y">
                        @php
                            $todayBookings = $specialist->bookings()
                                ->whereDate('booking_time', today())
                                ->with(['user', 'service'])
                                ->orderBy('booking_time')
                                ->get();
                        @endphp
                        @forelse($todayBookings as $booking)
                            <tr>
                                <td class="px-4 py-2">
                                    {{ \Carbon\Carbon::parse($booking->booking_time)->format('H:i') }}
                                </td>
                                <td class="px-4 py-2">{{ $booking->user->name }}</td>
                                <td class="px-4 py-2">{{ $booking->service->name }}</td>
                                <td class="px-4 py-2">
                                    @switch($booking->status)
                                        @case('pending')
                                            <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded">
                                                    در انتظار تایید
                                                </span>
                                            @break
                                        @case('confirmed')
                                            <span class="bg-green-100 text-green-800 px-2 py-1 rounded">
                                                    تایید شده
                                                </span>
                                            @break
                                        @case('cancelled')
                                            <span class="bg-red-100 text-red-800 px-2 py-1 rounded">
                                                    لغو شده
                                                </span>
                                            @break
                                    @endswitch
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-4 text-center text-gray-500">
                                    هیچ نوبتی برای امروز ثبت نشده است
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
