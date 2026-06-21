@extends('layouts.specialist')

@section('title', 'ثبت مرخصی جدید')

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
    <div class="fade-in max-w-3xl mx-auto space-y-6">

        <div>
            <div class="flex items-center gap-3 mb-1">
                <a href="{{ route('specialist.leaves') }}" class="text-[var(--specialist-text-dim)] hover:text-[var(--specialist-plum-light)] transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                </a>
                <h1 class="text-xl font-bold text-[var(--specialist-text)] font-serif-fa">ثبت مرخصی جدید</h1>
            </div>
            <p class="text-sm text-[var(--specialist-text-dim)] mr-9">درخواست مرخصی خود را ثبت کنید</p>
        </div>

        <div class="specialist-card overflow-hidden">
            <div class="p-6 border-b flex items-center gap-3" style="border-color: var(--specialist-border);">
                <div class="rounded-lg p-3" style="background-color: rgba(216, 174, 224, 0.1);">
                    <svg class="w-6 h-6 text-[var(--specialist-plum-mid)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                </div>
                <div>
                    <h2 class="text-sm font-bold text-[var(--specialist-plum-light)] font-serif-fa">اطلاعات مرخصی</h2>
                    <p class="text-sm text-[var(--specialist-text-dim)]">تاریخ و دلیل مرخصی را مشخص کنید</p>
                </div>
            </div>

            <form action="{{ route('specialist.leaves.store') }}" method="POST" class="p-6">
                @csrf

                <div class="mb-6 rounded-lg p-4" style="background-color: rgba(216, 174, 224, 0.08); border: 1px solid var(--specialist-border);">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-[var(--specialist-plum-mid)] mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="16" x2="12" y2="12"></line>
                            <line x1="12" y1="8" x2="12.01" y2="8"></line>
                        </svg>
                        <div class="text-sm text-[var(--specialist-text-dim)]">
                            <p class="font-medium text-[var(--specialist-text)] mb-1">نکات مهم:</p>
                            <ul class="mr-4 space-y-1 list-disc">
                                <li>درخواست مرخصی شما پس از ثبت منتظر تایید مدیریت خواهد بود</li>
                                <li>در صورت تایید، نوبت‌های این بازه زمانی لغو خواهند شد</li>
                                <li>فقط درخواست‌های تایید نشده قابل حذف هستند</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="space-y-5">
                    <div>
                        <label class="block mb-2 text-xs text-[var(--specialist-plum-muted)]">
                            <span class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                                تاریخ شروع مرخصی
                                <span class="text-red-400">*</span>
                            </span>
                        </label>
                        <div class="jcal-wrapper">
                            <input type="text"
                                   id="start_date_jalali"
                                   name="start_date_jalali"
                                   required
                                   autocomplete="off"
                                   class="w-full rounded-lg p-3 cursor-pointer text-[var(--specialist-text)] placeholder-[var(--specialist-inactive)] focus:outline-none focus:ring-2 focus:ring-[var(--specialist-plum-mid)]"
                                   style="background-color: var(--specialist-bg); border: 1px solid var(--specialist-border);"
                                   placeholder="انتخاب کنید..." readonly>
                            <div class="jcal-popup" id="jcal-popup-start_date_jalali"></div>
                        </div>
                        @error('start_date_jalali')
                        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-2 text-xs text-[var(--specialist-plum-muted)]">
                            <span class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                                تاریخ پایان مرخصی
                                <span class="text-red-400">*</span>
                            </span>
                        </label>
                        <div class="jcal-wrapper">
                            <input type="text"
                                   id="end_date_jalali"
                                   name="end_date_jalali"
                                   required
                                   autocomplete="off"
                                   class="w-full rounded-lg p-3 cursor-pointer text-[var(--specialist-text)] placeholder-[var(--specialist-inactive)] focus:outline-none focus:ring-2 focus:ring-[var(--specialist-plum-mid)]"
                                   style="background-color: var(--specialist-bg); border: 1px solid var(--specialist-border);"
                                   placeholder="انتخاب کنید..." readonly>
                            <div class="jcal-popup" id="jcal-popup-end_date_jalali"></div>
                        </div>
                        @error('end_date_jalali')
                        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-2 text-xs text-[var(--specialist-plum-muted)]">
                            <span class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <polyline points="14 2 14 8 20 8"></polyline>
                                    <line x1="16" y1="13" x2="8" y2="13"></line>
                                    <line x1="16" y1="17" x2="8" y2="17"></line>
                                    <polyline points="10 9 9 9 8 9"></polyline>
                                </svg>
                                دلیل مرخصی
                                <span class="text-[var(--specialist-inactive)] text-xs">(اختیاری)</span>
                            </span>
                        </label>
                        <textarea name="reason"
                                  rows="4"
                                  class="w-full rounded-lg p-3 resize-none text-[var(--specialist-text)] placeholder-[var(--specialist-inactive)] focus:outline-none focus:ring-2 focus:ring-[var(--specialist-plum-mid)]"
                                  style="background-color: var(--specialist-bg); border: 1px solid var(--specialist-border);"
                                  placeholder="توضیحاتی درباره دلیل مرخصی خود بنویسید..."></textarea>
                        @error('reason')
                        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex gap-3 mt-8 pt-6 border-t" style="border-color: var(--specialist-border);">
                    <button type="submit"
                            class="specialist-cta flex-1 px-6 py-3 rounded-lg transition-opacity hover:opacity-90 font-bold flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        ثبت درخواست مرخصی
                    </button>
                    <a href="{{ route('specialist.leaves') }}"
                       class="px-6 py-3 rounded-lg transition flex items-center justify-center gap-2 text-[var(--specialist-text-dim)] hover:bg-white/5 hover:text-[var(--specialist-text)]"
                       style="border: 1px solid var(--specialist-border);">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                        انصراف
                    </a>
                </div>
            </form>
        </div>

        <div class="rounded-lg p-4" style="background-color: var(--specialist-surface); border: 1px solid var(--specialist-border);">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-[var(--specialist-plum-muted)] mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10"></circle>
                    <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg>
                <div class="text-sm text-[var(--specialist-text-dim)]">
                    <p class="font-medium text-[var(--specialist-text)] mb-1">نیاز به راهنمایی دارید؟</p>
                    <p>در صورت بروز هرگونه مشکل با پشتیبانی تماس بگیرید.</p>
                </div>
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
                initCalendar('start_date_jalali', 'jcal-popup-start_date_jalali');
                initCalendar('end_date_jalali', 'jcal-popup-end_date_jalali');

                document.addEventListener('click', function() {
                    document.querySelectorAll('.jcal-popup.open').forEach(p => p.classList.remove('open'));
                });
            });
        })();
    </script>
@endpush
