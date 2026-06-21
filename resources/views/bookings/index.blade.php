@extends('layouts.app')

@section('title', 'نوبت‌های من')

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
            background-color: var(--rasta-brown);
            border: 1px solid rgba(201,162,75,0.25);
            border-radius: 10px;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.6);
            padding: 12px;
        }
        .jcal-popup.open { display: block; }
        .jcal-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
        .jcal-header button { background: none; border: none; color: var(--rasta-cream); cursor: pointer; padding: 4px 8px; border-radius: 6px; }
        .jcal-header button:hover { background-color: rgba(201,162,75,0.12); }
        .jcal-title { color: var(--rasta-gold-light); font-weight: bold; font-size: 13px; }
        .jcal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px; text-align: center; }
        .jcal-weekday { font-size: 10px; color: var(--rasta-cream); opacity: 0.5; padding: 4px 0; }
        .jcal-day { font-size: 12px; color: var(--rasta-cream); padding: 6px 0; border-radius: 6px; cursor: pointer; }
        .jcal-day:hover { background-color: rgba(201,162,75,0.15); }
        .jcal-day.jcal-empty { cursor: default; }
        .jcal-day.jcal-empty:hover { background-color: transparent; }
        .jcal-day.jcal-selected { background-color: var(--rasta-gold); color: var(--rasta-dark); font-weight: bold; }
        .jcal-day.jcal-today { border: 1px solid var(--rasta-gold); }
    </style>
@endpush

@section('content')
    <div class="max-w-7xl mx-auto fade-in">
        <div class="flex justify-between items-center mb-6 flex-wrap gap-3">
            <h1 class="text-2xl font-bold" style="color: var(--rasta-gold-light);">نوبت‌های من</h1>
            <a href="{{ route('bookings.create') }}"
               class="px-5 py-2 rounded-lg font-bold transition-opacity hover:opacity-90 flex items-center"
               style="background: linear-gradient(135deg, var(--rasta-gold-light), var(--rasta-gold)); color: var(--rasta-dark);">
                <svg class="w-5 h-5 ml-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                رزرو نوبت جدید
            </a>
        </div>

        <div class="rounded-xl overflow-hidden" style="background-color: var(--rasta-brown); border: 1px solid rgba(201,162,75,0.2);">
            <div class="p-4 border-b" style="border-color: rgba(201,162,75,0.15);">
                <form action="{{ route('bookings.index') }}" method="GET" class="flex flex-wrap gap-4 items-end">
                    <div>
                        <label class="block text-xs mb-1" style="color: var(--rasta-gold-light); opacity: 0.8;">وضعیت</label>
                        <select name="status"
                                class="px-3 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--rasta-gold)]"
                                style="background-color: var(--rasta-dark); border: 1px solid rgba(201,162,75,0.25); color: var(--rasta-cream);">
                            <option value="">همه وضعیت‌ها</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>در انتظار تایید</option>
                            <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>تایید شده</option>
                            <option value="pending_payment" {{ request('status') == 'pending_payment' ? 'selected' : '' }}>در انتظار پرداخت</option>
                            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>لغو شده</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs mb-1" style="color: var(--rasta-gold-light); opacity: 0.8;">تاریخ</label>
                        <div class="jcal-wrapper">
                            <input type="text" id="date_filter" name="date" value="{{ request('date') }}"
                                   placeholder="1403/09/15" readonly
                                   class="px-3 py-2 rounded-lg cursor-pointer text-center focus:outline-none focus:ring-2 focus:ring-[var(--rasta-gold)]"
                                   style="background-color: var(--rasta-dark); border: 1px solid rgba(201,162,75,0.25); color: var(--rasta-cream); width: 140px;"
                                   dir="ltr" autocomplete="off">
                            <div class="jcal-popup" id="jcal-popup-date_filter"></div>
                        </div>
                    </div>

                    <button type="submit"
                            class="px-4 py-2 rounded-lg font-bold transition-opacity hover:opacity-90 flex items-center"
                            style="background: linear-gradient(135deg, var(--rasta-gold-light), var(--rasta-gold)); color: var(--rasta-dark);">
                        <svg class="w-5 h-5 ml-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                        </svg>
                        فیلتر
                    </button>

                    @if(request()->hasAny(['status', 'date']))
                        <a href="{{ route('bookings.index') }}"
                           class="px-4 py-2 rounded-lg transition hover:bg-white/5"
                           style="border: 1px solid rgba(201,162,75,0.25); color: var(--rasta-cream); opacity: 0.8;">
                            حذف فیلترها
                        </a>
                    @endif
                </form>
            </div>

            @if($bookings->isEmpty())
                <div class="p-12 text-center">
                    <svg class="w-16 h-16 mx-auto mb-4" style="color: var(--rasta-brown); opacity: 0.7;" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <p style="color: var(--rasta-cream); opacity: 0.6;">نوبتی یافت نشد.</p>
                    <a href="{{ route('bookings.create') }}" class="mt-4 inline-block transition hover:opacity-80" style="color: var(--rasta-gold);">
                        رزرو نوبت جدید
                    </a>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                        <tr style="background-color: var(--rasta-dark);">
                            <th class="px-6 py-3 text-right" style="color: var(--rasta-gold-light);">خدمت</th>
                            <th class="px-6 py-3 text-right" style="color: var(--rasta-gold-light);">متخصص</th>
                            <th class="px-6 py-3 text-right" style="color: var(--rasta-gold-light);">تاریخ و ساعت</th>
                            <th class="px-6 py-3 text-right" style="color: var(--rasta-gold-light);">وضعیت پرداخت</th>
                            <th class="px-6 py-3 text-right" style="color: var(--rasta-gold-light);">وضعیت</th>
                            <th class="px-6 py-3 text-right" style="color: var(--rasta-gold-light);">عملیات</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y" style="border-color: rgba(201,162,75,0.1);">
                        @foreach($bookings as $booking)
                            <tr class="transition hover:bg-white/5">
                                <td class="px-6 py-4" style="color: var(--rasta-cream);">{{ $booking->service->name }}</td>
                                <td class="px-6 py-4" style="color: var(--rasta-cream);">{{ $booking->specialist->name }}</td>
                                <td class="px-6 py-4 persian-number" dir="ltr" style="color: var(--rasta-cream);">
                                    {{ verta($booking->booking_time)->format('Y/m/d H:i') }}
                                </td>
                                <td class="px-6 py-4">
                                    @if($booking->payment_status == 'paid')
                                        <span class="px-2 py-1 rounded-full text-xs inline-flex items-center" style="background-color: rgba(111,207,151,0.12); color: #6FCF97;">
                                            <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            پرداخت شده
                                        </span>
                                    @else
                                        <span class="px-2 py-1 rounded-full text-xs inline-flex items-center" style="background-color: rgba(224,137,137,0.12); color: #E08989;">
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
                                            <span class="px-2 py-1 rounded-full text-xs inline-flex items-center" style="background-color: rgba(251,191,36,0.12); color: #FBBF24;">
                                                <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                در انتظار تایید
                                            </span>
                                            @break
                                        @case('confirmed')
                                            <span class="px-2 py-1 rounded-full text-xs inline-flex items-center" style="background-color: rgba(111,207,151,0.12); color: #6FCF97;">
                                                <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                تایید شده
                                            </span>
                                            @break
                                        @case('pending_payment')
                                            <span class="px-2 py-1 rounded-full text-xs inline-flex items-center" style="background-color: rgba(201,162,75,0.15); color: var(--rasta-gold-light);">
                                                <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                                </svg>
                                                در انتظار پرداخت
                                            </span>
                                            @break
                                        @case('cancelled')
                                            <span class="px-2 py-1 rounded-full text-xs inline-flex items-center" style="background-color: rgba(224,137,137,0.12); color: #E08989;">
                                                <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                لغو شده
                                            </span>
                                            @break
                                    @endswitch
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <a href="{{ route('bookings.show', $booking) }}"
                                           class="transition hover:opacity-70" style="color: var(--rasta-gold);" title="مشاهده جزئیات">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                        </a>

                                        @if($booking->payment_status == 'unpaid' && in_array($booking->status, ['pending_payment', 'confirmed']))
                                            <a href="{{ route('payment.show', $booking) }}"
                                               class="transition hover:opacity-70" style="color: #6FCF97;" title="پرداخت">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                                </svg>
                                            </a>
                                        @endif

                                        @if($booking->canBeRescheduled())
                                            <a href="{{ route('bookings.reschedule', $booking) }}"
                                               class="transition hover:opacity-70" style="color: var(--rasta-gold-light);" title="تغییر زمان">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                            </a>
                                        @endif

                                        @if(in_array($booking->status, ['pending', 'confirmed', 'pending_payment']) && $booking->booking_time > now()->addHours(24))
                                            <form action="{{ route('bookings.cancel', $booking) }}" method="POST" class="inline">
                                                @csrf
                                                @method('PUT')
                                                <button type="submit" class="transition hover:opacity-70" style="color: #E08989;" title="لغو نوبت"
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
                        });
                    });
                }

                render();
            }

            document.addEventListener('DOMContentLoaded', function() {
                const input = document.getElementById('date_filter');
                const popup = document.getElementById('jcal-popup-date_filter');
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
