@extends('layouts.specialist')

@section('title', 'تاریخچه تراکنش‌ها')

@php
    $transactionTypeMap = [
        'income'           => ['label' => 'درآمد',       'class' => 'bg-emerald-400/10 text-emerald-300'],
        'withdrawal'       => ['label' => 'برداشت',       'class' => 'bg-sky-400/10 text-sky-300'],
        'cancellation_fee' => ['label' => 'جریمه لغو',     'class' => 'bg-red-500/10 text-red-300'],
        'refund'           => ['label' => 'بازگشت وجه',    'class' => 'bg-amber-400/10 text-amber-300'],
        'adjustment'       => ['label' => 'تعدیل',        'class' => 'bg-white/5 text-[var(--specialist-text-dim)]'],
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

        <div class="flex items-center justify-between">
            <h1 class="text-xl font-bold text-[var(--specialist-text)] font-serif-fa">تاریخچه تراکنش‌ها</h1>
            <a href="{{ route('specialist.wallet.index') }}"
               class="flex items-center px-4 py-2 rounded-lg text-[var(--specialist-text-dim)] hover:bg-white/5 hover:text-[var(--specialist-text)] transition"
               style="border: 1px solid var(--specialist-border);">
                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                بازگشت به کیف پول
            </a>
        </div>

        {{-- Filters --}}
        <div class="specialist-card p-5">
            <form method="GET" action="{{ route('specialist.wallet.transactions') }}">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-xs text-[var(--specialist-plum-muted)] mb-2">نوع تراکنش</label>
                        <select name="type" class="w-full rounded-lg px-4 py-2 text-[var(--specialist-text)] focus:outline-none focus:ring-2 focus:ring-[var(--specialist-plum-mid)]" style="background-color: var(--specialist-bg); border: 1px solid var(--specialist-border);">
                            <option value="">همه</option>
                            <option value="income" {{ request('type') == 'income' ? 'selected' : '' }}>درآمد</option>
                            <option value="withdrawal" {{ request('type') == 'withdrawal' ? 'selected' : '' }}>برداشت</option>
                            <option value="cancellation_fee" {{ request('type') == 'cancellation_fee' ? 'selected' : '' }}>جریمه لغو</option>
                            <option value="refund" {{ request('type') == 'refund' ? 'selected' : '' }}>بازگشت وجه</option>
                            <option value="adjustment" {{ request('type') == 'adjustment' ? 'selected' : '' }}>تعدیل</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs text-[var(--specialist-plum-muted)] mb-2">از تاریخ</label>
                        <div class="jcal-wrapper">
                            <input type="text" id="date_from" name="date_from" value="{{ request('date_from') }}"
                                   class="w-full rounded-lg px-4 py-2 cursor-pointer text-center text-[var(--specialist-text)] placeholder-[var(--specialist-inactive)] focus:outline-none focus:ring-2 focus:ring-[var(--specialist-plum-mid)]"
                                   style="background-color: var(--specialist-bg); border: 1px solid var(--specialist-border);"
                                   placeholder="1403/09/15" dir="ltr" autocomplete="off" readonly>
                            <div class="jcal-popup" id="jcal-popup-date_from"></div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs text-[var(--specialist-plum-muted)] mb-2">تا تاریخ</label>
                        <div class="jcal-wrapper">
                            <input type="text" id="date_to" name="date_to" value="{{ request('date_to') }}"
                                   class="w-full rounded-lg px-4 py-2 cursor-pointer text-center text-[var(--specialist-text)] placeholder-[var(--specialist-inactive)] focus:outline-none focus:ring-2 focus:ring-[var(--specialist-plum-mid)]"
                                   style="background-color: var(--specialist-bg); border: 1px solid var(--specialist-border);"
                                   placeholder="1403/09/20" dir="ltr" autocomplete="off" readonly>
                            <div class="jcal-popup" id="jcal-popup-date_to"></div>
                        </div>
                    </div>

                    <div class="flex items-end gap-2">
                        <button type="submit" class="specialist-cta px-5 py-2 rounded-lg font-bold transition-opacity hover:opacity-90 flex-1">
                            اعمال فیلتر
                        </button>
                        <a href="{{ route('specialist.wallet.transactions') }}" class="px-5 py-2 rounded-lg text-[var(--specialist-text-dim)] hover:bg-white/5 hover:text-[var(--specialist-text)] transition" style="border: 1px solid var(--specialist-border);">
                            حذف
                        </a>
                    </div>
                </div>
            </form>
        </div>

        {{-- Transactions list --}}
        <div class="specialist-card overflow-hidden">
            @if($transactions->isEmpty())
                <div class="text-center py-16 text-[var(--specialist-inactive)]">
                    <svg class="w-16 h-16 mx-auto mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <p>تراکنشی یافت نشد</p>
                </div>
            @else
                <div class="divide-y" style="border-color: var(--specialist-border);">
                    @foreach($transactions as $transaction)
                        @php
                            $typeInfo = $transactionTypeMap[$transaction->type] ?? ['label' => 'نامشخص', 'class' => 'bg-white/5 text-[var(--specialist-text-dim)]'];
                        @endphp
                        <div class="p-4 flex items-center justify-between gap-4 flex-wrap">
                            <div class="flex items-center gap-3">
                                <span class="px-3 py-1 rounded-full text-xs font-medium {{ $typeInfo['class'] }}">{{ $typeInfo['label'] }}</span>
                                <div>
                                    <p class="text-sm text-[var(--specialist-text)]">{{ $transaction->description }}</p>
                                    @if($transaction->booking_id)
                                        <p class="text-xs text-[var(--specialist-plum-muted)] persian-number">نوبت #{{ $transaction->booking_id }}</p>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                <span class="text-sm text-[var(--specialist-plum-muted)] persian-number">{{ verta($transaction->created_at)->format('Y/m/d H:i') }}</span>
                                <span class="persian-number font-semibold {{ $transaction->amount >= 0 ? 'text-emerald-300' : 'text-red-300' }}">
                                    {{ $transaction->formatted_amount }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="p-4 border-t" style="border-color: var(--specialist-border);">
                    {{ $transactions->links() }}
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
                initCalendar('date_from', 'jcal-popup-date_from');
                initCalendar('date_to', 'jcal-popup-date_to');

                document.addEventListener('click', function() {
                    document.querySelectorAll('.jcal-popup.open').forEach(p => p.classList.remove('open'));
                });
            });
        })();
    </script>
@endpush
