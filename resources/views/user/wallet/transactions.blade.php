@extends('layouts.app')

@section('title', 'تراکنش‌های کیف پول')

@php
    $typeBadgeMap = [
        'deposit'    => ['label' => 'واریز',       'class' => 'background-color: rgba(111,207,151,0.12); color: #6FCF97;'],
        'payment'    => ['label' => 'پرداخت',       'class' => 'background-color: rgba(224,137,137,0.12); color: #E08989;'],
        'refund'     => ['label' => 'بازگشت وجه',    'class' => 'background-color: rgba(201,162,75,0.15); color: var(--rasta-gold-light);'],
        'adjustment' => ['label' => 'تعدیل',        'class' => 'background-color: rgba(248,243,233,0.08); color: var(--rasta-cream);'],
    ];
@endphp

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
        .jcal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .jcal-header button {
            background: none;
            border: none;
            color: var(--rasta-cream);
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 6px;
        }
        .jcal-header button:hover { background-color: rgba(201,162,75,0.12); }
        .jcal-title { color: var(--rasta-gold-light); font-weight: bold; font-size: 13px; }
        .jcal-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 2px;
            text-align: center;
        }
        .jcal-weekday { font-size: 10px; color: var(--rasta-cream); opacity: 0.5; padding: 4px 0; }
        .jcal-day {
            font-size: 12px;
            color: var(--rasta-cream);
            padding: 6px 0;
            border-radius: 6px;
            cursor: pointer;
        }
        .jcal-day:hover { background-color: rgba(201,162,75,0.15); }
        .jcal-day.jcal-empty { cursor: default; }
        .jcal-day.jcal-empty:hover { background-color: transparent; }
        .jcal-day.jcal-selected {
            background-color: var(--rasta-gold);
            color: var(--rasta-dark);
            font-weight: bold;
        }
        .jcal-day.jcal-today { border: 1px solid var(--rasta-gold); }
    </style>
@endpush

@section('content')
    <div class="max-w-7xl mx-auto fade-in">
        <div class="mb-6 flex items-center justify-between flex-wrap gap-4">
            <div>
                <h1 class="text-2xl font-bold flex items-center" style="color: var(--rasta-cream);">
                    <svg class="w-7 h-7 ml-2" style="color: var(--rasta-gold);" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    تراکنش‌های کیف پول
                </h1>
                <p class="mt-2 text-sm" style="color: var(--rasta-gold-light); opacity: 0.8;">مشاهده تمام تراکنش‌های مالی</p>
            </div>
            <a href="{{ route('wallet.index') }}"
               class="inline-flex items-center px-4 py-2 rounded-lg transition hover:bg-white/5"
               style="border: 1px solid rgba(201,162,75,0.25); color: var(--rasta-cream); opacity: 0.85;">
                <svg class="w-5 h-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12" />
                </svg>
                بازگشت به کیف پول
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="rounded-xl p-5" style="background-color: var(--rasta-brown); border: 1px solid rgba(201,162,75,0.2);">
                <p class="text-sm mb-1" style="color: var(--rasta-cream); opacity: 0.6;">موجودی فعلی</p>
                <p class="text-2xl font-bold persian-number" style="color: var(--rasta-gold-light);">{{ number_format($wallet->balance) }}</p>
                <p class="text-xs mt-1" style="color: var(--rasta-cream); opacity: 0.5;">تومان</p>
            </div>

            <div class="rounded-xl p-5" style="background-color: var(--rasta-brown); border: 1px solid rgba(201,162,75,0.2);">
                <p class="text-sm mb-1" style="color: var(--rasta-cream); opacity: 0.6;">کل واریزی‌ها</p>
                <p class="text-2xl font-bold persian-number" style="color: #6FCF97;">{{ number_format($wallet->total_deposited) }}</p>
                <p class="text-xs mt-1" style="color: var(--rasta-cream); opacity: 0.5;">تومان</p>
            </div>

            <div class="rounded-xl p-5" style="background-color: var(--rasta-brown); border: 1px solid rgba(201,162,75,0.2);">
                <p class="text-sm mb-1" style="color: var(--rasta-cream); opacity: 0.6;">کل پرداختی‌ها</p>
                <p class="text-2xl font-bold persian-number" style="color: #E08989;">{{ number_format($wallet->total_spent) }}</p>
                <p class="text-xs mt-1" style="color: var(--rasta-cream); opacity: 0.5;">تومان</p>
            </div>
        </div>

        <div class="rounded-xl p-6 mb-6" style="background-color: var(--rasta-brown); border: 1px solid rgba(201,162,75,0.2);">
            <form method="GET" action="{{ route('wallet.transactions') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs mb-2" style="color: var(--rasta-gold-light); opacity: 0.8;">نوع تراکنش</label>
                    <select name="type"
                            class="w-full px-4 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--rasta-gold)]"
                            style="background-color: var(--rasta-dark); border: 1px solid rgba(201,162,75,0.25); color: var(--rasta-cream);">
                        <option value="">همه تراکنش‌ها</option>
                        <option value="deposit" {{ request('type') === 'deposit' ? 'selected' : '' }}>واریز</option>
                        <option value="payment" {{ request('type') === 'payment' ? 'selected' : '' }}>پرداخت</option>
                        <option value="refund" {{ request('type') === 'refund' ? 'selected' : '' }}>بازگشت وجه</option>
                        <option value="adjustment" {{ request('type') === 'adjustment' ? 'selected' : '' }}>تعدیل</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs mb-2" style="color: var(--rasta-gold-light); opacity: 0.8;">از تاریخ</label>
                    <div class="jcal-wrapper">
                        <input type="text" id="date_from" name="date_from" value="{{ request('date_from') }}"
                               placeholder="1403/09/15" readonly
                               class="w-full px-4 py-2 rounded-lg text-center cursor-pointer focus:outline-none focus:ring-2 focus:ring-[var(--rasta-gold)]"
                               style="background-color: var(--rasta-dark); border: 1px solid rgba(201,162,75,0.25); color: var(--rasta-cream);"
                               dir="ltr" autocomplete="off">
                        <div class="jcal-popup" id="jcal-popup-date_from"></div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs mb-2" style="color: var(--rasta-gold-light); opacity: 0.8;">تا تاریخ</label>
                    <div class="jcal-wrapper">
                        <input type="text" id="date_to" name="date_to" value="{{ request('date_to') }}"
                               placeholder="1403/09/20" readonly
                               class="w-full px-4 py-2 rounded-lg text-center cursor-pointer focus:outline-none focus:ring-2 focus:ring-[var(--rasta-gold)]"
                               style="background-color: var(--rasta-dark); border: 1px solid rgba(201,162,75,0.25); color: var(--rasta-cream);"
                               dir="ltr" autocomplete="off">
                        <div class="jcal-popup" id="jcal-popup-date_to"></div>
                    </div>
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit"
                            class="flex-1 px-4 py-2 rounded-lg font-bold transition-opacity hover:opacity-90 flex items-center justify-center"
                            style="background: linear-gradient(135deg, var(--rasta-gold-light), var(--rasta-gold)); color: var(--rasta-dark);">
                        <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        جستجو
                    </button>
                    @if(request()->hasAny(['type', 'date_from', 'date_to']))
                        <a href="{{ route('wallet.transactions') }}"
                           class="px-4 py-2 rounded-lg transition hover:bg-white/5 flex items-center justify-center"
                           style="border: 1px solid rgba(201,162,75,0.25); color: var(--rasta-cream); opacity: 0.8;">
                            حذف فیلتر
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <div class="rounded-xl overflow-hidden" style="background-color: var(--rasta-brown); border: 1px solid rgba(201,162,75,0.2);">
            <div class="p-5 border-b" style="border-color: rgba(201,162,75,0.15);">
                <h2 class="font-bold" style="color: var(--rasta-gold-light);">لیست تراکنش‌ها</h2>
            </div>

            <div class="divide-y" style="border-color: rgba(201,162,75,0.1);">
                @forelse($transactions as $transaction)
                    @php
                        $typeInfo = $typeBadgeMap[$transaction->type] ?? ['label' => $transaction->type_text, 'class' => 'background-color: rgba(248,243,233,0.08); color: var(--rasta-cream);'];
                    @endphp
                    <a href="{{ route('wallet.transactions.show', $transaction) }}"
                       class="p-5 flex items-center justify-between flex-wrap gap-4 transition hover:bg-white/5">
                        <div class="flex items-center gap-4 flex-1 min-w-[260px]">
                            <div class="w-12 h-12 rounded-full flex items-center justify-center flex-shrink-0"
                                 style="background-color: rgba(201,162,75,0.1);">
                                @if($transaction->type === 'payment')
                                    <svg class="w-6 h-6" style="color: #E08989;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                @elseif($transaction->type === 'refund')
                                    <svg class="w-6 h-6" style="color: var(--rasta-gold-light);" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                    </svg>
                                @elseif($transaction->type === 'deposit')
                                    <svg class="w-6 h-6" style="color: #6FCF97;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                @else
                                    <svg class="w-6 h-6" style="color: var(--rasta-cream); opacity: 0.7;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                @endif
                            </div>

                            <div>
                                <div class="flex items-center gap-2 mb-1 flex-wrap">
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium" style="{{ $typeInfo['class'] }}">{{ $typeInfo['label'] }}</span>
                                </div>
                                <p class="text-sm" style="color: var(--rasta-cream); opacity: 0.8;">{{ $transaction->description }}</p>
                                <div class="flex items-center gap-4 mt-1.5 text-xs flex-wrap" style="color: var(--rasta-cream); opacity: 0.55;">
                                    <span class="persian-number">{{ \Morilog\Jalali\Jalalian::forge($transaction->created_at)->format('Y/m/d - H:i') }}</span>
                                    @if($transaction->booking)
                                        <span class="persian-number">نوبت #{{ $transaction->booking_id }}</span>
                                        @if($transaction->booking->payment_reference)
                                            <span class="persian-number" dir="ltr" style="color: var(--rasta-gold-light); opacity: 0.85;">
                                                شماره پیگیری: {{ $transaction->booking->payment_reference }}
                                            </span>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="text-left">
                            <p class="text-xl font-bold persian-number" style="color: {{ $transaction->amount >= 0 ? '#6FCF97' : '#E08989' }};">
                                {{ $transaction->amount >= 0 ? '+' : '' }}{{ number_format($transaction->amount) }}
                            </p>
                            <p class="text-xs mt-0.5 persian-number" style="color: var(--rasta-cream); opacity: 0.5;">
                                موجودی: {{ number_format($transaction->balance_after) }}
                            </p>
                        </div>
                    </a>
                @empty
                    <div class="text-center py-16">
                        <svg class="w-16 h-16 mx-auto mb-4" style="color: var(--rasta-brown); opacity: 0.7;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                        </svg>
                        <p class="text-lg font-medium" style="color: var(--rasta-cream); opacity: 0.7;">هیچ تراکنشی یافت نشد</p>
                        <p class="text-sm mt-1" style="color: var(--rasta-cream); opacity: 0.5;">با فیلترهای مختلف جستجو کنید</p>
                    </div>
                @endforelse
            </div>

            @if($transactions->hasPages())
                <div class="p-4 border-t" style="border-color: rgba(201,162,75,0.15);">
                    {{ $transactions->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            // --- تبدیل میلادی به شمسی و برعکس (بدون وابستگی به کتابخانه خارجی) ---
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
                // اسفند: بررسی کبیسه با تبدیل به میلادی
                const [, , gd1] = jalaliToGregorian(jy, jm, 1);
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
                    // getDay(): 0=یکشنبه در میلادی؛ هفته شمسی از شنبه شروع می‌شود
                    const jsDay = firstDate.getDay(); // 0=Sun..6=Sat
                    const startOffset = (jsDay + 1) % 7; // 0=Sat..6=Fri

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
                initCalendar('date_from', 'jcal-popup-date_from');
                initCalendar('date_to', 'jcal-popup-date_to');

                document.addEventListener('click', function() {
                    document.querySelectorAll('.jcal-popup.open').forEach(p => p.classList.remove('open'));
                });
            });
        })();
    </script>
@endpush
