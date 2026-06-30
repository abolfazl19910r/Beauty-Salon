@extends('layouts.admin')

@section('title', 'گزارشات مدیریتی')

@push('styles')
    <style>
        /* ── jcal ── */
        .jcal-wrapper { position: relative; }
        .jcal-popup {
            display: none; position: absolute; top: calc(100% + 6px); right: 0;
            z-index: 9999; background: var(--admin-surface); border: 1px solid var(--admin-border);
            border-radius: 12px; box-shadow: 0 8px 30px rgba(0,0,0,.12);
            padding: 12px; width: 280px; direction: rtl;
        }
        .jcal-popup.open { display: block; }
        .jcal-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
        .jcal-header button { background: none; border: none; cursor: pointer; padding: 4px 8px; border-radius: 6px; font-size: 16px; color: var(--admin-text-dim); }
        .jcal-header button:hover { background: var(--admin-accent-light); color: var(--admin-accent); }
        .jcal-header span { font-size: .875rem; font-weight: 600; color: var(--admin-text); }
        .jcal-weekdays { display: grid; grid-template-columns: repeat(7,1fr); gap: 2px; margin-bottom: 4px; }
        .jcal-weekdays span { text-align: center; font-size: .7rem; color: var(--admin-text-light); padding: 4px 0; }
        .jcal-grid { display: grid; grid-template-columns: repeat(7,1fr); gap: 2px; }
        .jcal-day { text-align: center; padding: 6px 2px; font-size: .8rem; border-radius: 6px; cursor: pointer; color: var(--admin-text); transition: background .15s; }
        .jcal-day:hover { background: var(--admin-accent-light); color: var(--admin-accent); }
        .jcal-day.selected { background: var(--admin-accent); color: #fff; font-weight: 600; }
        .jcal-day.today { border: 1px solid var(--admin-accent); color: var(--admin-accent); font-weight: 600; }
        .jcal-day.empty { cursor: default; }
        .jcal-day.empty:hover { background: none; }
        .jcal-today-btn { display: block; text-align: center; margin-top: 8px; padding-top: 8px; border-top: 1px solid var(--admin-border); }
        .jcal-today-btn button { font-size: .75rem; padding: 3px 12px; border-radius: 6px; border: none; cursor: pointer; background: var(--admin-accent-light); color: var(--admin-accent); }
        /* ── stat card ── */
        .stat-card { background: var(--admin-surface); border: 1px solid var(--admin-border); border-radius: 12px; padding: 20px; }
        .stat-card .label { font-size: .8rem; color: var(--admin-text-dim); margin-bottom: 6px; }
        .stat-card .value { font-size: 1.1rem; font-weight: 700; }
        /* ── table ── */
        .report-table { width: 100%; border-collapse: collapse; font-size: .85rem; }
        .report-table th { background: var(--admin-accent); color: #fff; padding: 10px 14px; text-align: right; font-weight: 600; }
        .report-table td { padding: 9px 14px; border-bottom: 1px solid var(--admin-border); color: var(--admin-text); }
        .report-table tbody tr:nth-child(even) { background: var(--admin-bg); }
        .report-table tbody tr:hover { background: var(--admin-accent-light); }
        /* ── tabs ── */
        .rtab { padding: 10px 20px; font-size: .875rem; cursor: pointer; border-bottom: 2px solid transparent; color: var(--admin-text-dim); background: none; border-top: none; border-left: none; border-right: none; transition: all .15s; }
        .rtab.active { color: var(--admin-accent); border-bottom-color: var(--admin-accent); font-weight: 600; }
        .rtab:hover:not(.active) { color: var(--admin-accent); background: var(--admin-accent-light); }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        /* ── empty state ── */
        .empty-state { text-align: center; padding: 48px 0; color: var(--admin-text-dim); font-size: .9rem; }
    </style>
@endpush

@section('content')
    <div class="container px-6 mx-auto">

        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold flex items-center gap-2" style="color: var(--admin-text);">
                <svg class="w-6 h-6" style="color:var(--admin-accent)" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21.21 15.89A10 10 0 1 1 8 2.83"/><path d="M22 12A10 10 0 0 0 12 2v10z"/>
                </svg>
                گزارشات مدیریتی
            </h1>
            <a href="{{ route('admin.dashboard') }}"
               class="inline-flex items-center gap-1 px-4 py-2 text-sm rounded-lg border transition-colors"
               style="color:var(--admin-text-dim);background:var(--admin-surface);border-color:var(--admin-border);">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
                بازگشت
            </a>
        </div>

        {{-- Filter --}}
        <form method="GET" action="{{ route('admin.reports.index') }}"
              class="rounded-xl p-5 mb-6" style="background:var(--admin-surface);border:1px solid var(--admin-border);">
            <div class="flex flex-wrap items-end gap-4">

                {{-- Report type --}}
                <div>
                    <label class="block text-xs mb-2 font-medium" style="color:var(--admin-text-dim);">نوع گزارش</label>
                    <div class="flex gap-2">
                        @foreach(['daily'=>'روزانه','weekly'=>'هفتگی','monthly'=>'ماهانه'] as $val=>$lbl)
                            <button type="button" onclick="setType('{{ $val }}')"
                                    id="type-btn-{{ $val }}"
                                    class="px-4 py-2 rounded-lg text-sm font-medium transition-colors type-btn">
                                {{ $lbl }}
                            </button>
                        @endforeach
                        <input type="hidden" name="type" id="type-input" value="{{ $type ?? 'daily' }}">
                    </div>
                </div>

                {{-- From history --}}
                <div>
                    <label class="block text-xs mb-2 font-medium" style="color:var(--admin-text-dim);">از تاریخ</label>
                    <div class="jcal-wrapper">
                        <input type="text" id="start-jalali" placeholder="انتخاب کنید..." readonly
                               value="{{ $startDate ? jalali_date($startDate,'Y/m/d') : '' }}"
                               class="w-40 rounded-lg px-3 py-2 text-sm cursor-pointer"
                               style="border:1px solid var(--admin-border);background:var(--admin-bg);color:var(--admin-text)">
                        <input type="hidden" name="start_date" id="start-date-val" value="{{ $startDate ?? '' }}">
                        <div class="jcal-popup" id="start-popup"></div>
                    </div>
                </div>

                {{-- to date --}}
                <div>
                    <label class="block text-xs mb-2 font-medium" style="color:var(--admin-text-dim);">تا تاریخ</label>
                    <div class="jcal-wrapper">
                        <input type="text" id="end-jalali" placeholder="انتخاب کنید..." readonly
                               value="{{ $endDate ? jalali_date($endDate,'Y/m/d') : '' }}"
                               class="w-40 rounded-lg px-3 py-2 text-sm cursor-pointer"
                               style="border:1px solid var(--admin-border);background:var(--admin-bg);color:var(--admin-text)">
                        <input type="hidden" name="end_date" id="end-date-val" value="{{ $endDate ?? '' }}">
                        <div class="jcal-popup" id="end-popup"></div>
                    </div>
                </div>

                {{-- Buttons --}}
                <div class="flex gap-2 mr-auto flex-wrap">
                    <button type="submit"
                            class="inline-flex items-center gap-1 px-5 py-2 text-sm font-medium text-white rounded-lg"
                            style="background:var(--admin-accent)">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                        اعمال فیلتر
                    </button>

                    {{-- Clear filter --}}
                    @if($startDate || $endDate)
                        <a href="{{ route('admin.reports.index') }}"
                           class="inline-flex items-center gap-1 px-4 py-2 text-sm rounded-lg border"
                           style="color:var(--admin-text-dim);background:var(--admin-surface);border-color:var(--admin-border);">
                            ✕ پاک‌کردن
                        </a>
                    @endif

                    @permission('export-reports')
                    @if($startDate && $endDate)
                        <a href="{{ route('admin.reports.export', ['format'=>'pdf','report_type'=>$type??'daily','start_date'=>$startDate,'end_date'=>$endDate]) }}"
                           class="inline-flex items-center gap-1 px-4 py-2 text-sm text-white rounded-lg"
                           style="background:#dc2626">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                            PDF
                        </a>
                        <a href="{{ route('admin.reports.export', ['format'=>'excel','report_type'=>$type??'daily','start_date'=>$startDate,'end_date'=>$endDate]) }}"
                           class="inline-flex items-center gap-1 px-4 py-2 text-sm text-white rounded-lg"
                           style="background:#16a34a">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                            Excel
                        </a>
                    @endif
                    @endpermission
                </div>
            </div>
        </form>

        {{-- When the filter is not yet selected --}}
        @if(!$startDate && !$endDate)
            <div class="rounded-xl p-16 text-center" style="background:var(--admin-surface);border:1px solid var(--admin-border);">
                <svg class="w-16 h-16 mx-auto mb-4" style="color:var(--admin-border)" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
                <p class="text-lg font-medium mb-2" style="color:var(--admin-text)">بازه زمانی انتخاب نشده</p>
                <p class="text-sm" style="color:var(--admin-text-dim)">برای مشاهده گزارش، تاریخ شروع و پایان را انتخاب کرده و فیلتر را اعمال کنید.</p>
            </div>

        @else

            {{-- Statistics cards --}}
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
                @php
                    $statCards = [
                        ['label'=>'درآمد کل','value'=>number_format($summary['total_revenue']).' ت','color'=>'#16a34a'],
                        ['label'=>'کل نوبت‌ها','value'=>number_format($summary['total_bookings']),'color'=>'#334155'],
                        ['label'=>'انجام‌شده','value'=>number_format($summary['completed_bookings']),'color'=>'#0284c7'],
                        ['label'=>'لغو شده','value'=>number_format($summary['cancelled_bookings']),'color'=>'#dc2626'],
                        ['label'=>'درآمد معلق','value'=>number_format($summary['pending_payments']).' ت','color'=>'#d97706'],
                        ['label'=>'میانگین نوبت','value'=>number_format($summary['average_booking_value']).' ت','color'=>'#7c3aed'],
                    ];
                @endphp
                @foreach($statCards as $card)
                    <div class="stat-card">
                        <div class="label">{{ $card['label'] }}</div>
                        <div class="value" style="color:{{ $card['color'] }}">{{ $card['value'] }}</div>
                    </div>
                @endforeach
            </div>

            {{-- Fevers --}}
            <div class="rounded-xl overflow-hidden" style="background:var(--admin-surface);border:1px solid var(--admin-border);">
                <div class="flex border-b" style="border-color:var(--admin-border);">
                    <button class="rtab active" onclick="showTab('revenue',this)">📊 درآمد</button>
                    <button class="rtab" onclick="showTab('specialists',this)">👥 متخصصین</button>
                    <button class="rtab" onclick="showTab('services',this)">✂️ خدمات</button>
                    <button class="rtab" onclick="showTab('satisfaction',this)">⭐ رضایت</button>
                </div>

                {{-- Income fever --}}
                <div id="tab-revenue" class="tab-content active p-6">
                    <h3 class="text-base font-semibold mb-4" style="color:var(--admin-text)">
                        نمودار درآمد — {{ ['daily'=>'روزانه','weekly'=>'هفتگی','monthly'=>'ماهانه'][$type??'daily'] }}
                    </h3>
                    @if(count($revenueChart) > 0)
                        <div style="height:300px"><canvas id="revenueChart"></canvas></div>
                    @else
                        <div class="empty-state">داده‌ای برای این بازه زمانی وجود ندارد</div>
                    @endif

                    @if($monthlyBreakdown->count())
                        <h3 class="text-base font-semibold mt-8 mb-4" style="color:var(--admin-text)">گردش مالی ماهانه (بر اساس بازه انتخابی)</h3>
                        <div style="height:260px"><canvas id="monthlyChart"></canvas></div>
                    @endif
                </div>

                {{-- Experts' Fever --}}
                <div id="tab-specialists" class="tab-content p-6">
                    <h3 class="text-base font-semibold mb-4" style="color:var(--admin-text)">عملکرد متخصصین</h3>
                    @if($specialists->count())
                        <div style="height:300px" class="mb-6"><canvas id="specialistChart"></canvas></div>
                        <table class="report-table">
                            <thead><tr>
                                <th>نام متخصص</th><th>تعداد نوبت</th><th>درآمد کل (تومان)</th>
                                <th>نرخ کمیسیون</th><th>سهم متخصص (تومان)</th>
                                <th>نرخ تکمیل</th><th>نرخ بازگشت</th>
                            </tr></thead>
                            <tbody>
                            @foreach($specialists as $sp)
                                <tr>
                                    <td>{{ $sp['name'] }}</td>
                                    <td>{{ number_format($sp['total_bookings']) }}</td>
                                    <td>{{ number_format($sp['total_revenue']) }}</td>
                                    <td>
                        <span class="px-2 py-0.5 text-xs rounded-full font-medium"
                              style="background:var(--admin-accent-light);color:var(--admin-accent)">
                            {{ $sp['commission_rate'] }}%
                        </span>
                                    </td>
                                    <td style="color:#16a34a;font-weight:600">{{ number_format($sp['specialist_share']) }}</td>
                                    <td>{{ $sp['booking_completion_rate'] }}%</td>
                                    <td>{{ $sp['customer_return_rate'] }}%</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="empty-state">داده‌ای برای این بازه زمانی وجود ندارد</div>
                    @endif
                </div>

                {{-- Services tab --}}
                <div id="tab-services" class="tab-content p-6">
                    <h3 class="text-base font-semibold mb-4" style="color:var(--admin-text)">درآمد به تفکیک خدمات</h3>
                    @if($serviceRevenue->count())
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div style="height:280px"><canvas id="serviceChart"></canvas></div>
                            <table class="report-table">
                                <thead><tr><th>خدمت</th><th>نوبت</th><th>درآمد (تومان)</th></tr></thead>
                                <tbody>
                                @foreach($serviceRevenue as $svc)
                                    <tr>
                                        <td>{{ $svc->name }}</td>
                                        <td>{{ number_format($svc->bookings_count) }}</td>
                                        <td>{{ number_format($svc->revenue ?? 0) }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="empty-state">داده‌ای برای این بازه زمانی وجود ندارد</div>
                    @endif

                    @if($popularServices->count())
                        <h3 class="text-base font-semibold mt-4 mb-4" style="color:var(--admin-text)">خدمات محبوب (تعداد نوبت)</h3>
                        <table class="report-table">
                            <thead><tr><th>خدمت</th><th>تعداد نوبت</th><th>درآمد (تومان)</th></tr></thead>
                            <tbody>
                            @foreach($popularServices as $ps)
                                <tr>
                                    <td>{{ $ps->name }}</td>
                                    <td>{{ number_format($ps->bookings_count) }}</td>
                                    <td>{{ number_format($ps->revenue ?? 0) }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>

                {{-- Satisfaction fever --}}
                <div id="tab-satisfaction" class="tab-content p-6">
                    <h3 class="text-base font-semibold mb-4" style="color:var(--admin-text)">رضایت مشتریان</h3>
                    @if($satisfaction->count())
                        <table class="report-table">
                            <thead><tr>
                                <th>نام متخصص</th><th>میانگین امتیاز</th><th>تعداد نظر</th><th>درصد رضایت</th>
                            </tr></thead>
                            <tbody>
                            @foreach($satisfaction as $sat)
                                <tr>
                                    <td>{{ $sat['specialist_name'] }}</td>
                                    <td>{{ $sat['average_rating'] }} / 5</td>
                                    <td>{{ number_format($sat['total_ratings']) }}</td>
                                    <td>
                                        <div class="flex items-center gap-2">
                                            <div class="flex-1 rounded-full h-2" style="background:var(--admin-border)">
                                                <div class="h-2 rounded-full" style="width:{{ $sat['satisfaction_rate'] }}%;background:#16a34a"></div>
                                            </div>
                                            <span class="text-xs font-medium">{{ $sat['satisfaction_rate'] }}%</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="empty-state">نظری برای این بازه زمانی ثبت نشده</div>
                    @endif
                </div>
            </div>

        @endif {{-- end if $startDate --}}

    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        /* ── Data from PHP ── */
        const revenueData    = @json($revenueChart ?? []);
        const monthlyData    = @json($monthlyBreakdown ?? collect());
        const specialistData = @json($specialists ?? collect());
        const serviceData    = @json($serviceRevenue ? $serviceRevenue->values() : collect());

        const CHART_COLORS = ['#0088FE','#00C49F','#FFBB28','#FF8042','#8884D8','#82ca9d','#ffc658','#a4de6c'];

        Chart.defaults.font.family = "'Vazirmatn', 'Vazir', sans-serif";
        Chart.defaults.plugins.legend.rtl = true;

        /* ── Income chart ── */
        if (revenueData.length && document.getElementById('revenueChart')) {
            new Chart(document.getElementById('revenueChart'), {
                data: {
                    labels: revenueData.map(r => r.label),
                    datasets: [
                        {
                            type: 'bar',
                            label: 'درآمد (تومان)',
                            data: revenueData.map(r => r.revenue),
                            backgroundColor: 'rgba(51,65,85,.75)',
                            yAxisID: 'y'
                        },
                        {
                            type: 'line',
                            label: 'تعداد نوبت',
                            data: revenueData.map(r => r.bookings),
                            borderColor: '#16a34a',
                            backgroundColor: 'transparent',
                            yAxisID: 'y1',
                            tension: 0.3,
                            pointRadius: 4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' },
                        tooltip: {
                            callbacks: {
                                label: ctx => ctx.dataset.label + ': ' + ctx.parsed.y.toLocaleString('fa-IR')
                            }
                        }
                    },
                    scales: {
                        x: { ticks: { font: { family: "'Vazirmatn','Vazir',sans-serif" } } },
                        y: {
                            position: 'right',
                            title: { display: true, text: 'درآمد (تومان)', font: { family: "'Vazirmatn','Vazir',sans-serif" } },
                            ticks: { callback: v => v.toLocaleString('fa-IR') }
                        },
                        y1: {
                            position: 'left',
                            grid: { drawOnChartArea: false },
                            title: { display: true, text: 'تعداد نوبت', font: { family: "'Vazirmatn','Vazir',sans-serif" } }
                        }
                    }
                }
            });
        }

        /* ── Monthly turnover chart — based on selected interval only ── */
        const MONTH_FA = ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];
        if (monthlyData.length && document.getElementById('monthlyChart')) {
            new Chart(document.getElementById('monthlyChart'), {
                data: {
                    labels: monthlyData.map(r => MONTH_FA[r.month - 1]),
                    datasets: [
                        {
                            type: 'bar',
                            label: 'درآمد (تومان)',
                            data: monthlyData.map(r => r.revenue),
                            backgroundColor: 'rgba(51,65,85,.75)',
                            yAxisID: 'y'
                        },
                        {
                            type: 'bar',
                            label: 'تعداد نوبت',
                            data: monthlyData.map(r => r.bookings),
                            backgroundColor: 'rgba(22,163,74,.65)',
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom' } },
                    scales: {
                        y:  { position: 'right', ticks: { callback: v => v.toLocaleString('fa-IR') } },
                        y1: { position: 'left', grid: { drawOnChartArea: false } }
                    }
                }
            });
        }

        /* ── Experts Chart ── */
        if (specialistData.length && document.getElementById('specialistChart')) {
            new Chart(document.getElementById('specialistChart'), {
                type: 'bar',
                data: {
                    labels: specialistData.map(s => s.name),
                    datasets: [
                        { label: 'تعداد نوبت',      data: specialistData.map(s => s.total_bookings),          backgroundColor: 'rgba(51,65,85,.75)' },
                        { label: 'نرخ تکمیل (%)',    data: specialistData.map(s => s.booking_completion_rate), backgroundColor: 'rgba(22,163,74,.65)' },
                        { label: 'نرخ بازگشت (%)',   data: specialistData.map(s => s.customer_return_rate),   backgroundColor: 'rgba(124,58,237,.5)' },
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom' } }
                }
            });
        }

        /* ── Service Chart (Pie) ── */
        const validSvc = serviceData.filter(s => (s.revenue ?? 0) > 0).slice(0, 6);
        if (validSvc.length && document.getElementById('serviceChart')) {
            new Chart(document.getElementById('serviceChart'), {
                type: 'pie',
                data: {
                    labels: validSvc.map(s => s.name),
                    datasets: [{ data: validSvc.map(s => s.revenue), backgroundColor: CHART_COLORS }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' },
                        tooltip: {
                            callbacks: {
                                label: ctx => ctx.label + ': ' + ctx.parsed.toLocaleString('fa-IR') + ' تومان'
                            }
                        }
                    }
                }
            });
        }

        /* ── tab ── */
        function showTab(id, btn) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.rtab').forEach(el => el.classList.remove('active'));
            document.getElementById('tab-' + id).classList.add('active');
            btn.classList.add('active');
        }

        /* ── type buttons ── */
        (function() {
            var current = document.getElementById('type-input').value || 'daily';
            function applyStyle(val) {
                document.querySelectorAll('.type-btn').forEach(b => {
                    b.style.background = 'var(--admin-accent-light)';
                    b.style.color = 'var(--admin-accent)';
                });
                var a = document.getElementById('type-btn-' + val);
                if (a) { a.style.background = 'var(--admin-accent)'; a.style.color = '#fff'; }
            }
            applyStyle(current);
            window.setType = function(val) {
                document.getElementById('type-input').value = val;
                applyStyle(val);
            };
        })();

        /* ── jcal ── */
        (function(){
            function toJalali(gy,gm,gd){var g_d_no,j_d_no,j_np,i,j_y,j_m,j_d,g_days_in_month=[31,28,31,30,31,30,31,31,30,31,30,31],j_days_in_month=[31,31,31,31,31,31,30,30,30,30,30,29];gy-=1600;gm-=1;gd-=1;g_d_no=365*gy+Math.floor((gy+3)/4)-Math.floor((gy+99)/100)+Math.floor((gy+399)/400);for(i=0;i<gm;i++)g_d_no+=g_days_in_month[i];if(gm>1&&((gy%4===0&&gy%100!==0)||(gy%400===0)))g_d_no++;g_d_no+=gd;j_d_no=g_d_no-79;j_np=Math.floor(j_d_no/12053);j_d_no%=12053;j_y=979+33*j_np+4*Math.floor(j_d_no/1461);j_d_no%=1461;if(j_d_no>=366){j_y+=Math.floor((j_d_no-1)/365);j_d_no=(j_d_no-1)%365;}for(i=0;i<11&&j_d_no>=j_days_in_month[i];i++)j_d_no-=j_days_in_month[i];j_m=i+1;j_d=j_d_no+1;return[j_y,j_m,j_d];}
            function toGregorian(jy,jm,jd){var gy,gm,gd,g_d_no,j_d_no,i,j_days_in_month=[31,31,31,31,31,31,30,30,30,30,30,29],g_days_in_month=[31,28,31,30,31,30,31,31,30,31,30,31];jy-=979;jm-=1;jd-=1;j_d_no=365*jy+Math.floor(jy/33)*8+Math.floor((jy%33+3)/4);for(i=0;i<jm;i++)j_d_no+=j_days_in_month[i];j_d_no+=jd;g_d_no=j_d_no+79;gy=1600+400*Math.floor(g_d_no/146097);g_d_no%=146097;var leap=true;if(g_d_no>=36525){g_d_no--;gy+=100*Math.floor(g_d_no/36524);g_d_no%=36524;if(g_d_no>=365)g_d_no++;else leap=false;}gy+=4*Math.floor(g_d_no/1461);g_d_no%=1461;if(g_d_no>=366){leap=false;g_d_no--;gy+=Math.floor(g_d_no/365);g_d_no%=365;}for(i=0;g_d_no>=g_days_in_month[i]+((i===1&&leap)?1:0);i++)g_d_no-=g_days_in_month[i]+((i===1&&leap)?1:0);gm=i+1;gd=g_d_no+1;return[gy,gm,gd];}
            var JM=['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];
            var JD=['ش','ی','د','س','چ','پ','ج'];
            function pad(n){return n<10?'0'+n:''+n;}
            var now=new Date(), todayJ=toJalali(now.getFullYear(),now.getMonth()+1,now.getDate());

            function buildCal(popup,dispEl,hidEl,yr,mo){
                popup.innerHTML='';
                var hdr=document.createElement('div'); hdr.className='jcal-header';
                var bp=document.createElement('button'); bp.innerHTML='&#8594;'; bp.type='button';
                var bn=document.createElement('button'); bn.innerHTML='&#8592;'; bn.type='button';
                var ti=document.createElement('span'); ti.textContent=JM[mo-1]+' '+yr;
                hdr.appendChild(bp); hdr.appendChild(ti); hdr.appendChild(bn); popup.appendChild(hdr);
                bp.onclick=function(e){e.stopPropagation();var m=mo-1,y=yr;if(m<1){m=12;y--;}buildCal(popup,dispEl,hidEl,y,m);};
                bn.onclick=function(e){e.stopPropagation();var m=mo+1,y=yr;if(m>12){m=1;y++;}buildCal(popup,dispEl,hidEl,y,m);};
                var wd=document.createElement('div'); wd.className='jcal-weekdays';
                JD.forEach(function(d){var s=document.createElement('span');s.textContent=d;wd.appendChild(s);}); popup.appendChild(wd);
                var grid=document.createElement('div'); grid.className='jcal-grid';
                var fg=toGregorian(yr,mo,1); var fd=new Date(fg[0],fg[1]-1,fg[2]); var dow=(fd.getDay()+1)%7;
                var dim=[31,31,31,31,31,31,30,30,30,30,30,29][mo-1];
                var selVal=dispEl.value;
                var selParts=selVal?selVal.split('/').map(Number):null;
                for(var i=0;i<dow;i++){var e=document.createElement('div');e.className='jcal-day empty';grid.appendChild(e);}
                for(var d=1;d<=dim;d++){
                    (function(day){
                        var cell=document.createElement('div'); cell.className='jcal-day'; cell.textContent=day;
                        if(todayJ[0]===yr&&todayJ[1]===mo&&todayJ[2]===day) cell.classList.add('today');
                        if(selParts&&selParts[0]===yr&&selParts[1]===mo&&selParts[2]===day) cell.classList.add('selected');
                        cell.onclick=function(e){
                            e.stopPropagation();
                            var jalStr=yr+'/'+pad(mo)+'/'+pad(day);
                            var greg=toGregorian(yr,mo,day);
                            var gregStr=greg[0]+'-'+pad(greg[1])+'-'+pad(greg[2]);
                            dispEl.value=jalStr; hidEl.value=gregStr;
                            popup.classList.remove('open');
                        };
                        grid.appendChild(cell);
                    })(d);
                }
                popup.appendChild(grid);
                var tb=document.createElement('div'); tb.className='jcal-today-btn';
                var tbtn=document.createElement('button'); tbtn.type='button'; tbtn.textContent='برو به امروز';
                tbtn.onclick=function(e){e.stopPropagation();buildCal(popup,dispEl,hidEl,todayJ[0],todayJ[1]);};
                tb.appendChild(tbtn); popup.appendChild(tb);
            }

            function initJcal(dispId,hidId,popupId){
                var disp=document.getElementById(dispId);
                var hid=document.getElementById(hidId);
                var popup=document.getElementById(popupId);
                if(!disp||!popup) return;
                var curY=todayJ[0], curM=todayJ[1];
                if(disp.value){var p=disp.value.split('/').map(Number);if(p.length===3){curY=p[0];curM=p[1];}}
                disp.onclick=function(e){
                    e.stopPropagation();
                    document.querySelectorAll('.jcal-popup.open').forEach(function(p){if(p!==popup)p.classList.remove('open');});
                    buildCal(popup,disp,hid,curY,curM);
                    popup.classList.toggle('open');
                };
                popup.onclick=function(e){e.stopPropagation();};
                document.addEventListener('click',function(){popup.classList.remove('open');});
            }

            document.addEventListener('DOMContentLoaded',function(){
                initJcal('start-jalali','start-date-val','start-popup');
                initJcal('end-jalali','end-date-val','end-popup');
            });
        })();
    </script>
@endpush
