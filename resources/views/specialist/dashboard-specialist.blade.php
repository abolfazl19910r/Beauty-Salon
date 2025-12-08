@extends('layouts.admin')

@section('title', 'پنل من')

@section('content')
    <div class="fade-in">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 mb-2">پنل من</h1>
                <p class="text-sm text-gray-500">خوش آمدید {{ $specialist->name }}</p>
            </div>

            <a href="{{ route('specialist.profile.show') }}"
               class="bg-gradient-to-r from-blue-500 to-purple-600 text-white px-4 py-2 rounded-lg hover:opacity-90 transition-opacity flex items-center">
                <svg class="w-5 h-5 ml-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                پروفایل من
            </a>
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
                        <h3 class="text-2xl font-bold text-gray-800">{{ $todayBookingsCount }}</h3>
                    </div>
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
                        <p class="text-sm text-gray-500">درآمد امروز</p>
                        <h3 class="text-2xl font-bold text-gray-800">{{ number_format($todayRevenue) }}</h3>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden">
                <div class="p-5 flex items-center gap-4">
                    <div class="bg-purple-50 text-purple-600 rounded-lg p-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">نوبت‌های این ماه</p>
                        <h3 class="text-2xl font-bold text-gray-800">{{ $monthBookingsCount }}</h3>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden">
                <div class="p-5 flex items-center gap-4">
                    <div class="bg-yellow-50 text-yellow-600 rounded-lg p-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">امتیاز من</p>
                        <h3 class="text-2xl font-bold text-gray-800">{{ number_format($averageRating, 1) }} / 5</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-6">
            <div class="xl:col-span-2 bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden">
                <div class="p-5 border-b border-gray-100">
                    <h2 class="text-lg font-semibold text-gray-800">برنامه کاری امروز</h2>
                </div>
                <div class="p-5">
                    @if($todaySchedule->count() > 0)
                        <div class="space-y-4">
                            @foreach($todaySchedule as $booking)
                                <div class="flex items-start gap-4 p-4 rounded-lg {{ $booking->status === 'completed' ? 'bg-gray-50' : 'bg-blue-50' }} hover:shadow-sm transition">
                                    <div class="flex-shrink-0 text-center">
                                        <div class="text-2xl font-bold text-blue-600">
                                            {{ \Carbon\Carbon::parse($booking->booking_time)->format('H:i') }}
                                        </div>
                                        <div class="text-xs text-gray-500">{{ $booking->service->duration }} دقیقه</div>
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="font-semibold text-gray-800">{{ $booking->service->name }}</h3>
                                        <p class="text-sm text-gray-600 mt-1">
                                            <i class="fas fa-user"></i> {{ $booking->user->name }}
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            <i class="fas fa-phone"></i> {{ $booking->user->phone }}
                                        </p>
                                        @if($booking->user_notes)
                                            <p class="text-sm text-blue-600 mt-2">
                                                <i class="fas fa-comment"></i> {{ $booking->user_notes }}
                                            </p>
                                        @endif
                                    </div>
                                    <div>
                                        @if($booking->status === 'confirmed')
                                            <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-medium">تایید شده</span>
                                        @elseif($booking->status === 'completed')
                                            <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">انجام شده</span>
                                        @else
                                            <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-medium">در انتظار</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-12">
                            <svg class="mx-auto h-16 w-16 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <p class="mt-4 text-gray-500">برای امروز نوبتی ندارید</p>
                        </div>
                    @endif
                </div>
            </div>

            <div class="space-y-6">
                <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden">
                    <div class="p-5 border-b border-gray-100">
                        <h2 class="text-lg font-semibold text-gray-800">7 روز آینده</h2>
                    </div>
                    <div class="p-5">
                        @if($upcomingBookings->count() > 0)
                            <div class="space-y-3">
                                @foreach($upcomingBookings->take(5) as $booking)
                                    <div class="flex justify-between items-start border-b pb-3">
                                        <div>
                                            <p class="font-semibold text-sm">{{ $booking->booking_date_persian }}</p>
                                            <p class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($booking->booking_time)->format('H:i') }} - {{ $booking->user->name }}</p>
                                            <p class="text-xs text-gray-600">{{ $booking->service->name }}</p>
                                        </div>
                                        <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs">{{ $booking->status }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-center text-gray-500 py-4">نوبتی در 7 روز آینده ندارید</p>
                        @endif
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden">
                    <div class="p-5 border-b border-gray-100">
                        <h2 class="text-lg font-semibold text-gray-800">نظرات اخیر</h2>
                    </div>
                    <div class="p-5">
                        @if($recentReviews->count() > 0)
                            <div class="space-y-4">
                                @foreach($recentReviews as $review)
                                    <div class="border-b pb-3">
                                        <div class="flex justify-between items-start mb-2">
                                            <p class="font-semibold text-sm">{{ $review->user->name }}</p>
                                            <div class="flex text-yellow-400">
                                                @for($i = 1; $i <= 5; $i++)
                                                    @if($i <= $review->rating)
                                                        <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20"><path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/></svg>
                                                    @else
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                                                    @endif
                                                @endfor
                                            </div>
                                        </div>
                                        <p class="text-sm text-gray-600">{{ Str::limit($review->review, 100) }}</p>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-center text-gray-500 py-4">هنوز نظری ثبت نشده</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden">
            <div class="p-5 border-b border-gray-100">
                <h2 class="text-lg font-semibold text-gray-800">نمودار درآمد (7 روز گذشته)</h2>
            </div>
            <div class="p-5">
                <div id="revenue-chart" class="h-80"></div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const weeklyRevenue = @json($weeklyRevenue);

            if (weeklyRevenue && weeklyRevenue.length > 0) {
                const categories = weeklyRevenue.map(item => item.date);
                const series = [{
                    name: 'درآمد',
                    data: weeklyRevenue.map(item => parseInt(item.total))
                }];

                const options = {
                    chart: {
                        type: 'area',
                        height: 320,
                        fontFamily: 'Vazir, sans-serif',
                        toolbar: { show: false }
                    },
                    colors: ['#3B82F6'],
                    dataLabels: { enabled: false },
                    stroke: { curve: 'smooth', width: 2 },
                    fill: {
                        type: 'gradient',
                        gradient: {
                            shadeIntensity: 1,
                            opacityFrom: 0.7,
                            opacityTo: 0.3
                        }
                    },
                    xaxis: {
                        categories: categories,
                        labels: { style: { fontFamily: 'Vazir, sans-serif' } }
                    },
                    yaxis: {
                        labels: {
                            formatter: (val) => new Intl.NumberFormat('fa-IR').format(val),
                            style: { fontFamily: 'Vazir, sans-serif' }
                        }
                    },
                    tooltip: {
                        y: {
                            formatter: (val) => new Intl.NumberFormat('fa-IR').format(val) + ' تومان'
                        }
                    }
                };

                const chart = new ApexCharts(document.getElementById('revenue-chart'), { series, ...options });
                chart.render();
            }
        });
    </script>
@endpush
