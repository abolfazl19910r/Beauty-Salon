@extends('layouts.specialist')

@section('title', 'داشبورد')

@php
    $statusLabels = [
        'pending'   => 'در انتظار تایید',
        'confirmed' => 'تایید شده',
        'completed' => 'انجام شده',
        'cancelled' => 'لغو شده', 'no_show' => 'نیامد',
    ];

    $nextBooking = $todaySchedule->first() ?: $upcomingBookings->first();
    $nextBookingIsToday = $todaySchedule->isNotEmpty()
        && $nextBooking
        && $todaySchedule->first()->id === $nextBooking->id;
@endphp

@section('content')
    <div class="fade-in space-y-6">

        {{-- Welcome header --}}
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-full specialist-cta flex items-center justify-center text-lg font-bold flex-shrink-0">
                {{ mb_substr(optional(auth()->user())->name ?? 'متخصص', 0, 1) }}
            </div>
            <div>
                <h2 class="text-lg font-bold text-[var(--specialist-text)]">سلام، {{ auth()->user()->name ?? 'متخصص' }}</h2>
                <p class="text-sm text-[var(--specialist-plum-muted)] persian-number">{{ $todayPersian }}</p>
            </div>
        </div>

        {{-- 3 key stat cards --}}
        <div class="grid grid-cols-3 gap-3">
            <div class="specialist-card p-4 text-center">
                <p class="text-xs text-[var(--specialist-plum-muted)] mb-1">نوبت امروز</p>
                <p class="text-xl font-bold text-[var(--specialist-text)] persian-number">{{ number_format($todayBookingsCount) }}</p>
            </div>
            <div class="specialist-card p-4 text-center">
                <p class="text-xs text-[var(--specialist-plum-muted)] mb-1">درآمد ماه</p>
                <p class="text-xl font-bold text-[var(--specialist-text)] persian-number">{{ number_format($monthRevenue) }}</p>
            </div>
            <div class="specialist-card p-4 text-center">
                <p class="text-xs text-[var(--specialist-plum-muted)] mb-1">امتیاز</p>
                <p class="text-xl font-bold text-[var(--specialist-text)] persian-number">{{ number_format($averageRating, 1) }}</p>
            </div>
        </div>

        {{-- Next appointment --}}
        <div class="specialist-card p-5 border" style="border-color: var(--specialist-border);">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-bold text-[var(--specialist-plum-light)] font-serif-fa">نوبت بعدی</h3>
                @if($nextBooking)
                    <span class="specialist-badge px-3 py-1 text-xs font-medium">{{ $statusLabels[$nextBooking->status] ?? 'نامشخص' }}</span>
                @endif
            </div>

            @if($nextBooking)
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-semibold text-[var(--specialist-text)]">{{ $nextBooking->user->name }}</p>
                        <p class="text-sm text-[var(--specialist-text-dim)]">{{ $nextBooking->service->name }}</p>
                    </div>
                    <div class="text-left">
                        <p class="font-bold text-[var(--specialist-plum-mid)] persian-number" dir="ltr">{{ \Carbon\Carbon::parse($nextBooking->booking_time)->format('H:i') }}</p>
                        <p class="text-xs text-[var(--specialist-text-dim)] persian-number">{{ $nextBookingIsToday ? 'امروز' : $nextBooking->booking_date_persian }}</p>
                    </div>
                </div>
            @else
                <p class="text-center text-[var(--specialist-text-dim)] py-3">نوبتی برای نمایش وجود ندارد</p>
            @endif
        </div>

        {{-- Main CTA --}}
        <a href="{{ route('specialist.bookings.index') }}" class="specialist-cta w-full flex items-center justify-center gap-2 py-3 rounded-xl font-bold transition-opacity hover:opacity-90">
            مدیریت نوبت‌ها
        </a>

        {{-- Overall stats --}}
        <div>
            <div class="flex items-center mb-4">
                <h2 class="text-base font-bold text-[var(--specialist-plum-light)] font-serif-fa">آمار کل</h2>
                <div class="flex-grow mr-4 h-px" style="background-color: var(--specialist-border);"></div>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="specialist-card p-5">
                    <p class="text-sm text-[var(--specialist-plum-muted)] mb-1">کل نوبت‌ها</p>
                    <h3 class="text-2xl font-bold text-[var(--specialist-text)] persian-number">{{ number_format($allBookingsCount) }}</h3>
                </div>
                <div class="specialist-card p-5">
                    <p class="text-sm text-[var(--specialist-plum-muted)] mb-1">تایید شده</p>
                    <h3 class="text-2xl font-bold text-m-ok persian-number">{{ number_format($confirmedBookingsCount) }}</h3>
                </div>
                <div class="specialist-card p-5">
                    <p class="text-sm text-[var(--specialist-plum-muted)] mb-1">در انتظار تایید</p>
                    <h3 class="text-2xl font-bold text-m-warn persian-number">{{ number_format($pendingBookingsCount) }}</h3>
                </div>
                <div class="specialist-card p-5">
                    <p class="text-sm text-[var(--specialist-plum-muted)] mb-1">انجام شده</p>
                    <h3 class="text-2xl font-bold text-[var(--specialist-plum-light)] persian-number">{{ number_format($completedBookingsCount) }}</h3>
                </div>
            </div>
        </div>

        {{-- Today's schedule + upcoming + reviews --}}
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <div class="xl:col-span-2 specialist-card overflow-hidden">
                <div class="p-5 border-b" style="border-color: var(--specialist-border);">
                    <h2 class="text-base font-semibold text-[var(--specialist-text)]">برنامه کاری امروز</h2>
                </div>
                <div class="p-5">
                    @if($todaySchedule->count() > 0)
                        <div class="space-y-4">
                            @foreach($todaySchedule as $booking)
                                <div class="flex justify-between items-start border-b pb-3 {{ $booking->status === 'no_show' ? 'opacity-60' : '' }}" style="border-color: var(--specialist-border);" data-today-booking="{{ $booking->id }}">
                                    <div>
                                        <p class="font-semibold text-[var(--specialist-text)]">{{ $booking->user->name }}</p>
                                        <p class="text-sm text-[var(--specialist-text-dim)]">{{ $booking->service?->name ?? '—' }} @if ($__courseLabel = $booking->courseSessionLabel())<span class="text-xs mr-1 px-2 py-0.5 rounded-full" style="background:rgb(var(--m-gold) / .15); color:rgb(var(--m-gold2));">{{ $__courseLabel }}</span>@endif</p>
                                        {{-- پرداخت و مبلغ دریافتی در سالن (۲۰۲۶-۱۰-۲۲) --}}
                                        @unless ($booking->customer_course_id)
                                            <p class="text-xs mt-1 persian-number" data-payment="{{ $booking->payment_status === 'paid' ? 'paid' : 'unpaid' }}">
                                                @if ($booking->payment_status === 'paid')
                                                    <span style="color: rgb(var(--m-ok));">پیش‌پرداخت شده</span>
                                                @else
                                                    <span style="color: rgb(var(--m-warn));">پیش‌پرداخت نشده</span>
                                                @endif
                                                @if ($booking->due_at_salon > 0 && $booking->status !== 'no_show')
                                                    <span class="text-[var(--specialist-text-dim)]"> · دریافت در سالن: {{ number_format($booking->due_at_salon) }} تومان</span>
                                                @endif
                                            </p>
                                        @endunless
                                    </div>
                                    <div class="text-left">
                                        <p class="font-bold text-[var(--specialist-plum-mid)] persian-number" dir="ltr">{{ \Carbon\Carbon::parse($booking->booking_time)->format('H:i') }}</p>
                                        <span class="specialist-badge px-2 py-0.5 text-xs">{{ $statusLabels[$booking->status] ?? 'نامشخص' }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-10 text-[var(--specialist-inactive)]">
                            <svg class="w-12 h-12 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <p class="mt-4">برای امروز نوبتی ندارید</p>
                        </div>
                    @endif

                    @if($todayCancelled->isNotEmpty())
                        {{-- لغوشده‌های امروز، جمع‌شده (۲۰۲۶-۱۰-۲۲) --}}
                        @php $cancelledBy = ['customer' => 'مشتری', 'specialist' => 'خودتان', 'admin' => 'مدیر سالن', 'system' => 'سیستم (پرداخت نشد)']; @endphp
                        <details class="mt-5 pt-4 border-t" style="border-color: var(--specialist-border);" data-today-cancelled>
                            <summary class="cursor-pointer text-sm text-[var(--specialist-plum-muted)] persian-number">لغوشده‌های امروز ({{ number_format($todayCancelled->count()) }})</summary>
                            <div class="space-y-3 mt-3">
                                @foreach($todayCancelled as $booking)
                                    <a href="{{ route('specialist.bookings.show', $booking) }}" class="flex justify-between items-start text-sm" data-cancelled-booking="{{ $booking->id }}">
                                        <div>
                                            <p class="text-[var(--specialist-text)]">{{ $booking->user?->name ?? '—' }} <span class="text-[var(--specialist-text-dim)]">— {{ $booking->service?->name ?? '—' }}</span></p>
                                            <p class="text-xs text-[var(--specialist-text-dim)]">لغو توسط {{ $cancelledBy[$booking->cancelled_by] ?? 'نامشخص' }}@if($booking->cancellation_reason) · {{ \Illuminate\Support\Str::limit($booking->cancellation_reason, 60) }}@endif</p>
                                        </div>
                                        <span class="font-bold text-[var(--specialist-plum-muted)] persian-number" dir="ltr">{{ $booking->booking_time->format('H:i') }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </details>
                    @endif
                </div>
            </div>

            <div class="space-y-6">
                <div class="specialist-card overflow-hidden">
                    <div class="p-5 border-b" style="border-color: var(--specialist-border);">
                        <h2 class="text-base font-semibold text-[var(--specialist-text)]">۷ روز آینده</h2>
                    </div>
                    <div class="p-5">
                        @if($upcomingBookings->count() > 0)
                            <div class="space-y-3">
                                @foreach($upcomingBookings->take(5) as $booking)
                                    <div class="flex justify-between items-start border-b pb-3" style="border-color: var(--specialist-border);">
                                        <div>
                                            <p class="font-semibold text-sm text-[var(--specialist-text)] persian-number">{{ $booking->booking_date_persian }}</p>
                                            <p class="text-xs text-[var(--specialist-text-dim)] persian-number" dir="ltr">{{ \Carbon\Carbon::parse($booking->booking_time)->format('H:i') }} - {{ $booking->user->name }}</p>
                                            <p class="text-xs text-[var(--specialist-text-dim)]">{{ $booking->service?->name ?? '—' }}</p>
                                        </div>
                                        <span class="specialist-badge px-2 py-1 text-xs">{{ $booking->status_fa }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-center text-[var(--specialist-inactive)] py-4">نوبتی در ۷ روز آینده ندارید</p>
                        @endif
                    </div>
                </div>

                <div class="specialist-card overflow-hidden">
                    <div class="p-5 border-b" style="border-color: var(--specialist-border);">
                        <h2 class="text-base font-semibold text-[var(--specialist-text)]">نظرات اخیر</h2>
                    </div>
                    <div class="p-5">
                        @if($recentReviews->count() > 0)
                            <div class="space-y-4">
                                @foreach($recentReviews as $review)
                                    <div class="border-b pb-3" style="border-color: var(--specialist-border);">
                                        <div class="flex justify-between items-start mb-2">
                                            <p class="font-semibold text-sm text-[var(--specialist-text)]">{{ $review->user->name }}</p>
                                            <div class="flex text-m-warn">
                                                @for($i = 1; $i <= 5; $i++)
                                                    @if($i <= $review->rating)
                                                        <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20"><path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/></svg>
                                                    @else
                                                        <svg class="w-4 h-4 text-[var(--specialist-inactive)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                                                    @endif
                                                @endfor
                                            </div>
                                        </div>
                                        <p class="text-sm text-[var(--specialist-text-dim)]">{{ \Illuminate\Support\Str::limit($review->review, 100) }}</p>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-center text-[var(--specialist-inactive)] py-4">هنوز نظری ثبت نشده</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Revenue chart --}}
        <div class="specialist-card overflow-hidden">
            <div class="p-5 border-b" style="border-color: var(--specialist-border);">
                <h2 class="text-base font-semibold text-[var(--specialist-text)]">نمودار درآمد (۷ روز گذشته)</h2>
            </div>
            <div class="p-5">
                <div id="revenue-chart" class="h-80"></div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/vendor/apexcharts.js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // بسته‌ی ه: رنگ نمودار از توکن تم؛ با عوض شدن تم صفحه دوباره بار می‌شود تا نمودار هم تم را بگیرد
            document.addEventListener('click', e => { if (e.target.closest && e.target.closest('[data-m-theme-toggle]') && document.querySelector('.apexcharts-canvas')) setTimeout(() => location.reload(), 0); });
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
                        fontFamily: 'Mahru, Vazirmatn, sans-serif',
                        toolbar: { show: false },
                        background: 'transparent'
                    },
                    colors: [window.mToken('gold')],
                    dataLabels: { enabled: false },
                    stroke: { curve: 'smooth', width: 2 },
                    fill: {
                        type: 'gradient',
                        gradient: {
                            shadeIntensity: 1,
                            colorStops: [
                                { offset: 0, color: window.mToken('gold'), opacity: 0.45 },
                                { offset: 100, color: window.mToken('bg2'), opacity: 0.05 }
                            ]
                        }
                    },
                    grid: {
                        borderColor: window.mToken('tx', .08)
                    },
                    xaxis: {
                        categories: categories,
                        labels: { style: { fontFamily: 'Mahru, Vazirmatn, sans-serif', colors: window.mToken('mu') } },
                        axisBorder: { color: window.mToken('tx', .12) },
                        axisTicks: { color: window.mToken('tx', .12) }
                    },
                    yaxis: {
                        labels: {
                            formatter: (val) => new Intl.NumberFormat('fa-IR').format(val),
                            style: { fontFamily: 'Mahru, Vazirmatn, sans-serif', colors: window.mToken('mu') }
                        }
                    },
                    tooltip: {
                        theme: document.documentElement.dataset.theme === 'light' ? 'light' : 'dark',
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
