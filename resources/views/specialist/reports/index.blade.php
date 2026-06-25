@extends('layouts.specialist')

@section('title', 'گزارش عملکرد')

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
            background-color: var(--specialist-surface);
            border: 1px solid var(--specialist-border);
            border-radius: 10px;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.6);
            padding: 12px;
        }
        .jcal-popup.open { display: block; }
        .jcal-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
        .jcal-header button { background: none; border: none; color: var(--specialist-text); cursor: pointer; padding: 4px 8px; border-radius: 6px; }
        .jcal-header button:hover { background-color: rgba(216,174,224,0.12); }
        .jcal-title { color: var(--specialist-plum-light); font-weight: bold; font-size: 13px; }
        .jcal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px; text-align: center; }
        .jcal-weekday { font-size: 10px; color: var(--specialist-text-dim); opacity: 0.7; padding: 4px 0; }
        .jcal-day { font-size: 12px; color: var(--specialist-text); padding: 6px 0; border-radius: 6px; cursor: pointer; }
        .jcal-day:hover { background-color: rgba(216,174,224,0.15); }
        .jcal-day.jcal-empty { cursor: default; }
        .jcal-day.jcal-empty:hover { background-color: transparent; }
        .jcal-day.jcal-selected { background-color: var(--specialist-plum-mid); color: #250D2B; font-weight: bold; }
        .jcal-day.jcal-today { border: 1px solid var(--specialist-plum-mid); }
    </style>
@endpush

@section('content')
    <div class="fade-in space-y-6">

        {{-- Filters --}}
        <div class="specialist-card p-6">
            <h2 class="text-sm font-bold text-[var(--specialist-plum-light)] font-serif-fa mb-5 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                فیلتر گزارشات
            </h2>

            <form action="{{ route('specialist.reports.index') }}" method="GET" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-xs text-[var(--specialist-plum-muted)] mb-2">از تاریخ</label>
                        <div class="jcal-wrapper">
                            <input type="text" id="start_date_filter" name="start_date" value="{{ $startDate }}"
                                   class="w-full rounded-lg px-4 py-2 text-center cursor-pointer text-[var(--specialist-text)] focus:outline-none focus:ring-2 focus:ring-[var(--specialist-plum-mid)]"
                                   style="background-color: var(--specialist-bg); border: 1px solid var(--specialist-border);"
                                   dir="ltr" autocomplete="off" readonly>
                            <div class="jcal-popup" id="jcal-popup-start_date_filter"></div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs text-[var(--specialist-plum-muted)] mb-2">تا تاریخ</label>
                        <div class="jcal-wrapper">
                            <input type="text" id="end_date_filter" name="end_date" value="{{ $endDate }}"
                                   class="w-full rounded-lg px-4 py-2 text-center cursor-pointer text-[var(--specialist-text)] focus:outline-none focus:ring-2 focus:ring-[var(--specialist-plum-mid)]"
                                   style="background-color: var(--specialist-bg); border: 1px solid var(--specialist-border);"
                                   dir="ltr" autocomplete="off" readonly>
                            <div class="jcal-popup" id="jcal-popup-end_date_filter"></div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs text-[var(--specialist-plum-muted)] mb-2">نوع خدمت</label>
                        <select name="service_id" class="w-full rounded-lg px-4 py-2 text-[var(--specialist-text)] focus:outline-none focus:ring-2 focus:ring-[var(--specialist-plum-mid)]" style="background-color: var(--specialist-bg); border: 1px solid var(--specialist-border);">
                            <option value="all" {{ $serviceId == 'all' ? 'selected' : '' }}>همه خدمات</option>
                            @foreach($specialistServices as $service)
                                <option value="{{ $service->id }}" {{ $serviceId == $service->id ? 'selected' : '' }}>{{ $service->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs text-[var(--specialist-plum-muted)] mb-2">وضعیت نوبت</label>
                        <select name="status" class="w-full rounded-lg px-4 py-2 text-[var(--specialist-text)] focus:outline-none focus:ring-2 focus:ring-[var(--specialist-plum-mid)]" style="background-color: var(--specialist-bg); border: 1px solid var(--specialist-border);">
                            <option value="all" {{ $status == 'all' ? 'selected' : '' }}>همه وضعیت‌ها</option>
                            <option value="completed" {{ $status == 'completed' ? 'selected' : '' }}>انجام شده</option>
                            <option value="confirmed" {{ $status == 'confirmed' ? 'selected' : '' }}>تایید شده</option>
                            <option value="pending" {{ $status == 'pending' ? 'selected' : '' }}>در انتظار</option>
                            <option value="cancelled" {{ $status == 'cancelled' ? 'selected' : '' }}>لغو شده</option>
                        </select>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button type="submit" class="specialist-cta font-bold py-2 px-4 rounded-lg transition-opacity hover:opacity-90 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        <span>اعمال فیلتر</span>
                    </button>

                    <button type="submit" name="export" value="excel" class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-2 px-4 rounded-lg transition-colors flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>خروجی اکسل</span>
                    </button>

                    <button type="submit" name="export" value="pdf" class="bg-red-600/90 hover:bg-red-600 text-white font-bold py-2 px-4 rounded-lg transition-colors flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        <span>خروجی PDF</span>
                    </button>

                    @if(request()->has('start_date') || request()->has('end_date') || request()->get('service_id') != 'all' || request()->get('status') != 'all')
                        <a href="{{ route('specialist.reports.index') }}" class="font-medium py-2 px-4 rounded-lg transition-colors flex items-center gap-2 text-[var(--specialist-text-dim)] hover:bg-white/5 hover:text-[var(--specialist-text)]" style="border: 1px solid var(--specialist-border);">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            <span>حذف فیلتر</span>
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Summary stats --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="specialist-cta rounded-xl p-6">
                <div class="text-sm opacity-80 mb-1">درآمد حاصله (بیعانه)</div>
                <div class="text-2xl font-bold persian-number">
                    {{ number_format($totalRevenue) }}
                    <span class="text-xs opacity-70 font-normal mr-1">تومان</span>
                </div>
            </div>

            <div class="specialist-card p-6">
                <div class="text-sm text-[var(--specialist-plum-muted)] mb-1">کل نوبت‌های دریافتی</div>
                <div class="text-2xl font-bold text-sky-300 persian-number">{{ $totalBookings }}</div>
                <div class="text-[10px] text-[var(--specialist-inactive)] mt-1">شامل همه وضعیت‌ها در این بازه</div>
            </div>

            <div class="specialist-card p-6">
                <div class="text-sm text-[var(--specialist-plum-muted)] mb-1">خدمات ارائه شده</div>
                <div class="text-2xl font-bold text-emerald-300 persian-number">{{ $completedBookings }}</div>
            </div>

            <div class="specialist-card p-6">
                <div class="text-sm text-[var(--specialist-plum-muted)] mb-1">نوبت‌های لغو شده</div>
                <div class="text-2xl font-bold text-red-300 persian-number">{{ $cancelledBookings }}</div>
            </div>
        </div>

        {{-- Detail list --}}
        <div class="specialist-card overflow-hidden">
            <div class="p-5 border-b flex justify-between items-center" style="border-color: var(--specialist-border);">
                <h3 class="text-sm font-bold text-[var(--specialist-plum-light)] font-serif-fa">ریز تراکنش‌ها و نوبت‌ها</h3>
                <span class="text-xs text-[var(--specialist-inactive)] persian-number">نمایش {{ $bookings->count() }} مورد</span>
            </div>

            @forelse($bookings as $booking)
                @php
                    $statusClass = match($booking->status) {
                        'completed' => 'bg-emerald-400/10 text-emerald-300',
                        'confirmed' => 'bg-sky-400/10 text-sky-300',
                        'pending' => 'bg-amber-400/10 text-amber-300',
                        'cancelled' => 'bg-red-500/10 text-red-300',
                        default => 'bg-white/5 text-[var(--specialist-text-dim)]'
                    };
                    $statusText = match($booking->status) {
                        'completed' => 'انجام شده',
                        'confirmed' => 'تایید شده',
                        'pending' => 'در انتظار',
                        'cancelled' => 'لغو شده',
                        default => 'نامشخص'
                    };
                @endphp
                <div class="p-4 border-b flex flex-wrap items-center justify-between gap-4" style="border-color: var(--specialist-border);">
                    <div class="flex items-center gap-4 flex-1 min-w-[220px]">
                        <span class="font-mono text-xs text-[var(--specialist-inactive)] persian-number">#{{ $booking->id }}</span>
                        <div>
                            <p class="text-sm font-medium text-[var(--specialist-text)]">{{ $booking->user->name }}</p>
                            <p class="text-xs text-[var(--specialist-plum-muted)]" dir="ltr">{{ $booking->user->phone }}</p>
                        </div>
                        <span class="text-sm text-[var(--specialist-text-dim)]">{{ $booking->service?->name ?? '—' ?? 'حذف شده' }}</span>
                    </div>

                    <div class="text-sm text-[var(--specialist-text-dim)] persian-number">
                        {{ \Morilog\Jalali\Jalalian::fromCarbon($booking->booking_time)->format('Y/m/d') }}
                        <span class="text-[var(--specialist-inactive)]">{{ \Morilog\Jalali\Jalalian::fromCarbon($booking->booking_time)->format('H:i') }}</span>
                    </div>

                    <span class="font-bold text-[var(--specialist-text)] persian-number">{{ number_format($booking->prepayment_amount * (1 - ($commissionRate ?? 10) / 100)) }} تومان</span>

                    <span class="px-3 py-1 rounded-full text-xs font-medium {{ $statusClass }}">{{ $statusText }}</span>
                </div>
            @empty
                <div class="p-12 text-center text-[var(--specialist-inactive)]">
                    موردی با این مشخصات یافت نشد.
                </div>
            @endforelse

            <div class="p-4 border-t" style="border-color: var(--specialist-border);">
                {{ $bookings->links() }}
            </div>
        </div>
    </div>
@endsection

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
                            selectedValue = viewYear + '/' + String(viewMonth).padStart(2, '0') + '/' + String(d).padStart(2, '0');
                            input.value = selectedValue;
                            popup.classList.remove('open');
                            input.dispatchEvent(new Event('change'));
                        });
                    });
                }

                render();
            }

            function initCalendar(inputId, popupId) {
                const input = document.getElementById(inputId);
                const popup = document.getElementById(popupId);
                if (!input || !popup) return;

                input.addEventListener('click', function(e) {
                    e.stopPropagation();
                    document.querySelectorAll('.jcal-popup.open').forEach(p => { if (p !== popup) p.classList.remove('open'); });
                    buildCalendar(input, popup);
                    popup.classList.add('open');
                });
            }

            document.addEventListener('DOMContentLoaded', function() {
                initCalendar('start_date_filter', 'jcal-popup-start_date_filter');
                initCalendar('end_date_filter', 'jcal-popup-end_date_filter');

                document.addEventListener('click', function() {
                    document.querySelectorAll('.jcal-popup.open').forEach(p => p.classList.remove('open'));
                });
            });
        })();
    </script>
@endpush
