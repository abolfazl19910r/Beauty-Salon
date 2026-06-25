@extends('layouts.app')

@section('title', 'داشبورد')

@section('content')
    <div class="max-w-7xl mx-auto fade-in space-y-8">

        {{-- خوش‌آمدگویی --}}
        <div class="rounded-xl p-6" style="background: linear-gradient(135deg, var(--rasta-brown), var(--rasta-dark)); border: 1px solid rgba(201,162,75,0.2);">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div>
                    <h1 class="text-2xl font-bold" style="color: var(--rasta-gold-light);">
                        سلام، {{ auth()->user()->name }}
                    </h1>
                    <p class="mt-1 text-sm persian-number" style="color: var(--rasta-cream); opacity: 0.7;">
                        {{ \Morilog\Jalali\Jalalian::forge(now())->format('l، j F Y') }}
                    </p>
                </div>
                <a href="{{ route('services.index') }}"
                   class="px-5 py-2.5 rounded-xl font-bold transition-opacity hover:opacity-90 flex items-center gap-2"
                   style="background: linear-gradient(135deg, var(--rasta-gold-light), var(--rasta-gold)); color: var(--rasta-dark);">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    رزرو نوبت جدید
                </a>
            </div>
        </div>

        {{-- اعلانات --}}
        @if($announcements->count() > 0)
            <div class="space-y-3">
                @foreach($announcements as $announcement)
                    <div class="rounded-xl p-4 flex items-start gap-3"
                         style="background-color: rgba(201,162,75,0.08); border: 1px solid rgba(201,162,75,0.25);">
                        <svg class="w-5 h-5 flex-shrink-0 mt-0.5" style="color: var(--rasta-gold);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        <div>
                            <p class="text-sm font-semibold" style="color: var(--rasta-gold-light);">{{ $announcement->title }}</p>
                            <p class="text-xs mt-1" style="color: var(--rasta-cream); opacity: 0.75;">{{ $announcement->content }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- نوبت‌های اخیر --}}
            <div class="lg:col-span-2">
                <div class="rounded-xl overflow-hidden" style="background-color: var(--rasta-brown); border: 1px solid rgba(201,162,75,0.2);">
                    <div class="p-5 border-b flex justify-between items-center" style="border-color: rgba(201,162,75,0.15);">
                        <h2 class="font-bold flex items-center gap-2" style="color: var(--rasta-gold-light);">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            نوبت‌های اخیر
                        </h2>
                        <a href="{{ route('bookings.index') }}" class="text-sm transition hover:opacity-80" style="color: var(--rasta-gold);">مشاهده همه</a>
                    </div>

                    @if($userBookings->isEmpty())
                        <div class="p-10 text-center">
                            <p class="text-sm mb-4" style="color: var(--rasta-cream); opacity: 0.5;">هنوز نوبتی رزرو نکرده‌اید</p>
                            <a href="{{ route('services.index') }}"
                               class="px-5 py-2 rounded-lg text-sm font-bold transition-opacity hover:opacity-90"
                               style="background: linear-gradient(135deg, var(--rasta-gold-light), var(--rasta-gold)); color: var(--rasta-dark);">
                                رزرو اولین نوبت
                            </a>
                        </div>
                    @else
                        <div class="divide-y" style="border-color: rgba(201,162,75,0.1);">
                            @foreach($userBookings as $booking)
                                @php
                                    $statusMap = [
                                        'pending'         => ['label' => 'در انتظار تایید', 'color' => '#F5C56B'],
                                        'confirmed'       => ['label' => 'تایید شده',       'color' => '#6FCF97'],
                                        'completed'       => ['label' => 'انجام شده',       'color' => 'var(--rasta-gold-light)'],
                                        'cancelled'       => ['label' => 'لغو شده',         'color' => '#E08989'],
                                        'pending_payment' => ['label' => 'در انتظار پرداخت','color' => '#F5A623'],
                                    ];
                                    $statusInfo = $statusMap[$booking->status] ?? ['label' => $booking->status, 'color' => 'var(--rasta-cream)'];
                                @endphp
                                <div class="p-4 flex items-center justify-between flex-wrap gap-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0"
                                             style="background-color: rgba(201,162,75,0.12);">
                                            <svg class="w-5 h-5" style="color: var(--rasta-gold);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium" style="color: var(--rasta-cream);">{{ $booking->service?->name ?? '—' }}</p>
                                            <p class="text-xs persian-number" style="color: var(--rasta-cream); opacity: 0.6;">
                                                {{ \Morilog\Jalali\Jalalian::forge($booking->booking_time)->format('Y/m/d H:i') }}
                                                @if($booking->specialist)
                                                    — {{ $booking->specialist?->name ?? '—' }}
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="px-3 py-1 rounded-full text-xs font-medium"
                                              style="background-color: rgba(201,162,75,0.1); color: {{ $statusInfo['color'] }};">
                                            {{ $statusInfo['label'] }}
                                        </span>
                                        @if($booking->canBeRescheduled())
                                            <a href="{{ route('bookings.reschedule', $booking->id) }}"
                                               class="text-xs transition hover:opacity-80" style="color: var(--rasta-gold);">تغییر زمان</a>
                                        @endif
                                        <a href="{{ route('bookings.show', $booking->id) }}"
                                           class="text-xs transition hover:opacity-80" style="color: var(--rasta-cream); opacity: 0.7;">جزئیات</a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- نوبت‌های آینده --}}
                @if($upcomingBookings->count() > 0)
                    <div class="mt-6 rounded-xl overflow-hidden" style="background-color: var(--rasta-brown); border: 1px solid rgba(201,162,75,0.2);">
                        <div class="p-5 border-b" style="border-color: rgba(201,162,75,0.15);">
                            <h2 class="font-bold flex items-center gap-2" style="color: var(--rasta-gold-light);">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                نوبت‌های پیش رو
                            </h2>
                        </div>
                        <div class="divide-y" style="border-color: rgba(201,162,75,0.1);">
                            @foreach($upcomingBookings as $booking)
                                <div class="p-4 flex items-center justify-between flex-wrap gap-3">
                                    <div>
                                        <p class="text-sm font-medium" style="color: var(--rasta-cream);">{{ $booking->service?->name ?? '—' }}</p>
                                        <p class="text-xs persian-number mt-1" style="color: var(--rasta-gold-light); opacity: 0.8;">
                                            {{ \Morilog\Jalali\Jalalian::forge($booking->booking_time)->format('l، j F Y — H:i') }}
                                        </p>
                                        @if($booking->specialist)
                                            <p class="text-xs mt-0.5" style="color: var(--rasta-cream); opacity: 0.6;">متخصص: {{ $booking->specialist?->name ?? '—' }}</p>
                                        @endif
                                    </div>
                                    <a href="{{ route('bookings.show', $booking->id) }}"
                                       class="px-3 py-1.5 rounded-lg text-xs font-medium transition-opacity hover:opacity-90"
                                       style="background: linear-gradient(135deg, var(--rasta-gold-light), var(--rasta-gold)); color: var(--rasta-dark);">
                                        مشاهده
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- ستون کنار: توصیه‌ها + متخصصان برتر --}}
            <div class="space-y-6">

                {{-- خدمات پیشنهادی --}}
                @if($recommendations->count() > 0)
                    <div class="rounded-xl overflow-hidden" style="background-color: var(--rasta-brown); border: 1px solid rgba(201,162,75,0.2);">
                        <div class="p-5 border-b" style="border-color: rgba(201,162,75,0.15);">
                            <h2 class="font-bold flex items-center gap-2" style="color: var(--rasta-gold-light);">
                                <svg class="w-5 h-5" style="color: var(--rasta-gold);" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                                پیشنهاد برای شما
                            </h2>
                        </div>
                        <div class="p-4 space-y-3">
                            @foreach($recommendations as $service)
                                <a href="{{ route('services.show', $service->id) }}"
                                   class="flex items-center justify-between p-3 rounded-lg transition hover:bg-white/5">
                                    <div>
                                        <p class="text-sm font-medium" style="color: var(--rasta-cream);">{{ $service->name }}</p>
                                        @if($service->price)
                                            <p class="text-xs persian-number mt-0.5" style="color: var(--rasta-gold); opacity: 0.85;">{{ number_format($service->price) }} تومان</p>
                                        @endif
                                    </div>
                                    <svg class="w-4 h-4 flex-shrink-0" style="color: var(--rasta-gold); opacity: 0.5;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                                    </svg>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- متخصصان برتر --}}
                @if($topSpecialists->count() > 0)
                    <div class="rounded-xl overflow-hidden" style="background-color: var(--rasta-brown); border: 1px solid rgba(201,162,75,0.2);">
                        <div class="p-5 border-b" style="border-color: rgba(201,162,75,0.15);">
                            <h2 class="font-bold flex items-center gap-2" style="color: var(--rasta-gold-light);">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                متخصصان برتر
                            </h2>
                        </div>
                        <div class="p-4 space-y-3">
                            @foreach($topSpecialists as $specialist)
                                <div class="flex items-center gap-3 p-3 rounded-lg">
                                    <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold flex-shrink-0"
                                         style="background: linear-gradient(135deg, var(--rasta-gold-light), var(--rasta-gold)); color: var(--rasta-dark);">
                                        {{ mb_substr($specialist->name, 0, 1) }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium truncate" style="color: var(--rasta-cream);">{{ $specialist->name }}</p>
                                        @if($specialist->bookings_avg_rating)
                                            <div class="flex items-center gap-1 mt-0.5">
                                                <svg class="w-3 h-3" style="color: var(--rasta-gold);" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                </svg>
                                                <span class="text-xs persian-number" style="color: var(--rasta-gold-light);">{{ number_format($specialist->bookings_avg_rating, 1) }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- دسترسی سریع --}}
                <div class="rounded-xl p-5" style="background-color: var(--rasta-brown); border: 1px solid rgba(201,162,75,0.2);">
                    <h2 class="font-bold mb-4 flex items-center gap-2" style="color: var(--rasta-gold-light);">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h7"/>
                        </svg>
                        دسترسی سریع
                    </h2>
                    <div class="grid grid-cols-2 gap-3">
                        <a href="{{ route('bookings.index') }}"
                           class="p-3 rounded-lg text-center transition hover:bg-white/5"
                           style="border: 1px solid rgba(201,162,75,0.2);">
                            <svg class="w-6 h-6 mx-auto mb-1" style="color: var(--rasta-gold);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <p class="text-xs" style="color: var(--rasta-cream); opacity: 0.8;">نوبت‌هایم</p>
                        </a>
                        <a href="{{ route('wallet.index') }}"
                           class="p-3 rounded-lg text-center transition hover:bg-white/5"
                           style="border: 1px solid rgba(201,162,75,0.2);">
                            <svg class="w-6 h-6 mx-auto mb-1" style="color: var(--rasta-gold);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                            </svg>
                            <p class="text-xs" style="color: var(--rasta-cream); opacity: 0.8;">کیف پول</p>
                        </a>
                        <a href="{{ route('profile.show') }}"
                           class="p-3 rounded-lg text-center transition hover:bg-white/5"
                           style="border: 1px solid rgba(201,162,75,0.2);">
                            <svg class="w-6 h-6 mx-auto mb-1" style="color: var(--rasta-gold);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <p class="text-xs" style="color: var(--rasta-cream); opacity: 0.8;">پروفایل</p>
                        </a>
                        <a href="{{ route('loyalty.index') }}"
                           class="p-3 rounded-lg text-center transition hover:bg-white/5"
                           style="border: 1px solid rgba(201,162,75,0.2);">
                            <svg class="w-6 h-6 mx-auto mb-1" style="color: var(--rasta-gold);" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                            <p class="text-xs" style="color: var(--rasta-cream); opacity: 0.8;">امتیازها</p>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
