@extends('layouts.app')
@section('title', $specialist->name)

{{--
    ⭐ صفحه‌ی عمومی پروفایل متخصص (۲۰۲۶-۰۹-۲۴). SpecialistController::show از قبل به view
    'specialists.show' ارجاع می‌داد ولی این فایل هیچ‌وقت ساخته نشده بود — باز کردن
    /s/{slug}/specialists/{id} خطای ۵۰۰ می‌داد. حالا با عکس، خدمات، امتیاز و نظرات اخیر؛ و کارت‌های
    «متخصصین ما» صفحه‌ی اصلی بهش لینک می‌دن. شماره موبایل شخصی متخصص عمداً نمایش داده نمی‌شه.
--}}
@php
    $dayNames = \App\Support\SalonWorkingHours::DAY_NAMES;
    $schedules = $specialist->schedules->where('is_active', true)
        ->sortBy(fn ($s) => array_search((int) $s->day_of_week, \App\Support\SalonWorkingHours::WEEK_ORDER, true));
@endphp

@section('content')
    <div class="max-w-4xl mx-auto px-4 py-10" style="color: var(--rasta-cream);">
        @include('specialists.partials.nav')
        <div class="flex flex-col sm:flex-row items-center gap-6 mb-10 text-center sm:text-right">
            @if ($specialist->photoUrl())
                <img src="{{ $specialist->photoUrl() }}" alt="{{ $specialist->name }}" class="w-32 h-32 rounded-full object-cover border-2 border-[var(--rasta-gold)]/40">
            @else
                <div class="w-32 h-32 rounded-full flex items-center justify-center text-4xl font-bold" style="background: rgba(201,162,75,.15); color: var(--rasta-gold-light);">
                    {{ mb_substr($specialist->name, 0, 1) }}
                </div>
            @endif
            <div class="flex-1">
                <h1 class="text-2xl md:text-3xl font-bold mb-2" style="color: var(--rasta-gold-light);">{{ $specialist->name }}</h1>
                <p class="text-sm opacity-80 mb-3">متخصص {{ $currentSalonName }}</p>
                <div class="flex flex-wrap justify-center sm:justify-start gap-4 text-sm persian-number">
                    @if ($specialist->rating_count > 0)
                        <span><span style="color: var(--rasta-gold);">★</span> {{ to_persian_num(number_format((float) $specialist->rating_avg, 1)) }} از ۵ ({{ to_persian_num((string) $specialist->rating_count) }} نظر)</span>
                    @endif
                    @if ($specialist->completed_bookings > 0)
                        <span>{{ to_persian_num((string) $specialist->completed_bookings) }} نوبت انجام‌شده</span>
                    @endif
                </div>
            </div>
            <div class="flex flex-col gap-2">
                <a href="{{ route('bookings.create') }}" class="px-6 py-3 rounded-full font-bold text-center" style="background: var(--rasta-gold); color: #1A1410;">رزرو نوبت</a>
                <a href="{{ route('specialists.availability', $specialist) }}" class="px-6 py-2 rounded-full text-sm text-center" style="border: 1px solid rgba(201,162,75,.4); color: var(--rasta-gold-light);">تقویم نوبت‌های خالی</a>
            </div>
        </div>

        @if ($specialist->services->isNotEmpty())
            <h2 class="text-lg font-bold mb-4" style="color: var(--rasta-gold-light);">خدمات</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-10">
                @foreach ($specialist->services as $service)
                    <div class="rounded-xl p-4 flex items-center justify-between" style="background: var(--rasta-brown); border: 1px solid rgba(201,162,75,.12);">
                        <span>{{ $service->name }}</span>
                        <span class="text-sm persian-number" style="color: var(--rasta-gold);">{{ to_persian_num(number_format((int) $service->price)) }} تومان</span>
                    </div>
                @endforeach
            </div>
        @endif

        @if ($schedules->isNotEmpty())
            <h2 class="text-lg font-bold mb-4" style="color: var(--rasta-gold-light);">روزهای کاری</h2>
            <div class="flex flex-wrap gap-2 mb-10 text-sm persian-number">
                @foreach ($schedules as $schedule)
                    <span class="rounded-full px-3 py-1" style="background: var(--rasta-brown); border: 1px solid rgba(201,162,75,.15);">
                        {{ $dayNames[(int) $schedule->day_of_week] ?? '' }}: {{ to_persian_num(substr((string) $schedule->start_time, 0, 5)) }} تا {{ to_persian_num(substr((string) $schedule->end_time, 0, 5)) }}
                    </span>
                @endforeach
            </div>
        @endif

        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-bold" style="color: var(--rasta-gold-light);">نظرات اخیر مشتریان</h2>
            <a href="{{ route('reviews.specialist', $specialist) }}" class="text-sm underline" style="color: var(--rasta-gold);">همه‌ی نظرات</a>
        </div>
        @forelse ($reviews as $review)
            <div class="rounded-xl p-4 mb-3" style="background: var(--rasta-brown); border: 1px solid rgba(201,162,75,.1);">
                <div class="flex items-center justify-between mb-1 text-sm">
                    <span class="font-bold">{{ $review['user_name'] ?? 'مشتری' }}</span>
                    <span style="color: var(--rasta-gold);">{{ str_repeat('★', (int) $review['rating']) }}{{ str_repeat('☆', max(0, 5 - (int) $review['rating'])) }}</span>
                </div>
                @if ($review['review'])
                    <p class="text-sm opacity-90 leading-7">{{ $review['review'] }}</p>
                @endif
            </div>
        @empty
            <p class="text-sm opacity-70">هنوز نظری ثبت نشده است.</p>
        @endforelse
    </div>
@endsection
