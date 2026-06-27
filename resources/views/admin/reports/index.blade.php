@extends('layouts.admin')

@section('title', 'گزارشات مدیریتی')

@section('content')
    <div class="container px-6 mx-auto">

        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold flex items-center" style="color: var(--admin-text);">
                <svg class="w-6 h-6 ml-2" style="color: var(--admin-accent);" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21.21 15.89A10 10 0 1 1 8 2.83"></path>
                    <path d="M22 12A10 10 0 0 0 12 2v10z"></path>
                </svg>
                گزارشات مدیریتی
            </h1>

            <div class="flex gap-2">
                @permission('export-reports')
                <a href="{{ route('admin.reports.export', ['format' => 'pdf', 'report_type' => 'daily']) }}"
                   id="pdf-export-link"
                   class="inline-flex items-center px-4 py-2 text-white rounded-lg transition-colors"
                   style="background: #dc2626;">
                    <svg class="w-4 h-4 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="12" y1="18" x2="12" y2="12"></line>
                        <line x1="9" y1="15" x2="15" y2="15"></line>
                    </svg>
                    خروجی PDF
                </a>

                <a href="{{ route('admin.reports.export', ['format' => 'excel', 'report_type' => 'daily']) }}"
                   id="excel-export-link"
                   class="inline-flex items-center px-4 py-2 text-white rounded-lg transition-colors"
                   style="background: #16a34a;">
                    <svg class="w-4 h-4 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                    </svg>
                    خروجی Excel
                </a>
                @endpermission

                <a href="{{ route('admin.dashboard') }}"
                   class="inline-flex items-center px-4 py-2 text-sm rounded-lg border transition-colors"
                   style="color: var(--admin-text-dim); background: var(--admin-surface); border-color: var(--admin-border);">
                    <svg class="w-4 h-4 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 12H5"></path>
                        <path d="M12 19l-7-7 7-7"></path>
                    </svg>
                    بازگشت
                </a>
            </div>
        </div>

        {{-- Report Type Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="report-type-card rounded-xl p-5 cursor-pointer transition-all duration-200"
                 id="daily-report-card"
                 onclick="selectReportType('daily')"
                 style="background: var(--admin-surface); border: 2px solid var(--admin-border);">
                <div class="flex items-center">
                    <div class="p-3 rounded-lg ml-4" style="background: var(--admin-accent-light);">
                        <svg class="w-6 h-6" style="color: var(--admin-accent);" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs mb-1" style="color: var(--admin-text-dim);">گزارش روزانه</p>
                        <h2 class="font-bold" style="color: var(--admin-text);">نمای روزانه</h2>
                    </div>
                </div>
            </div>

            <div class="report-type-card rounded-xl p-5 cursor-pointer transition-all duration-200"
                 id="weekly-report-card"
                 onclick="selectReportType('weekly')"
                 style="background: var(--admin-surface); border: 2px solid var(--admin-border);">
                <div class="flex items-center">
                    <div class="p-3 rounded-lg ml-4" style="background: #f0fdf4;">
                        <svg class="w-6 h-6" style="color: #16a34a;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                            <polyline points="2 17 12 22 22 17"></polyline>
                            <polyline points="2 12 12 17 22 12"></polyline>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs mb-1" style="color: var(--admin-text-dim);">گزارش هفتگی</p>
                        <h2 class="font-bold" style="color: var(--admin-text);">نمای هفتگی</h2>
                    </div>
                </div>
            </div>

            <div class="report-type-card rounded-xl p-5 cursor-pointer transition-all duration-200"
                 id="monthly-report-card"
                 onclick="selectReportType('monthly')"
                 style="background: var(--admin-surface); border: 2px solid var(--admin-border);">
                <div class="flex items-center">
                    <div class="p-3 rounded-lg ml-4" style="background: #faf5ff;">
                        <svg class="w-6 h-6" style="color: #7c3aed;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs mb-1" style="color: var(--admin-text-dim);">گزارش ماهانه</p>
                        <h2 class="font-bold" style="color: var(--admin-text);">نمای ماهانه</h2>
                    </div>
                </div>
            </div>

            <div class="report-type-card rounded-xl p-5 cursor-pointer transition-all duration-200"
                 id="custom-report-card"
                 onclick="selectReportType('custom')"
                 style="background: var(--admin-surface); border: 2px solid var(--admin-border);">
                <div class="flex items-center">
                    <div class="p-3 rounded-lg ml-4" style="background: #fffbeb;">
                        <svg class="w-6 h-6" style="color: #d97706;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs mb-1" style="color: var(--admin-text-dim);">گزارش سفارشی</p>
                        <h2 class="font-bold" style="color: var(--admin-text);">بازه دلخواه</h2>
                    </div>
                </div>
            </div>
        </div>

        {{-- Date Picker Section (custom) --}}
        <div id="date-picker-section" class="rounded-xl p-6 mb-6 hidden" style="background: var(--admin-surface); border: 1px solid var(--admin-border);">
            <h2 class="text-base font-semibold mb-4" style="color: var(--admin-text);">انتخاب بازه زمانی</h2>
            <div class="flex flex-col md:flex-row gap-4 items-end">
                <div class="flex-1">
                    <label for="start-date" class="block mb-1 text-sm font-medium" style="color: var(--admin-text-dim);">از تاریخ</label>
                    <div class="jcal-wrapper" id="start-date-wrapper">
                        <div class="relative">
                            <input type="text" id="start-date" placeholder="انتخاب تاریخ" readonly
                                   class="w-full rounded-lg px-4 py-2 pr-10 text-sm cursor-pointer"
                                   style="border: 1px solid var(--admin-border); background: var(--admin-bg); color: var(--admin-text);">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" style="color: var(--admin-text-light);">
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            </span>
                        </div>
                        <div class="jcal-popup" id="start-date-popup"></div>
                    </div>
                </div>

                <div class="flex-1">
                    <label for="end-date" class="block mb-1 text-sm font-medium" style="color: var(--admin-text-dim);">تا تاریخ</label>
                    <div class="jcal-wrapper" id="end-date-wrapper">
                        <div class="relative">
                            <input type="text" id="end-date" placeholder="انتخاب تاریخ" readonly
                                   class="w-full rounded-lg px-4 py-2 pr-10 text-sm cursor-pointer"
                                   style="border: 1px solid var(--admin-border); background: var(--admin-bg); color: var(--admin-text);">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" style="color: var(--admin-text-light);">
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            </span>
                        </div>
                        <div class="jcal-popup" id="end-date-popup"></div>
                    </div>
                </div>

                <div>
                    <button id="apply-date-range"
                            class="inline-flex items-center px-5 py-2 text-sm font-medium text-white rounded-lg transition-colors"
                            style="background: var(--admin-accent);"
                            onmouseover="this.style.background='var(--admin-accent-hover)'"
                            onmouseout="this.style.background='var(--admin-accent)'">
                        <svg class="w-4 h-4 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="9 11 12 14 22 4"></polyline>
                            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                        </svg>
                        اعمال
                    </button>
                </div>
            </div>
        </div>

        {{-- React SPA mount point  --}}
        <div class="rounded-xl overflow-hidden" style="background: var(--admin-surface); border: 1px solid var(--admin-border);">
            <div class="p-6">
                <div id="reports-panel"
                     class="fade-in"
                     data-base-url="{{ url('/') }}"
                     data-routes="{{ json_encode([
                            'revenueData'    => '/admin/reports/revenue',
                            'dailyRevenue'   => '/admin/reports/daily',
                            'weeklyRevenue'  => '/admin/reports/weekly',
                            'monthlyRevenue' => '/admin/reports/monthly',
                            'financialData'  => '/admin/reports/financial',
                            'specialistsData'=> '/admin/reports/specialist-performance',
                            'customersData'  => '/admin/reports/customer-satisfaction',
                            'servicesData'   => '/admin/reports/popular-services',
                            'export'         => '/admin/reports/export',
                        ]) }}"
                >
                    <div class="flex justify-center items-center min-h-[400px]">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2" style="border-color: var(--admin-accent);"></div>
                        <span class="mr-3 text-sm" style="color: var(--admin-text-dim);">در حال بارگذاری...</span>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('styles')
    @vite('resources/css/app.css')
    <style>
        /* ── jcal ── */
        .jcal-wrapper { position: relative; }
        .jcal-popup {
            display: none;
            position: absolute;
            top: calc(100% + 6px);
            right: 0;
            z-index: 9999;
            background: var(--admin-surface);
            border: 1px solid var(--admin-border);
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0,0,0,.12);
            padding: 12px;
            width: 280px;
            direction: rtl;
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
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 16px;
            color: var(--admin-text-dim);
            transition: background .15s;
        }
        .jcal-header button:hover { background: var(--admin-accent-light); color: var(--admin-accent); }
        .jcal-header span {
            font-size: .875rem;
            font-weight: 600;
            color: var(--admin-text);
        }
        .jcal-weekdays {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 2px;
            margin-bottom: 4px;
        }
        .jcal-weekdays span {
            text-align: center;
            font-size: .7rem;
            color: var(--admin-text-light);
            padding: 4px 0;
        }
        .jcal-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 2px;
        }
        .jcal-day {
            text-align: center;
            padding: 6px 2px;
            font-size: .8rem;
            border-radius: 6px;
            cursor: pointer;
            color: var(--admin-text);
            transition: background .15s, color .15s;
        }
        .jcal-day:hover { background: var(--admin-accent-light); color: var(--admin-accent); }
        .jcal-day.selected { background: var(--admin-accent); color: #fff; font-weight: 600; }
        .jcal-day.today { border: 1px solid var(--admin-accent); color: var(--admin-accent); font-weight: 600; }
        .jcal-day.empty { cursor: default; }
        .jcal-day.empty:hover { background: none; }

        /* ── Report type card active state ── */
        .report-type-card.active {
            border-color: var(--admin-accent) !important;
            background: var(--admin-accent-light) !important;
        }
    </style>
@endpush

@push('scripts')
    <script>
        window.initialData = {
            baseUrl: '{{ url('/') }}',
            routes: {
                dailyRevenue:   '/admin/reports/daily',
                weeklyRevenue:  '/admin/reports/weekly',
                monthlyRevenue: '/admin/reports/monthly',
                specialists:    '/admin/reports/specialist-performance',
                financial:      '/admin/reports/financial',
                customers:      '/admin/reports/customer-satisfaction',
                services:       '/admin/reports/popular-services',
                export:         '/admin/reports/export'
            },
            dateFormat: 'jYYYY/jMM/jDD'
        };


        (function () {
            /* Gregorian → Solar Conversion */
            function toJalali(gy, gm, gd) {
                var g_d_no, j_d_no, j_np, i, j_y, j_m, j_d;
                var g_days_in_month = [31,28,31,30,31,30,31,31,30,31,30,31];
                var j_days_in_month = [31,31,31,31,31,31,30,30,30,30,30,29];
                gy -= 1600; gm -= 1; gd -= 1;
                g_d_no = 365*gy + Math.floor((gy+3)/4) - Math.floor((gy+99)/100) + Math.floor((gy+399)/400);
                for (i=0; i<gm; i++) g_d_no += g_days_in_month[i];
                if (gm>1 && ((gy%4===0&&gy%100!==0)||(gy%400===0))) g_d_no++;
                g_d_no += gd;
                j_d_no = g_d_no - 79;
                j_np = Math.floor(j_d_no/12053); j_d_no %= 12053;
                j_y = 979 + 33*j_np + 4*Math.floor(j_d_no/1461);
                j_d_no %= 1461;
                if (j_d_no >= 366) { j_y += Math.floor((j_d_no-1)/365); j_d_no = (j_d_no-1)%365; }
                for (i=0; i<11 && j_d_no>=j_days_in_month[i]; i++) j_d_no -= j_days_in_month[i];
                j_m = i+1; j_d = j_d_no+1;
                return [j_y, j_m, j_d];
            }

            /* Solar → Gregorian conversion */
            function toGregorian(jy, jm, jd) {
                var sal_a, gy, gm, gd, g_d_no, j_d_no, i;
                var j_days_in_month = [31,31,31,31,31,31,30,30,30,30,30,29];
                var g_days_in_month = [31,28,31,30,31,30,31,31,30,31,30,31];
                jy -= 979; jm -= 1; jd -= 1;
                j_d_no = 365*jy + Math.floor(jy/33)*8 + Math.floor((jy%33+3)/4);
                for (i=0; i<jm; i++) j_d_no += j_days_in_month[i];
                j_d_no += jd;
                g_d_no = j_d_no + 79;
                gy = 1600 + 400*Math.floor(g_d_no/146097); g_d_no %= 146097;
                var leap = true;
                if (g_d_no >= 36525) { g_d_no--; gy += 100*Math.floor(g_d_no/36524); g_d_no %= 36524; if (g_d_no >= 365) g_d_no++; else leap = false; }
                gy += 4*Math.floor(g_d_no/1461); g_d_no %= 1461;
                if (g_d_no >= 366) { leap = false; g_d_no--; gy += Math.floor(g_d_no/365); g_d_no %= 365; }
                for (i=0; g_d_no>=g_days_in_month[i]+((i===1&&leap)?1:0); i++) g_d_no -= g_days_in_month[i]+((i===1&&leap)?1:0);
                gm = i+1; gd = g_d_no+1;
                return [gy, gm, gd];
            }

            var jMonthNames = ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];
            var jDayNames   = ['ش','ی','د','س','چ','پ','ج'];

            function pad(n) { return n < 10 ? '0'+n : ''+n; }

            function buildCalendar(popup, inputEl, hiddenEl, year, month, onSelect) {
                popup.innerHTML = '';

                /* header */
                var header = document.createElement('div');
                header.className = 'jcal-header';
                var btnPrev = document.createElement('button'); btnPrev.innerHTML = '&#8594;'; btnPrev.type='button';
                var btnNext = document.createElement('button'); btnNext.innerHTML = '&#8592;'; btnNext.type='button';
                var title   = document.createElement('span');
                title.textContent = jMonthNames[month-1] + ' ' + year;
                header.appendChild(btnPrev);
                header.appendChild(title);
                header.appendChild(btnNext);
                popup.appendChild(header);

                btnPrev.addEventListener('click', function(e){ e.stopPropagation(); var m=month-1,y=year; if(m<1){m=12;y--;} buildCalendar(popup,inputEl,hiddenEl,y,m,onSelect); });
                btnNext.addEventListener('click', function(e){ e.stopPropagation(); var m=month+1,y=year; if(m>12){m=1;y++;} buildCalendar(popup,inputEl,hiddenEl,y,m,onSelect); });

                /* weekday labels */
                var wdRow = document.createElement('div'); wdRow.className = 'jcal-weekdays';
                jDayNames.forEach(function(d){ var s=document.createElement('span'); s.textContent=d; wdRow.appendChild(s); });
                popup.appendChild(wdRow);

                /* days grid */
                var grid = document.createElement('div'); grid.className = 'jcal-grid';
                var firstDayGregorian = toGregorian(year, month, 1);
                var fd = new Date(firstDayGregorian[0], firstDayGregorian[1]-1, firstDayGregorian[2]);
                var startDow = (fd.getDay() + 1) % 7; // شنبه=0

                /* Today */
                var now = new Date();
                var todayJ = toJalali(now.getFullYear(), now.getMonth()+1, now.getDate());

                /* Current selection */
                var selVal = inputEl.value; // e.g. "1403/06/15"
                var selJ   = selVal ? selVal.split('/').map(Number) : null;

                /* Days of the month */
                var daysInMonth = [31,31,31,31,31,31,30,30,30,30,30,29];
                var dim = daysInMonth[month-1];

                for (var i=0; i<startDow; i++) {
                    var empty = document.createElement('div'); empty.className='jcal-day empty'; grid.appendChild(empty);
                }
                for (var d=1; d<=dim; d++) {
                    (function(day){
                        var cell = document.createElement('div'); cell.className='jcal-day';
                        cell.textContent = day;
                        if (todayJ[0]===year && todayJ[1]===month && todayJ[2]===day) cell.classList.add('today');
                        if (selJ && selJ[0]===year && selJ[1]===month && selJ[2]===day) cell.classList.add('selected');
                        cell.addEventListener('click', function(e){
                            e.stopPropagation();
                            var jalaliStr = year+'/'+pad(month)+'/'+pad(day);
                            var greg = toGregorian(year, month, day);
                            var gregStr = greg[0]+'-'+pad(greg[1])+'-'+pad(greg[2]);
                            inputEl.value  = jalaliStr;
                            if (hiddenEl) hiddenEl.value = gregStr;
                            popup.classList.remove('open');
                            if (onSelect) onSelect(jalaliStr, gregStr);
                        });
                        grid.appendChild(cell);
                    })(d);
                }
                popup.appendChild(grid);
            }

            function initJcal(inputId, popupId, hiddenId, onSelect) {
                var inputEl  = document.getElementById(inputId);
                var popup    = document.getElementById(popupId);
                var hiddenEl = hiddenId ? document.getElementById(hiddenId) : null;
                if (!inputEl || !popup) return;

                /* Initial date = today */
                var now = new Date();
                var todayJ = toJalali(now.getFullYear(), now.getMonth()+1, now.getDate());
                var curYear = todayJ[0], curMonth = todayJ[1];

                inputEl.addEventListener('click', function(e) {
                    e.stopPropagation();
                    /* Close all other popups */
                    document.querySelectorAll('.jcal-popup.open').forEach(function(p){ if(p!==popup) p.classList.remove('open'); });
                    buildCalendar(popup, inputEl, hiddenEl, curYear, curMonth, function(j, g){
                        if (onSelect) onSelect(j, g);
                    });
                    popup.classList.toggle('open');
                });

                /* Keep curYear/curMonth with scrolling */
                popup.addEventListener('click', function(e){ e.stopPropagation(); });

                document.addEventListener('click', function(){ popup.classList.remove('open'); });
            }

            window.jcalInit = initJcal;
        })();

        /* ══════════════════════════════════════════
           Page logic
           ══════════════════════════════════════════ */
        document.addEventListener('DOMContentLoaded', function () {
            var activeReportType = 'daily';
            var startDateGreg = '';
            var endDateGreg   = '';

            /* init jcal */
            jcalInit('start-date', 'start-date-popup', null, function(j, g) { startDateGreg = g; updateExportLinks(); });
            jcalInit('end-date',   'end-date-popup',   null, function(j, g) { endDateGreg   = g; updateExportLinks(); });

            function updateExportLinks() {
                var params = new URLSearchParams();
                params.append('report_type', activeReportType);
                if (startDateGreg && endDateGreg) {
                    params.append('start_date', startDateGreg);
                    params.append('end_date',   endDateGreg);
                }
                var base = "{{ route('admin.reports.export') }}";

                var pdfLink = document.getElementById('pdf-export-link');
                if (pdfLink) { params.set('format','pdf');   pdfLink.href   = base+'?'+params.toString(); }

                var excelLink = document.getElementById('excel-export-link');
                if (excelLink) { params.set('format','excel'); excelLink.href = base+'?'+params.toString(); }
            }

            window.selectReportType = function (type) {
                document.querySelectorAll('.report-type-card').forEach(function(c){ c.classList.remove('active'); });
                document.getElementById(type+'-report-card').classList.add('active');

                var datePicker = document.getElementById('date-picker-section');
                datePicker.classList.toggle('hidden', type !== 'custom');

                activeReportType = type;
                updateExportLinks();
            };

            document.getElementById('apply-date-range').addEventListener('click', function () {
                var s = document.getElementById('start-date').value;
                var e = document.getElementById('end-date').value;
                if (s && e) {
                    updateExportLinks();
                } else {
                    alert('لطفاً بازه زمانی را انتخاب کنید');
                }
            });

            updateExportLinks();
            selectReportType('daily');
        });
    </script>
    @viteReactRefresh
    @vite(['resources/js/admin.jsx'])
@endpush
