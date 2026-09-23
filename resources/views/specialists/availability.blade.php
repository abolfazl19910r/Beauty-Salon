@extends('layouts.app')
@section('title', 'نوبت‌های خالی '.$specialist->name)

{{--
    ⭐ تقویم نوبت‌های خالی یک متخصص (۲۰۲۶-۰۹-۲۴) — قبلاً view نداشت (۵۰۰). داده از
    Specialist::getMonthAvailability (ماه میلادی year/month در URL)؛ هر روز با تاریخ شمسی نمایش داده
    می‌شه. همچنان پشت لاگین مشتری (routes/web/services.php) چون تعداد ظرفیت‌های خالی رو نشون می‌ده.
--}}
@php
    $start = \Carbon\Carbon::createFromDate((int) $year, (int) $month, 1)->startOfDay();
    $available = collect($availabilityData['available_days'] ?? [])->keyBy('date');
    $holidays = collect($availabilityData['holiday_days'] ?? [])->flip();
    $prev = $start->copy()->subMonth();
    $next = $start->copy()->addMonth();
    // شنبه = ستون اول: فاصله‌ی روز اول ماه تا شنبه‌ی قبلش
    $leading = ($start->dayOfWeek + 1) % 7;
    $jalaliTitle = \Morilog\Jalali\Jalalian::fromCarbon($start)->format('%B %Y').' / '.\Morilog\Jalali\Jalalian::fromCarbon($start->copy()->endOfMonth())->format('%B %Y');
@endphp

@section('content')
    <div class="max-w-3xl mx-auto px-4 py-10" style="color: var(--rasta-cream);">
        <div class="flex items-center gap-4 mb-6">
            @if ($specialist->photoUrl())
                <img src="{{ $specialist->photoUrl() }}" alt="{{ $specialist->name }}" class="w-14 h-14 rounded-full object-cover">
            @endif
            <div>
                <h1 class="text-2xl font-bold" style="color: var(--rasta-gold-light);">نوبت‌های خالی {{ $specialist->name }}</h1>
                <a href="{{ route('specialists.show', $specialist) }}" class="text-sm underline opacity-80">مشاهده‌ی پروفایل</a>
            </div>
        </div>

        <div class="flex items-center justify-between mb-4 text-sm">
            <a href="{{ route('specialists.availability', ['specialist' => $specialist, 'year' => $prev->year, 'month' => $prev->month]) }}" class="px-3 py-1 rounded-full" style="border: 1px solid rgba(201,162,75,.35);">ماه قبل</a>
            <span class="font-bold persian-number">{{ to_persian_num($jalaliTitle) }}</span>
            <a href="{{ route('specialists.availability', ['specialist' => $specialist, 'year' => $next->year, 'month' => $next->month]) }}" class="px-3 py-1 rounded-full" style="border: 1px solid rgba(201,162,75,.35);">ماه بعد</a>
        </div>

        <div class="grid grid-cols-7 gap-1 text-center text-xs mb-1 opacity-70">
            @foreach (['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'] as $d)
                <div>{{ $d }}</div>
            @endforeach
        </div>
        <div class="grid grid-cols-7 gap-1 text-center text-sm persian-number">
            @for ($i = 0; $i < $leading; $i++)
                <div></div>
            @endfor
            @for ($day = 1; $day <= $start->daysInMonth; $day++)
                @php
                    $date = $start->copy()->setDay($day);
                    $key = $date->toDateString();
                    $jDay = \Morilog\Jalali\Jalalian::fromCarbon($date)->getDay();
                    $isPast = $date->lt(today());
                    if ($isPast) {
                        [$bg, $label] = ['rgba(248,243,233,.04)', 'گذشته'];
                    } elseif ($available->has($key)) {
                        [$bg, $label] = ['rgba(34,197,94,.22)', to_persian_num((string) $available[$key]['slots_count']).' نوبت'];
                    } elseif ($holidays->has($key)) {
                        [$bg, $label] = ['rgba(239,68,68,.18)', 'تعطیل'];
                    } else {
                        [$bg, $label] = ['rgba(248,243,233,.07)', 'پر / غیرکاری'];
                    }
                @endphp
                <div class="rounded-lg py-2 px-1" style="background: {{ $bg }}; {{ $isPast ? 'opacity:.45;' : '' }}" title="{{ jalali_date($date) }}">
                    <div class="font-bold">{{ to_persian_num((string) $jDay) }}</div>
                    <div class="text-[10px] opacity-80">{{ $label }}</div>
                </div>
            @endfor
        </div>

        <div class="flex flex-wrap gap-4 mt-5 text-xs opacity-80">
            <span><span class="inline-block w-3 h-3 rounded ml-1 align-middle" style="background: rgba(34,197,94,.5);"></span>نوبت خالی</span>
            <span><span class="inline-block w-3 h-3 rounded ml-1 align-middle" style="background: rgba(239,68,68,.4);"></span>تعطیل / مرخصی</span>
            <span><span class="inline-block w-3 h-3 rounded ml-1 align-middle" style="background: rgba(248,243,233,.2);"></span>پر یا روز غیرکاری</span>
        </div>

        <div class="mt-8 text-center">
            <a href="{{ route('bookings.create') }}" class="px-6 py-3 rounded-full font-bold" style="background: var(--rasta-gold); color: #1A1410;">رزرو نوبت</a>
        </div>
    </div>
@endsection
