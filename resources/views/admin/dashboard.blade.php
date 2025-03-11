@extends('layouts.admin')

@section('title', 'پنل مدیریت')

@section('content')
    <div class="fade-in">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 mb-2">داشبورد</h1>
                <p class="text-sm text-gray-500">خلاصه وضعیت سیستم مدیریت سالن زیبایی</p>
            </div>
            <div class="mt-4 md:mt-0">
                <div class="bg-white p-2 rounded-lg shadow-sm flex gap-2 text-sm">
                    <button id="filter-today" class="px-4 py-1.5 bg-blue-50 text-blue-600 rounded-md font-medium">امروز</button>
                    <button id="filter-week" class="px-4 py-1.5 text-gray-600 hover:bg-gray-50 rounded-md">هفته</button>
                    <button id="filter-month" class="px-4 py-1.5 text-gray-600 hover:bg-gray-50 rounded-md">ماه</button>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden">
                <div class="p-5 flex items-center gap-4">
                    <div class="bg-blue-50 text-blue-600 rounded-lg p-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">نوبت‌های امروز</p>
                        <div class="flex items-center gap-2">
                            <h3 class="text-2xl font-bold text-gray-800" id="today-bookings-count">{{ $todayBookingsCount }}</h3>
                            <span class="text-xs bg-blue-100 text-blue-800 px-1.5 py-0.5 rounded-full">
                                <span class="font-medium">+12.5%</span>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="border-t border-gray-100 px-5 py-3">
                    <a href="{{ route('admin.bookings.index') }}" class="text-xs text-blue-600 hover:text-blue-800 flex items-center justify-between">
                        <span>مشاهده همه</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </a>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden">
                <div class="p-5 flex items-center gap-4">
                    <div class="bg-green-50 text-green-600 rounded-lg p-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">درآمد کل</p>
                        <div class="flex items-center gap-2">
                            <h3 class="text-2xl font-bold text-gray-800" dir="ltr" id="total-revenue">{{ number_format($totalRevenue) }}</h3>
                            <span class="text-xs bg-green-100 text-green-800 px-1.5 py-0.5 rounded-full">
                                <span class="font-medium" id="revenue-change">+5.2%</span>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="border-t border-gray-100 px-5 py-3">
                    <a href="{{ route('admin.reports.index') }}" class="text-xs text-green-600 hover:text-green-800 flex items-center justify-between">
                        <span>گزارش مالی</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </a>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden">
                <div class="p-5 flex items-center gap-4">
                    <div class="bg-purple-50 text-purple-600 rounded-lg p-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">کاربران</p>
                        <div class="flex items-center gap-2">
                            <h3 class="text-2xl font-bold text-gray-800" id="users-count">{{ $usersCount }}</h3>
                            <span class="text-xs bg-purple-100 text-purple-800 px-1.5 py-0.5 rounded-full">
                                <span class="font-medium" id="users-change">+3.8%</span>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="border-t border-gray-100 px-5 py-3">
                    <a href="{{ route('admin.users.index') }}" class="text-xs text-purple-600 hover:text-purple-800 flex items-center justify-between">
                        <span>مشاهده همه</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </a>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden">
                <div class="p-5 flex items-center gap-4">
                    <div class="bg-pink-50 text-pink-600 rounded-lg p-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">متخصصین</p>
                        <div class="flex items-center gap-2">
                            <h3 class="text-2xl font-bold text-gray-800">{{ $specialistsCount }}</h3>
                        </div>
                    </div>
                </div>
                <div class="border-t border-gray-100 px-5 py-3">
                    <a href="{{ route('admin.specialists.index') }}" class="text-xs text-pink-600 hover:text-pink-800 flex items-center justify-between">
                        <span>مشاهده همه</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <div class="xl:col-span-2 bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden">
                <div class="p-5 border-b border-gray-100">
                    <h2 class="text-lg font-semibold text-gray-800">نمودار درآمد (<span id="revenue-chart-title">۷ روز گذشته</span>)</h2>
                </div>
                <div class="p-5">
                    <div id="revenue-chart" class="h-80">
                        <div class="flex justify-center items-center h-full">
                            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden">
                <div class="p-5 border-b border-gray-100">
                    <h2 class="text-lg font-semibold text-gray-800">خدمات محبوب</h2>
                </div>
                <div class="p-5">
                    <div class="space-y-4" id="popular-services-container">
                        @foreach($popularServices as $service)
                            <div class="flex items-center">
                                <div class="w-10 h-10 flex-shrink-0 bg-blue-100 text-blue-500 rounded-lg flex items-center justify-center ml-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-sm font-medium">{{ $service->name }}</h3>
                                    <div class="flex items-center">
                                        <div class="w-full bg-gray-200 rounded-full h-1.5">
                                            <div class="bg-blue-600 h-1.5 rounded-full" style="width: {{ min(($service->bookings_count / max($popularServices->max('bookings_count'), 1)) * 100, 100) }}%"></div>
                                        </div>
                                        <span class="mr-2 text-sm text-gray-500">{{ number_format(min(($service->bookings_count / max($popularServices->max('bookings_count'), 1)) * 100, 100), 0) }}%</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="xl:col-span-2 bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden">
                <div class="p-5 border-b border-gray-100">
                    <h2 class="text-lg font-semibold text-gray-800">آمار متخصصین</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                        <tr class="text-sm text-gray-600 bg-gray-50">
                            <th class="px-5 py-3 text-right font-medium">متخصص</th>
                            <th class="px-5 py-3 text-right font-medium">نوبت‌های امروز</th>
                            <th class="px-5 py-3 text-right font-medium">نرخ تکمیل</th>
                            <th class="px-5 py-3 text-right font-medium">درآمد</th>
                            <th class="px-5 py-3 text-right font-medium">عملکرد</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100" id="specialists-table-body">
                        @foreach($topSpecialists as $specialist)
                            <tr class="text-sm hover:bg-gray-50 transition-colors">
                                <td class="px-5 py-4">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 rounded-full bg-blue-500 text-white flex items-center justify-center text-xs font-bold ml-2">
                                            {{ substr($specialist->name, 0, 1) }}
                                        </div>
                                        <span class="font-medium">{{ $specialist->name }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4">{{ $specialist->bookings_count }}</td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center">
                                        <div class="w-16 bg-gray-200 rounded-full h-1.5 ml-2">
                                            @php
                                                $completionRate = 0;
                                                if ($specialist->bookings_count > 0) {
                                                    $completedBookings = $specialist->bookings()->where('status', 'completed')->count();
                                                    $completionRate = ($completedBookings / $specialist->bookings_count) * 100;
                                                }
                                            @endphp
                                            <div class="bg-green-600 h-1.5 rounded-full" style="width: {{ $completionRate }}%"></div>
                                        </div>
                                        <span>{{ number_format($completionRate, 0) }}%</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4">{{ number_format($specialist->bookings_sum_prepayment_amount ?? 0) }}</td>
                                <td class="px-5 py-4">
                                    @if($completionRate >= 80)
                                        <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">عالی</span>
                                    @elseif($completionRate >= 60)
                                        <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-medium">خوب</span>
                                    @else
                                        <span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs font-medium">ضعیف</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-t border-gray-100 text-center">
                    <a href="{{ route('admin.specialists.index') }}" class="text-sm text-blue-600 hover:text-blue-800">مشاهده همه متخصصین</a>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden">
                <div class="p-5 border-b border-gray-100">
                    <h2 class="text-lg font-semibold text-gray-800">نوبت‌های اخیر</h2>
                </div>
                <div class="p-5 space-y-5" id="recent-bookings-container">
                    @foreach($recentBookings as $booking)
                        <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                            <div class="flex items-center">
                                <span class="w-8 h-8 rounded-full bg-green-500 text-white flex items-center justify-center text-xs font-bold ml-2">{{ substr($booking->user->name ?? 'ن', 0, 1) }}</span>
                                <div>
                                    <h3 class="text-sm font-medium">{{ $booking->user->name ?? 'کاربر ناشناس' }}</h3>
                                    <p class="text-xs text-gray-500">{{ $booking->service->name ?? 'خدمت نامشخص' }}</p>
                                </div>
                            </div>
                            <span class="px-2 py-1 @if($booking->status == 'confirmed') bg-blue-100 text-blue-800 @elseif($booking->status == 'completed') bg-green-100 text-green-800 @elseif($booking->status == 'cancelled') bg-red-100 text-red-800 @else bg-yellow-100 text-yellow-800 @endif rounded-full text-xs font-medium">
                            @if($booking->status == 'pending' || $booking->status == 'confirmed')
                                    {{ verta($booking->booking_time)->format('H:i') }}
                                @elseif($booking->status == 'completed')
                                    تکمیل شده
                                @elseif($booking->status == 'cancelled')
                                    لغو شده
                                @else
                                    {{ $booking->status }}
                                @endif
                        </span>
                        </div>
                    @endforeach
                </div>
                <div class="p-4 border-t border-gray-100 text-center">
                    <a href="{{ route('admin.bookings.index') }}" class="text-sm text-blue-600 hover:text-blue-800">مشاهده همه نوبت‌ها</a>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mt-6">
            <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden">
                <div class="p-5 border-b border-gray-100">
                    <h2 class="text-lg font-semibold text-gray-800">نقش‌های سیستم</h2>
                </div>
                <div class="p-5 space-y-4">
                    @foreach($roles as $role)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 flex-shrink-0 bg-indigo-100 text-indigo-500 rounded-lg flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-sm font-medium">{{ $role->label }}</h3>
                                    <p class="text-xs text-gray-500" dir="ltr">{{ $role->name }}</p>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 bg-indigo-100 text-indigo-800 rounded-full text-xs font-medium">
                                {{ $role->users_count }} کاربر
                            </span>
                        </div>
                    @endforeach
                </div>
                <div class="p-4 border-t border-gray-100 text-center">
                    <a href="{{ route('admin.roles.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">مدیریت نقش‌ها</a>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // داده‌های اولیه نمودار
            const initialRevenueData = @json($weeklyRevenue);

            renderRevenueChart(initialRevenueData);

            document.getElementById('filter-today').addEventListener('click', () => {
                updateTimeFilter('today');
            });

            document.getElementById('filter-week').addEventListener('click', () => {
                updateTimeFilter('week');
            });

            document.getElementById('filter-month').addEventListener('click', () => {
                updateTimeFilter('month');
            });

            function updateTimeFilter(period) {
                document.getElementById('filter-today').classList.remove('bg-blue-50', 'text-blue-600');
                document.getElementById('filter-week').classList.remove('bg-blue-50', 'text-blue-600');
                document.getElementById('filter-month').classList.remove('bg-blue-50', 'text-blue-600');

                document.getElementById(`filter-${period}`).classList.add('bg-blue-50', 'text-blue-600');

                document.getElementById('revenue-chart').innerHTML = `
                    <div class="flex justify-center items-center h-full">
                        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
                    </div>
                `;

                let chartTitle = '';
                switch(period) {
                    case 'today':
                        chartTitle = 'امروز';
                        break;
                    case 'week':
                        chartTitle = '۷ روز گذشته';
                        break;
                    case 'month':
                        chartTitle = '۳۰ روز گذشته';
                        break;
                }
                document.getElementById('revenue-chart-title').textContent = chartTitle;

                fetch(`/admin/reports/${period}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.stats) {
                            updateStats(data.stats);
                        }

                        if (data.dailyRevenue) {
                            renderRevenueChart(data.dailyRevenue);
                        }

                        if (data.popularServices) {
                            updatePopularServices(data.popularServices);
                        }

                        if (data.specialists) {
                            updateSpecialists(data.specialists);
                        }

                        if (data.recentBookings) {
                            updateRecentBookings(data.recentBookings);
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching dashboard data:', error);
                        document.getElementById('revenue-chart').innerHTML = `
                            <div class="flex justify-center items-center h-full">
                                <div class="text-red-500">خطا در دریافت اطلاعات. لطفاً مجدداً تلاش کنید.</div>
                            </div>
                        `;
                    });
            }

            function renderRevenueChart(data) {
                if (!data || !data.length) {
                    document.getElementById('revenue-chart').innerHTML = `
                        <div class="flex justify-center items-center h-full">
                            <p class="text-gray-500">اطلاعاتی برای نمایش وجود ندارد</p>
                        </div>
                    `;
                    return;
                }

                const categories = data.map(item => item.date);
                const series = [{
                    name: 'درآمد',
                    data: data.map(item => parseInt(item.total))
                }];

                const options = {
                    chart: {
                        type: 'area',
                        height: 320,
                        fontFamily: 'Vazir, sans-serif',
                        toolbar: {
                            show: false
                        },
                        zoom: {
                            enabled: false
                        }
                    },
                    dataLabels: {
                        enabled: false
                    },
                    stroke: {
                        curve: 'smooth',
                        width: 2
                    },
                    fill: {
                        type: 'gradient',
                        gradient: {
                            shadeIntensity: 1,
                            opacityFrom: 0.7,
                            opacityTo: 0.3
                        }
                    },
                    colors: ['#3B82F6'],
                    grid: {
                        borderColor: '#f1f1f1',
                        row: {
                            colors: ['transparent', 'transparent'],
                            opacity: 0.5
                        }
                    },
                    xaxis: {
                        categories: categories,
                        labels: {
                            style: {
                                fontFamily: 'Vazir, sans-serif'
                            }
                        },
                        axisBorder: {
                            show: false
                        },
                        axisTicks: {
                            show: false
                        }
                    },
                    yaxis: {
                        labels: {
                            formatter: function (val) {
                                return new Intl.NumberFormat('fa-IR').format(val);
                            },
                            style: {
                                fontFamily: 'Vazir, sans-serif'
                            }
                        }
                    },
                    tooltip: {
                        x: {
                            format: 'dd/MM/yy'
                        },
                        y: {
                            formatter: function (val) {
                                return new Intl.NumberFormat('fa-IR').format(val) + ' تومان';
                            }
                        }
                    },
                    legend: {
                        position: 'top',
                        horizontalAlign: 'right',
                        offsetY: -30,
                        fontFamily: 'Vazir, sans-serif'
                    }
                };

                document.getElementById('revenue-chart').innerHTML = '';
                const chart = new ApexCharts(document.getElementById('revenue-chart'), {
                    series: series,
                    ...options
                });

                chart.render();
            }

            function updateStats(stats) {
                document.getElementById('today-bookings-count').textContent = stats.todayBookingsCount;
                document.getElementById('total-revenue').textContent = new Intl.NumberFormat('fa-IR').format(stats.totalRevenue);
                document.getElementById('users-count').textContent = stats.usersCount;

                if (stats.revenueChange !== undefined) {
                    const revenueChangeEl = document.getElementById('revenue-change');
                    revenueChangeEl.textContent = (stats.revenueChange > 0 ? '+' : '') + stats.revenueChange + '%';

                    if (stats.revenueChange > 0) {
                        revenueChangeEl.parentElement.classList.remove('bg-red-100', 'text-red-800');
                        revenueChangeEl.parentElement.classList.add('bg-green-100', 'text-green-800');
                    } else if (stats.revenueChange < 0) {
                        revenueChangeEl.parentElement.classList.remove('bg-green-100', 'text-green-800');
                        revenueChangeEl.parentElement.classList.add('bg-red-100', 'text-red-800');
                    }
                }

                if (stats.usersChange !== undefined) {
                    const usersChangeEl = document.getElementById('users-change');
                    usersChangeEl.textContent = (stats.usersChange > 0 ? '+' : '') + stats.usersChange + '%';

                    if (stats.usersChange > 0) {
                        usersChangeEl.parentElement.classList.remove('bg-red-100', 'text-red-800');
                        usersChangeEl.parentElement.classList.add('bg-purple-100', 'text-purple-800');
                    } else if (stats.usersChange < 0) {
                        usersChangeEl.parentElement.classList.remove('bg-purple-100', 'text-purple-800');
                        usersChangeEl.parentElement.classList.add('bg-red-100', 'text-red-800');
                    }
                }
            }

            function updatePopularServices(services) {
                if (!services || !services.length) {
                    document.getElementById('popular-services-container').innerHTML = `
                        <div class="text-center py-8">
                            <p class="text-gray-500">اطلاعاتی برای نمایش وجود ندارد</p>
                        </div>
                    `;
                    return;
                }

                const maxBookings = Math.max(...services.map(service => service.bookings_count));

                let html = '';
                services.forEach(service => {
                    const percentage = Math.min((service.bookings_count / maxBookings) * 100, 100);

                    html += `
                        <div class="flex items-center">
                            <div class="w-10 h-10 flex-shrink-0 bg-blue-100 text-blue-500 rounded-lg flex items-center justify-center ml-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-sm font-medium">${service.name}</h3>
                                <div class="flex items-center">
                                    <div class="w-full bg-gray-200 rounded-full h-1.5">
                                        <div class="bg-blue-600 h-1.5 rounded-full" style="width: ${percentage}%"></div>
                                    </div>
                                    <span class="mr-2 text-sm text-gray-500">${Math.round(percentage)}%</span>
                                </div>
                            </div>
                        </div>
                    `;
                });

                document.getElementById('popular-services-container').innerHTML = html;
            }

            function updateSpecialists(specialists) {
                if (!specialists || !specialists.length) {
                    document.getElementById('specialists-table-body').innerHTML = `
                        <tr>
                            <td colspan="5" class="px-5 py-8 text-center text-gray-500">
                                اطلاعاتی برای نمایش وجود ندارد
                            </td>
                        </tr>
                    `;
                    return;
                }

                let html = '';
                specialists.forEach(specialist => {
                    let completionRate = 0;
                    if (specialist.bookings_count > 0) {
                        const completedBookings = specialist.completed_bookings || 0;
                        completionRate = (completedBookings / specialist.bookings_count) * 100;
                    }

                    let performanceStatus = '';
                    if (completionRate >= 80) {
                        performanceStatus = '<span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">عالی</span>';
                    } else if (completionRate >= 60) {
                        performanceStatus = '<span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-medium">خوب</span>';
                    } else {
                        performanceStatus = '<span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs font-medium">ضعیف</span>';
                    }

                    html += `
                        <tr class="text-sm hover:bg-gray-50 transition-colors">
                            <td class="px-5 py-4">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 rounded-full bg-blue-500 text-white flex items-center justify-center text-xs font-bold ml-2">
                                        ${specialist.name.substr(0, 1)}
                                    </div>
                                    <span class="font-medium">${specialist.name}</span>
                                </div>
                            </td>
                            <td class="px-5 py-4">${specialist.today_bookings || 0}</td>
                            <td class="px-5 py-4">
                                <div class="flex items-center">
                                    <div class="w-16 bg-gray-200 rounded-full h-1.5 ml-2">
                                        <div class="bg-green-600 h-1.5 rounded-full" style="width: ${completionRate}%"></div>
                                    </div>
                                    <span>${Math.round(completionRate)}%</span>
                                </div>
                            </td>
                            <td class="px-5 py-4">${new Intl.NumberFormat('fa-IR').format(specialist.revenue || 0)}</td>
                            <td class="px-5 py-4">${performanceStatus}</td>
                        </tr>
                    `;
                });

                document.getElementById('specialists-table-body').innerHTML = html;
            }

            function updateRecentBookings(bookings) {
                if (!bookings || !bookings.length) {
                    document.getElementById('recent-bookings-container').innerHTML = `
                        <div class="text-center py-8">
                            <p class="text-gray-500">نوبتی برای نمایش وجود ندارد</p>
                        </div>
                    `;
                    return;
                }

                let html = '';
                bookings.forEach(booking => {
                    let statusClass = '';
                    let statusText = '';

                    switch(booking.status) {
                        case 'confirmed':
                            statusClass = 'bg-blue-100 text-blue-800';
                            statusText = booking.booking_time ? new Date(booking.booking_time).toLocaleTimeString('fa-IR', {hour: '2-digit', minute: '2-digit'}) : '';
                            break;
                        case 'completed':
                            statusClass = 'bg-green-100 text-green-800';
                            statusText = 'تکمیل شده';
                            break;
                        case 'cancelled':
                            statusClass = 'bg-red-100 text-red-800';
                            statusText = 'لغو شده';
                            break;
                        default:
                            statusClass = 'bg-yellow-100 text-yellow-800';
                            statusText = booking.booking_time ? new Date(booking.booking_time).toLocaleTimeString('fa-IR', {hour: '2-digit', minute: '2-digit'}) : '';
                    }

                    html += `
                        <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                            <div class="flex items-center">
                                <span class="w-8 h-8 rounded-full bg-green-500 text-white flex items-center justify-center text-xs font-bold ml-2">
                                    ${(booking.user?.name || 'ن').substr(0, 1)}
                                </span>
                                <div>
                                    <h3 class="text-sm font-medium">${booking.user?.name || 'کاربر ناشناس'}</h3>
                                    <p class="text-xs text-gray-500">${booking.service?.name || 'خدمت نامشخص'}</p>
                                </div>
                            </div>
                            <span class="px-2 py-1 ${statusClass} rounded-full text-xs font-medium">
                                ${statusText}
                            </span>
                        </div>
                    `;
                });

                document.getElementById('recent-bookings-container').innerHTML = html;
            }
        });
    </script>
@endpush
