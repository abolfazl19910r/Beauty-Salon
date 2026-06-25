@extends('layouts.specialist')

@section('title', 'پروفایل من')

@php
    $statusBadgeMap = [
        'pending'         => ['label' => 'در انتظار تایید', 'class' => 'bg-amber-400/10 text-amber-300'],
        'confirmed'       => ['label' => 'تایید شده',       'class' => 'bg-emerald-400/10 text-emerald-300'],
        'completed'       => ['label' => 'انجام شده',        'class' => 'bg-[var(--specialist-plum-mid)]/15 text-[var(--specialist-plum-light)]'],
        'cancelled'       => ['label' => 'لغو شده',          'class' => 'bg-red-500/10 text-red-300'],
        'pending_payment' => ['label' => 'در انتظار پرداخت', 'class' => 'bg-orange-400/10 text-orange-300'],
    ];
@endphp

@section('content')
    <div class="fade-in max-w-7xl mx-auto space-y-6">

        <div class="flex justify-between items-center">
            <h1 class="text-xl font-bold text-[var(--specialist-text)] font-serif-fa">پروفایل من</h1>
            <a href="{{ route('specialist.profile.edit') }}"
               class="specialist-cta px-4 py-2 rounded-lg transition-opacity hover:opacity-90 flex items-center text-sm font-bold">
                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
                ویرایش پروفایل
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Personal info --}}
            <div class="lg:col-span-1 space-y-6">
                <div class="specialist-card overflow-hidden">
                    <div class="p-5 border-b flex items-center gap-3" style="border-color: var(--specialist-border);">
                        <div class="w-12 h-12 rounded-full specialist-cta flex items-center justify-center text-lg font-bold flex-shrink-0">
                            {{ mb_substr($user->name ?? 'متخصص', 0, 1) }}
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-[var(--specialist-text)]">{{ $user->name }}</h2>
                            @if($specialist)
                                <span class="specialist-badge inline-block px-3 py-0.5 text-xs font-medium mt-1">متخصص</span>
                            @endif
                        </div>
                    </div>

                    <div class="p-5 space-y-4">
                        <div>
                            <label class="block text-xs text-[var(--specialist-plum-muted)] mb-1">نام</label>
                            <div class="text-[var(--specialist-text)] font-medium">{{ $user->name }}</div>
                        </div>

                        <div>
                            <label class="block text-xs text-[var(--specialist-plum-muted)] mb-1">شماره تماس</label>
                            <div class="text-[var(--specialist-text)] font-medium persian-number" dir="ltr">{{ $user->phone }}</div>
                        </div>

                        @if($user->email)
                            <div>
                                <label class="block text-xs text-[var(--specialist-plum-muted)] mb-1">ایمیل</label>
                                <div class="text-[var(--specialist-text)] font-medium" dir="ltr">{{ $user->email }}</div>
                            </div>
                        @endif

                        @if($specialist && $specialist->specialty)
                            <div class="pt-4 border-t" style="border-color: var(--specialist-border);">
                                <label class="block text-xs text-[var(--specialist-plum-muted)] mb-1">تخصص</label>
                                <div class="text-[var(--specialist-text)]">{{ $specialist->specialty }}</div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Personal booking stats (as a customer) --}}
                <div class="grid grid-cols-3 gap-3">
                    <div class="specialist-card p-4 text-center">
                        <p class="text-xs text-[var(--specialist-plum-muted)] mb-1">کل نوبت‌ها</p>
                        <p class="text-xl font-bold text-[var(--specialist-text)] persian-number">{{ number_format($totalBookings) }}</p>
                    </div>
                    <div class="specialist-card p-4 text-center">
                        <p class="text-xs text-[var(--specialist-plum-muted)] mb-1">انجام شده</p>
                        <p class="text-xl font-bold text-[var(--specialist-plum-light)] persian-number">{{ number_format($completedBookings) }}</p>
                    </div>
                    <div class="specialist-card p-4 text-center">
                        <p class="text-xs text-[var(--specialist-plum-muted)] mb-1">لغو شده</p>
                        <p class="text-xl font-bold text-red-300 persian-number">{{ number_format($cancelledBookings) }}</p>
                    </div>
                </div>
            </div>

            {{-- Bookings --}}
            <div class="lg:col-span-2 space-y-6">
                @if($upcomingBookings->count() > 0)
                    <div class="specialist-card overflow-hidden">
                        <div class="p-5 border-b" style="border-color: var(--specialist-border);">
                            <h2 class="text-sm font-bold text-[var(--specialist-plum-light)] font-serif-fa">نوبت‌های آینده من</h2>
                        </div>

                        <div class="p-5 space-y-3">
                            @foreach($upcomingBookings as $booking)
                                @php
                                    $upStatus = $statusBadgeMap[$booking->status] ?? ['label' => 'نامشخص', 'class' => 'bg-white/5 text-[var(--specialist-text-dim)]'];
                                @endphp
                                <div class="specialist-card p-4" style="background-color: var(--specialist-bg);">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <h3 class="font-semibold text-[var(--specialist-text)] mb-2">{{ $booking->service?->name ?? '—' }}</h3>
                                            <div class="space-y-1 text-sm text-[var(--specialist-text-dim)]">
                                                <p class="flex items-center gap-1.5">
                                                    <svg class="w-4 h-4 text-[var(--specialist-inactive)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                                    متخصص: {{ $booking->specialist?->name ?? '—' }}
                                                </p>
                                                <p class="flex items-center gap-1.5 persian-number">
                                                    <svg class="w-4 h-4 text-[var(--specialist-inactive)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                                    {{ $booking->booking_date_persian }}
                                                </p>
                                                <p class="flex items-center gap-1.5 persian-number" dir="ltr">
                                                    <svg class="w-4 h-4 text-[var(--specialist-inactive)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                    {{ \Carbon\Carbon::parse($booking->booking_time)->format('H:i') }}
                                                </p>
                                            </div>
                                        </div>

                                        <div class="text-left">
                                            <span class="px-3 py-1 rounded-full text-xs font-medium {{ $upStatus['class'] }}">{{ $upStatus['label'] }}</span>
                                            <div class="mt-2 text-sm font-bold text-[var(--specialist-text)] persian-number">
                                                {{ number_format($booking->prepayment_amount) }} تومان
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="specialist-card overflow-hidden">
                    <div class="p-5 border-b" style="border-color: var(--specialist-border);">
                        <h2 class="text-sm font-bold text-[var(--specialist-plum-light)] font-serif-fa">تاریخچه نوبت‌های من</h2>
                    </div>

                    <div class="p-5">
                        @if($myBookings->isEmpty())
                            <div class="text-center py-12 text-[var(--specialist-inactive)]">
                                <svg class="w-14 h-14 mx-auto mb-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                </svg>
                                <p class="mb-4">شما هنوز نوبتی رزرو نکرده‌اید</p>
                                <a href="{{ route('services.index') }}"
                                   class="text-[var(--specialist-plum-mid)] hover:text-[var(--specialist-plum-light)] transition-colors inline-flex items-center">
                                    <span>مشاهده لیست خدمات</span>
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <line x1="5" y1="12" x2="19" y2="12"></line>
                                        <polyline points="12 5 19 12 12 19"></polyline>
                                    </svg>
                                </a>
                            </div>
                        @else
                            <div class="space-y-3">
                                @foreach($myBookings as $booking)
                                    @php
                                        $histStatus = $statusBadgeMap[$booking->status] ?? ['label' => 'نامشخص', 'class' => 'bg-white/5 text-[var(--specialist-text-dim)]'];
                                    @endphp
                                    <div class="rounded-lg p-4" style="border: 1px solid var(--specialist-border);">
                                        <div class="flex justify-between items-start">
                                            <div class="flex-1">
                                                <h3 class="font-semibold text-[var(--specialist-text)]">{{ $booking->service?->name ?? '—' }}</h3>
                                                <div class="mt-2 space-y-1 text-sm text-[var(--specialist-text-dim)]">
                                                    <p class="flex items-center gap-1.5">
                                                        <svg class="w-4 h-4 text-[var(--specialist-inactive)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                                        {{ $booking->specialist?->name ?? '—' }}
                                                    </p>
                                                    <p class="flex items-center gap-1.5 persian-number" dir="ltr">
                                                        <span dir="rtl" class="flex items-center gap-1.5">
                                                            <svg class="w-4 h-4 text-[var(--specialist-inactive)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                                            {{ $booking->booking_date_persian }}
                                                        </span>
                                                        - {{ \Carbon\Carbon::parse($booking->booking_time)->format('H:i') }}
                                                    </p>
                                                </div>
                                            </div>

                                            <div class="text-left">
                                                <span class="px-3 py-1 rounded-full text-xs font-medium {{ $histStatus['class'] }}">{{ $histStatus['label'] }}</span>
                                                <div class="mt-2 text-sm font-bold text-[var(--specialist-text)] persian-number">
                                                    {{ number_format($booking->prepayment_amount) }} تومان
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="mt-4">
                                {{ $myBookings->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
