@extends('layouts.admin')

@section('title', 'داشبورد')

@push('styles')
    <style>
        .stat-card {
            background: var(--admin-surface);
            border: 1px solid var(--admin-border);
            border-radius: 0.75rem;
            transition: box-shadow 0.2s, transform 0.2s;
        }
        .stat-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.07);
            transform: translateY(-2px);
        }
        .stat-icon {
            width: 48px; height: 48px;
            border-radius: 0.625rem;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .filter-btn {
            padding: 6px 16px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 500;
            transition: background 0.15s, color 0.15s;
            cursor: pointer;
            border: none;
            color: var(--admin-text-dim);
            background: transparent;
        }
        .filter-btn:hover { background: var(--admin-accent-light); }
        .filter-btn.active {
            background: var(--admin-accent);
            color: #fff;
        }
        .section-card {
            background: var(--admin-surface);
            border: 1px solid var(--admin-border);
            border-radius: 0.75rem;
            overflow: hidden;
        }
        .section-header {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--admin-border);
            font-weight: 600;
            font-size: 0.95rem;
            color: var(--admin-text);
        }
        .progress-bar-track {
            width: 100%; height: 6px;
            background: var(--admin-border);
            border-radius: 9999px;
            overflow: hidden;
        }
        .progress-bar-fill {
            height: 100%;
            background: var(--admin-accent);
            border-radius: 9999px;
            transition: width 0.5s;
        }
        .badge-status {
            padding: 2px 10px;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .chart-spinner {
            display: flex; align-items: center; justify-content: center;
            height: 100%;
        }
        .chart-spinner-inner {
            width: 36px; height: 36px;
            border: 3px solid var(--admin-border);
            border-top-color: var(--admin-accent);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
@endpush

@section('content')
    <div class="fade-in">

        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
            <div>
                <h1 class="text-xl font-bold" style="color: var(--admin-text);">داشبورد</h1>
                <p class="text-sm mt-0.5" style="color: var(--admin-text-dim);">خلاصه وضعیت سیستم مدیریت سالن زیبایی</p>
            </div>
            <div class="flex items-center gap-1 p-1 rounded-lg" style="background: var(--admin-accent-light); border: 1px solid var(--admin-border);">
                <button id="filter-today" class="filter-btn active" onclick="updateTimeFilter('today')">امروز</button>
                <button id="filter-week"  class="filter-btn" onclick="updateTimeFilter('week')">هفته</button>
                <button id="filter-month" class="filter-btn" onclick="updateTimeFilter('month')">ماه</button>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-6">

            <div class="stat-card p-5 flex items-center gap-4">
                <div class="stat-icon" style="background:#EFF6FF; color:#2563EB;">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs mb-1" style="color:var(--admin-text-dim);">نوبت‌های امروز</p>
                    <p class="text-2xl font-bold persian-number" id="today-bookings-count" style="color:var(--admin-text);">{{ $todayBookingsCount }}</p>
                </div>
                <a href="{{ route('admin.bookings.index') }}" class="text-xs px-2 py-1 rounded" style="color:var(--admin-accent); background:var(--admin-accent-light);">مشاهده</a>
            </div>

            <div class="stat-card p-5 flex items-center gap-4">
                <div class="stat-icon" style="background:#F0FDF4; color:#16A34A;">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs mb-1" style="color:var(--admin-text-dim);">
                        درآمد سالن
                        <span class="text-xs px-1.5 py-0.5 rounded mr-1" style="background:#DCFCE7; color:#166534;">%{{ $commissionRate ?? 10 }} کمیسیون</span>
                    </p>
                    <p class="text-2xl font-bold persian-number" id="total-revenue" style="color:var(--admin-text);">{{ number_format($totalRevenue) }}</p>
                </div>
                <a href="{{ route('admin.reports.index') }}" class="text-xs px-2 py-1 rounded" style="color:#16A34A; background:#F0FDF4;">گزارش</a>
            </div>

            <div class="stat-card p-5 flex items-center gap-4">
                <div class="stat-icon" style="background:#F5F3FF; color:#7C3AED;">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M9 7a4 4 0 108 0 4 4 0 00-8 0zM23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs mb-1" style="color:var(--admin-text-dim);">کاربران</p>
                    <p class="text-2xl font-bold persian-number" id="users-count" style="color:var(--admin-text);">{{ $usersCount }}</p>
                </div>
                <a href="{{ route('admin.users.index') }}" class="text-xs px-2 py-1 rounded" style="color:#7C3AED; background:#F5F3FF;">مشاهده</a>
            </div>

            <div class="stat-card p-5 flex items-center gap-4">
                <div class="stat-icon" style="background:#FFF7ED; color:#EA580C;">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs mb-1" style="color:var(--admin-text-dim);">متخصصین</p>
                    <p class="text-2xl font-bold persian-number" id="specialists-count" style="color:var(--admin-text);">{{ $specialistsCount }}</p>
                </div>
                <a href="{{ route('admin.specialists.index') }}" class="text-xs px-2 py-1 rounded" style="color:#EA580C; background:#FFF7ED;">مشاهده</a>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-5 mb-5">

            <div class="section-card xl:col-span-2">
                <div class="section-header flex justify-between items-center">
                    <span>نمودار درآمد (<span id="revenue-chart-title">امروز</span>)</span>
                </div>
                <div class="p-4">
                    <div id="revenue-chart" style="height:280px;">
                        <div class="chart-spinner"><div class="chart-spinner-inner"></div></div>
                    </div>
                </div>
            </div>

            <div class="section-card">
                <div class="section-header">خدمات محبوب</div>
                <div class="p-4 space-y-4" id="popular-services-container">
                    @forelse($popularServices as $service)
                        @php $pct = $popularServices->max('bookings_count') > 0
                        ? min(($service->bookings_count / $popularServices->max('bookings_count')) * 100, 100)
                        : 0; @endphp
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span style="color:var(--admin-text);">{{ $service->name }}</span>
                                <span style="color:var(--admin-text-dim);">{{ $service->bookings_count }} نوبت</span>
                            </div>
                            <div class="progress-bar-track">
                                <div class="progress-bar-fill" style="width:{{ $pct }}%;"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-center py-8" style="color:var(--admin-text-dim);">داده‌ای موجود نیست</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-5 mb-5">

            <div class="section-card xl:col-span-2">
                <div class="section-header">آمار متخصصین</div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                        <tr style="background:var(--admin-accent-light); color:var(--admin-text-dim);">
                            <th class="px-4 py-3 text-right font-medium">متخصص</th>
                            <th class="px-4 py-3 text-right font-medium">نوبت‌های امروز</th>
                            <th class="px-4 py-3 text-right font-medium">نرخ تکمیل</th>
                            <th class="px-4 py-3 text-right font-medium">درآمد (ت)</th>
                            <th class="px-4 py-3 text-right font-medium">عملکرد</th>
                        </tr>
                        </thead>
                        <tbody id="specialists-table-body" style="border-top:1px solid var(--admin-border);">
                        @forelse($topSpecialists as $specialist)
                            @php
                                $cr = 0;
                                if ($specialist->bookings_count > 0) {
                                    $done = $specialist->bookings()->where('status','completed')->count();
                                    $cr = ($done / $specialist->bookings_count) * 100;
                                }
                            @endphp
                            <tr style="border-bottom:1px solid var(--admin-border);"
                                onmouseover="this.style.background='var(--admin-accent-light)'"
                                onmouseout="this.style.background=''">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold"
                                             style="background:var(--admin-accent); color:#fff;">
                                            {{ mb_substr($specialist->name, 0, 1) }}
                                        </div>
                                        <span style="color:var(--admin-text);">{{ $specialist->name }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 persian-number" style="color:var(--admin-text-dim);">{{ $specialist->bookings_count }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="progress-bar-track" style="width:64px;">
                                            <div class="progress-bar-fill" style="width:{{ $cr }}%; background:#16A34A;"></div>
                                        </div>
                                        <span class="text-xs persian-number" style="color:var(--admin-text-dim);">{{ number_format($cr, 0) }}%</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 persian-number" style="color:var(--admin-text-dim);">{{ number_format($specialist->bookings_sum_prepayment_amount ?? 0) }}</td>
                                <td class="px-4 py-3">
                                    @if($cr >= 80)
                                        <span class="badge-status" style="background:#F0FDF4;color:#166534;">عالی</span>
                                    @elseif($cr >= 60)
                                        <span class="badge-status" style="background:#FFFBEB;color:#92400E;">خوب</span>
                                    @else
                                        <span class="badge-status" style="background:#FEF2F2;color:#991B1B;">ضعیف</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center text-sm" style="color:var(--admin-text-dim);">داده‌ای موجود نیست</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-4 py-3 text-center" style="border-top:1px solid var(--admin-border);">
                    <a href="{{ route('admin.specialists.index') }}" class="text-sm font-medium" style="color:var(--admin-accent);">مشاهده همه متخصصین</a>
                </div>
            </div>

            <div class="section-card">
                <div class="section-header">نوبت‌های اخیر</div>
                <div class="divide-y" id="recent-bookings-container" style="border-color:var(--admin-border);">
                    @forelse($recentBookings as $booking)
                        <div class="flex items-center justify-between px-4 py-3">
                            <div class="flex items-center gap-2 min-w-0">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0"
                                     style="background:var(--admin-accent-light); color:var(--admin-accent);">
                                    {{ mb_substr($booking->user->name ?? 'ن', 0, 1) }}
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium truncate" style="color:var(--admin-text);">{{ $booking->user->name ?? 'کاربر ناشناس' }}</p>
                                    <p class="text-xs truncate" style="color:var(--admin-text-dim);">{{ $booking->service->name ?? '—' }}</p>
                                </div>
                            </div>
                            <span class="badge-status flex-shrink-0 mr-2
                            @if($booking->status=='confirmed') " style="background:#EFF6FF;color:#1D4ED8;"
                            @elseif($booking->status=='completed') " style="background:#F0FDF4;color:#166534;"
                            @elseif($booking->status=='cancelled') " style="background:#FEF2F2;color:#991B1B;"
                            @else " style="background:#FFFBEB;color:#92400E;"
                            @endif>
                            @if(in_array($booking->status, ['pending','confirmed']))
                                {{ verta($booking->booking_time)->format('H:i') }}
                            @elseif($booking->status=='completed') تکمیل
                            @elseif($booking->status=='cancelled') لغو
                            @else {{ $booking->status }}
                            @endif
                            </span>
                        </div>
                    @empty
                        <p class="text-sm text-center py-8" style="color:var(--admin-text-dim);">نوبتی وجود ندارد</p>
                    @endforelse
                </div>
                <div class="px-4 py-3 text-center" style="border-top:1px solid var(--admin-border);">
                    <a href="{{ route('admin.bookings.index') }}" class="text-sm font-medium" style="color:var(--admin-accent);">مشاهده همه نوبت‌ها</a>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
            <div class="section-card">
                <div class="section-header">نقش‌های سیستم</div>
                <div class="p-4 space-y-3">
                    @forelse($roles as $role)
                        <div class="flex items-center justify-between py-2" style="border-bottom:1px solid var(--admin-border);">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-lg flex items-center justify-center"
                                     style="background:var(--admin-accent-light); color:var(--admin-accent);">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium" style="color:var(--admin-text);">{{ $role->label }}</p>
                                    <p class="text-xs" dir="ltr" style="color:var(--admin-text-light);">{{ $role->name }}</p>
                                </div>
                            </div>
                            <span class="badge-status" style="background:var(--admin-accent-light); color:var(--admin-accent);">
                            {{ $role->users_count }} کاربر
                        </span>
                        </div>
                    @empty
                        <p class="text-sm text-center py-4" style="color:var(--admin-text-dim);">نقشی تعریف نشده</p>
                    @endforelse
                </div>
                <div class="px-4 py-3 text-center" style="border-top:1px solid var(--admin-border);">
                    <a href="{{ route('admin.roles.index') }}" class="text-sm font-medium" style="color:var(--admin-accent);">مدیریت نقش‌ها</a>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {

            const initialData = @json($weeklyRevenue);
            renderChart(initialData);

            function updateTimeFilter(period) {
                ['today','week','month'].forEach(p => {
                    const btn = document.getElementById('filter-' + p);
                    btn.classList.toggle('active', p === period);
                });

                const titles = { today: 'امروز', week: '۷ روز گذشته', month: '۳۰ روز گذشته' };
                document.getElementById('revenue-chart-title').textContent = titles[period];

                document.getElementById('revenue-chart').innerHTML =
                    '<div class="chart-spinner"><div class="chart-spinner-inner"></div></div>';

                fetch('/admin/reports/' + period)
                    .then(res => {
                        if (!res.ok) throw new Error('HTTP ' + res.status);
                        return res.json();
                    })
                    .then(data => {
                        if (data.stats)          updateStats(data.stats);
                        if (data.dailyRevenue)   renderChart(data.dailyRevenue);
                        if (data.popularServices) updatePopularServices(data.popularServices);
                        if (data.recentBookings) updateRecentBookings(data.recentBookings);
                    })
                    .catch(() => {
                        document.getElementById('revenue-chart').innerHTML =
                            '<div class="chart-spinner" style="color:#991B1B;">خطا در دریافت اطلاعات. لطفاً مجدداً تلاش کنید.</div>';
                    });
            }

            window.updateTimeFilter = updateTimeFilter;

            function renderChart(data) {
                const el = document.getElementById('revenue-chart');
                if (!data || !data.length) {
                    el.innerHTML = '<div class="chart-spinner" style="color:var(--admin-text-dim);">اطلاعاتی برای نمایش وجود ندارد</div>';
                    return;
                }

                el.innerHTML = '';

                const chart = new ApexCharts(el, {
                    chart: {
                        type: 'area',
                        height: 280,
                        fontFamily: 'Vazirmatn, Vazir, sans-serif',
                        toolbar: { show: false },
                        zoom: { enabled: false },
                    },
                    series: [{
                        name: 'درآمد (تومان)',
                        data: data.map(d => d.total ?? 0),
                    }],
                    xaxis: {
                        categories: data.map(d => d.date),
                        labels: {
                            style: { fontFamily: 'Vazirmatn, Vazir, sans-serif', fontSize: '11px' },
                        },
                        axisBorder: { show: false },
                        axisTicks: { show: false },
                    },
                    yaxis: {
                        labels: {
                            formatter: val => new Intl.NumberFormat('fa-IR').format(val),
                            style: { fontFamily: 'Vazirmatn, Vazir, sans-serif', fontSize: '11px' },
                        },
                    },
                    stroke: { curve: 'smooth', width: 2 },
                    fill: {
                        type: 'gradient',
                        gradient: { shadeIntensity: 1, opacityFrom: 0.5, opacityTo: 0.05 },
                    },
                    colors: ['#334155'],
                    grid: {
                        borderColor: '#E2E8F0',
                        row: { colors: ['transparent'], opacity: 0.5 },
                    },
                    dataLabels: { enabled: false },
                    tooltip: {
                        y: { formatter: val => new Intl.NumberFormat('fa-IR').format(val) + ' تومان' },
                    },
                });
                chart.render();
            }

            function updateStats(stats) {
                const fmt = v => new Intl.NumberFormat('fa-IR').format(v);
                if (stats.todayBookingsCount !== undefined)
                    document.getElementById('today-bookings-count').textContent = fmt(stats.todayBookingsCount);
                if (stats.totalRevenue !== undefined)
                    document.getElementById('total-revenue').textContent = fmt(stats.totalRevenue);
                if (stats.usersCount !== undefined)
                    document.getElementById('users-count').textContent = fmt(stats.usersCount);
                if (stats.specialistsCount !== undefined)
                    document.getElementById('specialists-count').textContent = fmt(stats.specialistsCount);
            }

            function updatePopularServices(services) {
                const el = document.getElementById('popular-services-container');
                if (!services || !services.length) {
                    el.innerHTML = '<p class="text-sm text-center py-8" style="color:var(--admin-text-dim);">داده‌ای موجود نیست</p>';
                    return;
                }
                const max = Math.max(...services.map(s => s.bookings_count), 1);
                el.innerHTML = services.map(s => {
                    const pct = Math.min((s.bookings_count / max) * 100, 100).toFixed(0);
                    return `
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span style="color:var(--admin-text);">${s.name}</span>
                        <span style="color:var(--admin-text-dim);">${s.bookings_count} نوبت</span>
                    </div>
                    <div class="progress-bar-track">
                        <div class="progress-bar-fill" style="width:${pct}%;"></div>
                    </div>
                </div>`;
                }).join('');
            }

            function updateRecentBookings(bookings) {
                const el = document.getElementById('recent-bookings-container');
                if (!bookings || !bookings.length) {
                    el.innerHTML = '<p class="text-sm text-center py-8" style="color:var(--admin-text-dim);">نوبتی وجود ندارد</p>';
                    return;
                }

                const statusStyles = {
                    confirmed: 'background:#EFF6FF;color:#1D4ED8;',
                    completed:  'background:#F0FDF4;color:#166534;',
                    cancelled:  'background:#FEF2F2;color:#991B1B;',
                };
                const defaultStyle = 'background:#FFFBEB;color:#92400E;';

                el.innerHTML = bookings.map(b => {
                    const style = statusStyles[b.status] || defaultStyle;
                    const label = b.status === 'completed' ? 'تکمیل'
                        : b.status === 'cancelled'  ? 'لغو'
                            : (b.booking_time || '');
                    return `
                <div class="flex items-center justify-between px-4 py-3" style="border-bottom:1px solid var(--admin-border);">
                    <div class="flex items-center gap-2 min-w-0">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0"
                             style="background:var(--admin-accent-light); color:var(--admin-accent);">
                            ${(b.user_name || 'ن').charAt(0)}
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-medium truncate" style="color:var(--admin-text);">${b.user_name || 'کاربر ناشناس'}</p>
                            <p class="text-xs truncate" style="color:var(--admin-text-dim);">${b.service_name || '—'}</p>
                        </div>
                    </div>
                    <span class="badge-status flex-shrink-0 mr-2" style="${style}">${label}</span>
                </div>`;
                }).join('');
            }

            updateTimeFilter('today');
        });
    </script>
@endpush
