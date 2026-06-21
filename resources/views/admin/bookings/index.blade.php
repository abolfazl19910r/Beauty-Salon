@extends('layouts.admin')

@section('title', 'مدیریت نوبت‌ها')

@section('content')
    <div class="container px-6 mx-auto">
        <div class="mb-8">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold flex items-center">
                    <svg class="w-6 h-6 ml-2 text-blue-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    مدیریت نوبت‌ها
                </h1>

                @permission('create-bookings')
                <a href="{{ route('admin.bookings.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                    <svg class="w-5 h-5 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    افزودن نوبت جدید
                </a>
                @endpermission
            </div>

            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <div class="flex flex-col md:flex-row gap-4 justify-between">
                    <div>
                        <h2 class="text-lg font-medium mb-4">فیلتر نوبت‌ها</h2>
                        <div class="flex flex-wrap gap-4">
                            <select class="border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                    onchange="window.location.href=this.value">
                                <option value="{{ route('admin.bookings.index', request('date') ? ['date' => request('date')] : []) }}"
                                    {{ request('status') == '' ? 'selected' : '' }}>
                                    همه وضعیت‌ها
                                </option>
                                <option value="{{ route('admin.bookings.index', array_filter(['status' => 'pending', 'date' => request('date')])) }}"
                                    {{ request('status') == 'pending' ? 'selected' : '' }}>
                                    در انتظار تایید
                                </option>
                                <option value="{{ route('admin.bookings.index', array_filter(['status' => 'confirmed', 'date' => request('date')])) }}"
                                    {{ request('status') == 'confirmed' ? 'selected' : '' }}>
                                    تایید شده
                                </option>
                                <option value="{{ route('admin.bookings.index', array_filter(['status' => 'cancelled', 'date' => request('date')])) }}"
                                    {{ request('status') == 'cancelled' ? 'selected' : '' }}>
                                    لغو شده
                                </option>
                            </select>

                            <div class="relative jcal-wrapper">
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                    <svg class="w-5 h-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                        <line x1="16" y1="2" x2="16" y2="6"></line>
                                        <line x1="8" y1="2" x2="8" y2="6"></line>
                                        <line x1="3" y1="10" x2="21" y2="10"></line>
                                    </svg>
                                </div>
                                <input type="text"
                                       id="date-picker"
                                       class="border border-gray-300 rounded-lg px-4 py-2 pr-10 cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                       value="{{ request('date') ? verta(request('date'))->format('Y/m/d') : '' }}"
                                       placeholder="انتخاب تاریخ"
                                       readonly>
                                <div class="jcal-popup" id="jcal-popup-date-picker"></div>
                            </div>

                            @if($hasDateFilter || request('status'))
                                <a href="{{ route('admin.bookings.index') }}"
                                   class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50 transition-colors">
                                    حذف فیلترها
                                </a>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center">
                        <div>
                            <p class="text-xs text-gray-400 mb-2 text-center">
                                {{ $hasDateFilter ? 'آمار تاریخ انتخاب‌شده' : 'آمار کل (همه تاریخ‌ها)' }}
                            </p>
                            <div class="grid grid-cols-3 gap-3">
                                <div class="bg-blue-50 p-3 rounded-lg text-center hover:shadow-md transition-all duration-300">
                                    <span class="text-xs text-blue-500">کل نوبت‌ها</span>
                                    <div class="text-xl font-bold text-blue-700 persian-number">{{ $totalBookings ?? 0 }}</div>
                                </div>
                                <div class="bg-green-50 p-3 rounded-lg text-center hover:shadow-md transition-all duration-300">
                                    <span class="text-xs text-green-500">تایید شده</span>
                                    <div class="text-xl font-bold text-green-700 persian-number">{{ $confirmedBookings ?? 0 }}</div>
                                </div>
                                <div class="bg-red-50 p-3 rounded-lg text-center hover:shadow-md transition-all duration-300">
                                    <span class="text-xs text-red-500">لغو شده</span>
                                    <div class="text-xl font-bold text-red-700 persian-number">{{ $cancelledBookings ?? 0 }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-lg hover:shadow-xl transition-shadow duration-300 overflow-x-auto">
            <table class="w-full text-sm" dir="rtl">
                <thead>
                <tr class="bg-gradient-to-r from-blue-50 to-blue-100 text-blue-800">
                    <th class="px-6 py-3 text-right">شماره</th>
                    <th class="px-6 py-3 text-right">مشتری</th>
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
                        <td class="px-6 py-4 text-right font-medium">{{ $booking->id }}</td>
                        <td class="px-6 py-4 text-right">
                            @if(isset($booking->user))
                                <div class="flex items-center">
                                    <div class="w-8 h-8 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center ml-2">
                                        {{ mb_substr($booking->user->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <span class="block">{{ $booking->user->name }}</span>
                                        <span class="text-xs text-gray-500" dir="ltr">{{ $booking->user->phone }}</span>
                                    </div>
                                </div>
                            @else
                                <span class="text-gray-400">کاربر نامشخص</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">{{ $booking->service->name }}</td>
                        <td class="px-6 py-4 text-right">{{ $booking->specialist?->name ?? 'متخصص نامشخص' }}</td>
                        <td class="px-6 py-4 text-right persian-number">
                            <div class="flex flex-col">
                                <span>{{ verta($booking->booking_time)->format('Y/m/d') }}</span>
                                <span class="text-xs text-gray-500">{{ verta($booking->booking_time)->format('H:i') }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            @if($booking->payment_status == 'paid')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <svg class="w-3 h-3 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                    </svg>
                                    پرداخت شده
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    <svg class="w-3 h-3 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="12" y1="8" x2="12" y2="12"></line>
                                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                    </svg>
                                    پرداخت نشده
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            @switch($booking->status)
                                @case('pending')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        <svg class="w-3 h-3 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <polyline points="12 6 12 12 16 14"></polyline>
                                        </svg>
                                        در انتظار تایید
                                    </span>
                                    @break
                                @case('confirmed')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <svg class="w-3 h-3 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                        </svg>
                                        تایید شده
                                    </span>
                                    @break
                                @case('cancelled')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        <svg class="w-3 h-3 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <line x1="15" y1="9" x2="9" y2="15"></line>
                                            <line x1="9" y1="9" x2="15" y2="15"></line>
                                        </svg>
                                        لغو شده
                                    </span>
                                    @break
                            @endswitch
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center gap-2">
                                <a href="{{ route('admin.bookings.show', $booking) }}"
                                   class="group inline-flex items-center text-blue-600 hover:text-blue-800 transition-colors">
                                    <svg class="w-5 h-5 group-hover:scale-110 transition-transform" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </a>

                                @permission('edit-bookings')
                                <a href="{{ route('admin.bookings.edit', $booking) }}"
                                   class="group inline-flex items-center text-purple-600 hover:text-purple-800 transition-colors">
                                    <svg class="w-5 h-5 group-hover:scale-110 transition-transform" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                </a>
                                @endpermission

                                @permission('approve-bookings')
                                @if($booking->status == 'pending')
                                    <form action="{{ route('admin.bookings.update', $booking) }}"
                                          method="POST" class="inline">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="status" value="confirmed">
                                        <button type="submit" class="group inline-flex items-center text-green-600 hover:text-green-800 transition-colors">
                                            <svg class="w-5 h-5 group-hover:scale-110 transition-transform" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="20 6 9 17 4 12"></polyline>
                                            </svg>
                                        </button>
                                    </form>
                                @endif
                                @endpermission

                                @permission('delete-bookings')
                                @if($booking->status != 'cancelled')
                                    <form action="{{ route('admin.bookings.update', $booking) }}"
                                          method="POST" class="inline">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="status" value="cancelled">
                                        <button type="submit" class="group inline-flex items-center text-red-600 hover:text-red-800 transition-colors"
                                                data-confirm-delete data-confirm-message="آیا از لغو این نوبت اطمینان دارید؟">
                                            <svg class="w-5 h-5 group-hover:scale-110 transition-transform" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <circle cx="12" cy="12" r="10"></circle>
                                                <line x1="15" y1="9" x2="9" y2="15"></line>
                                                <line x1="9" y1="9" x2="15" y2="15"></line>
                                            </svg>
                                        </button>
                                    </form>
                                @endif
                                @endpermission
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $bookings->links() }}
        </div>
    </div>
    @push('styles')
        <style>
            .jcal-wrapper { position: relative; }
            .jcal-popup {
                display: none;
                position: absolute;
                top: calc(100% + 6px);
                right: 0;
                z-index: 9999;
                width: 280px;
                background-color: #ffffff;
                border: 1px solid #e5e7eb;
                border-radius: 10px;
                box-shadow: 0 10px 25px -5px rgba(0,0,0,0.2);
                padding: 12px;
            }
            .jcal-popup.open { display: block; }
            .jcal-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
            .jcal-header button { background: none; border: none; color: #374151; cursor: pointer; padding: 4px 8px; border-radius: 6px; }
            .jcal-header button:hover { background-color: #f3f4f6; }
            .jcal-title { color: #1f2937; font-weight: bold; font-size: 13px; }
            .jcal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px; text-align: center; }
            .jcal-weekday { font-size: 10px; color: #9ca3af; padding: 4px 0; }
            .jcal-day { font-size: 12px; color: #374151; padding: 6px 0; border-radius: 6px; cursor: pointer; }
            .jcal-day:hover { background-color: #eff6ff; }
            .jcal-day.jcal-empty { cursor: default; }
            .jcal-day.jcal-empty:hover { background-color: transparent; }
            .jcal-day.jcal-selected { background-color: #3b82f6; color: #ffffff; font-weight: bold; }
            .jcal-day.jcal-today { border: 1px solid #3b82f6; }
        </style>
    @endpush
    @push('scripts')
        <script>
            (function() {
                function div(a, b) { return Math.trunc(a / b); }

                function gregorianToJalali(gy, gm, gd) {
                    const g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
                    let jy;
                    if (gy > 1600) { jy = 979; gy -= 1600; } else { jy = 0; gy -= 621; }
                    const gy2 = (gm > 2) ? (gy + 1) : gy;
                    let days = (365 * gy) + div(gy2 + 3, 4) - div(gy2 + 99, 100) + div(gy2 + 399, 400) - 80 + gd + g_d_m[gm - 1];
                    jy += 33 * div(days, 12053);
                    days %= 12053;
                    jy += 4 * div(days, 1461);
                    days %= 1461;
                    if (days > 365) { jy += div(days - 1, 365); days = (days - 1) % 365; }
                    const jm = (days < 186) ? 1 + div(days, 31) : 7 + div(days - 186, 30);
                    const jd = 1 + ((days < 186) ? (days % 31) : ((days - 186) % 30));
                    return [jy, jm, jd];
                }

                function jalaliToGregorian(jy, jm, jd) {
                    let gy;
                    if (jy > 979) { gy = 1600; jy -= 979; } else { gy = 621; }
                    let days = (365 * jy) + (div(jy, 33) * 8) + div((jy % 33) + 3, 4) + 78 + jd + ((jm < 7) ? (jm - 1) * 31 : ((jm - 7) * 30) + 186);
                    gy += 400 * div(days, 146097);
                    days %= 146097;
                    if (days > 36524) {
                        gy += 100 * div(--days, 36524);
                        days %= 36524;
                        if (days >= 365) days++;
                    }
                    gy += 4 * div(days, 1461);
                    days %= 1461;
                    if (days > 365) { gy += div(days - 1, 365); days = (days - 1) % 365; }
                    const gd = days + 1;
                    const isLeap = (gy % 4 === 0 && gy % 100 !== 0) || (gy % 400 === 0);
                    const sal_a = [0, 31, isLeap ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
                    let gm = 0, remaining = gd;
                    for (gm = 1; gm <= 12; gm++) {
                        if (remaining <= sal_a[gm]) break;
                        remaining -= sal_a[gm];
                    }
                    return [gy, gm, remaining];
                }

                const jMonths = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
                const jWeekdays = ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'];

                function jalaliMonthLength(jy, jm) {
                    if (jm <= 6) return 31;
                    if (jm <= 11) return 30;
                    const g1 = jalaliToGregorian(jy, jm, 29);
                    const g2 = jalaliToGregorian(jy + 1, 1, 1);
                    const d1 = new Date(g1[0], g1[1] - 1, g1[2]);
                    const d2 = new Date(g2[0], g2[1] - 1, g2[2]);
                    const diffDays = Math.round((d2 - d1) / 86400000);
                    return 28 + diffDays;
                }

                function gregorianStringFromJalali(jy, jm, jd) {
                    const [gy, gm, gd] = jalaliToGregorian(jy, jm, jd);
                    return gy + '-' + String(gm).padStart(2, '0') + '-' + String(gd).padStart(2, '0');
                }

                function buildCalendar(input, popup) {
                    const today = new Date();
                    const [tjy, tjm, tjd] = gregorianToJalali(today.getFullYear(), today.getMonth() + 1, today.getDate());

                    let viewYear = tjy, viewMonth = tjm;
                    let selectedValue = input.value || '';

                    if (selectedValue.match(/^\d{4}\/\d{1,2}\/\d{1,2}$/)) {
                        const parts = selectedValue.split('/').map(Number);
                        viewYear = parts[0];
                        viewMonth = parts[1];
                    }

                    function render() {
                        const firstDayGregorian = jalaliToGregorian(viewYear, viewMonth, 1);
                        const firstDate = new Date(firstDayGregorian[0], firstDayGregorian[1] - 1, firstDayGregorian[2]);
                        const jsDay = firstDate.getDay();
                        const startOffset = (jsDay + 1) % 7;

                        const monthLength = jalaliMonthLength(viewYear, viewMonth);

                        let html = '<div class="jcal-header">';
                        html += '<button type="button" data-nav="prev">&#9658;</button>';
                        html += '<span class="jcal-title persian-number">' + jMonths[viewMonth - 1] + ' ' + viewYear + '</span>';
                        html += '<button type="button" data-nav="next">&#9664;</button>';
                        html += '</div>';
                        html += '<div class="jcal-grid">';
                        jWeekdays.forEach(w => { html += '<div class="jcal-weekday">' + w + '</div>'; });

                        for (let i = 0; i < startOffset; i++) {
                            html += '<div class="jcal-day jcal-empty"></div>';
                        }
                        for (let d = 1; d <= monthLength; d++) {
                            const isToday = (viewYear === tjy && viewMonth === tjm && d === tjd);
                            const dayValue = viewYear + '/' + String(viewMonth).padStart(2, '0') + '/' + String(d).padStart(2, '0');
                            const isSelected = (dayValue === selectedValue);
                            html += '<div class="jcal-day persian-number' + (isToday ? ' jcal-today' : '') + (isSelected ? ' jcal-selected' : '') + '" data-day="' + d + '">' + d + '</div>';
                        }
                        html += '</div>';
                        popup.innerHTML = html;

                        popup.querySelector('[data-nav="prev"]').addEventListener('click', function(e) {
                            e.stopPropagation();
                            viewMonth--;
                            if (viewMonth < 1) { viewMonth = 12; viewYear--; }
                            render();
                        });
                        popup.querySelector('[data-nav="next"]').addEventListener('click', function(e) {
                            e.stopPropagation();
                            viewMonth++;
                            if (viewMonth > 12) { viewMonth = 1; viewYear++; }
                            render();
                        });
                        popup.querySelectorAll('.jcal-day[data-day]').forEach(function(el) {
                            el.addEventListener('click', function(e) {
                                e.stopPropagation();
                                const d = parseInt(this.dataset.day, 10);
                                const gDate = gregorianStringFromJalali(viewYear, viewMonth, d);
                                window.location.href = '{{ route("admin.bookings.index") }}?date=' + gDate
                                    + '{{ request("status") ? "&status=" . request("status") : "" }}';
                            });
                        });
                    }

                    render();
                }

                document.addEventListener('DOMContentLoaded', function() {
                    const input = document.getElementById('date-picker');
                    const popup = document.getElementById('jcal-popup-date-picker');
                    if (input && popup) {
                        input.addEventListener('click', function(e) {
                            e.stopPropagation();
                            buildCalendar(input, popup);
                            popup.classList.add('open');
                        });
                    }
                    document.addEventListener('click', function() {
                        document.querySelectorAll('.jcal-popup.open').forEach(p => p.classList.remove('open'));
                    });
                });
            })();
        </script>
    @endpush
@endsection
