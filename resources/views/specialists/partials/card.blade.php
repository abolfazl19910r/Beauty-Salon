{{--
    ⭐ کارت عمومی متخصص (۲۰۲۶-۰۹-۲۴) — مشترک بین جستجو، برترین‌ها و متخصص‌های یک خدمت.
    ورودی: $specialist (با فیلدهای اختیاری bookings_avg_rating / rating_count / completed_bookings
    بسته به کوئری صفحه). شماره موبایل شخصی متخصص عمداً نمایش داده نمی‌شه.
--}}
@php
    $avg = $specialist->bookings_avg_rating ?? null;
    $ratingCount = $specialist->rating_count ?? ($specialist->total_ratings ?? null);
@endphp
<div class="rounded-2xl p-5 flex flex-col items-center text-center" style="background: var(--rasta-brown); border: 1px solid rgba(201,162,75,.12);">
    @if ($specialist->photoUrl())
        <img src="{{ $specialist->photoUrl() }}" alt="{{ $specialist->name }}" loading="lazy" class="w-24 h-24 rounded-full object-cover mb-3 border-2 border-[var(--rasta-gold)]/40">
    @else
        <div class="w-24 h-24 rounded-full flex items-center justify-center text-3xl font-bold mb-3" style="background: rgba(201,162,75,.15); color: var(--rasta-gold-light);">
            {{ mb_substr($specialist->name, 0, 1) }}
        </div>
    @endif
    <h3 class="font-bold text-lg mb-1">
        <a href="{{ route('specialists.show', $specialist) }}" class="hover:text-[var(--rasta-gold-light)]">{{ $specialist->name }}</a>
    </h3>
    <div class="text-sm mb-4 persian-number" style="color: var(--rasta-cream); opacity: .8; min-height: 1.5rem;">
        @if ($avg)
            <span style="color: var(--rasta-gold);">★</span> {{ to_persian_num(number_format((float) $avg, 1)) }}
            @if ($ratingCount) <span class="opacity-70">({{ to_persian_num((string) $ratingCount) }} امتیاز)</span> @endif
        @endif
        @if (! empty($specialist->completed_bookings))
            <span class="opacity-70 mr-2">{{ to_persian_num((string) $specialist->completed_bookings) }} نوبت انجام‌شده</span>
        @endif
    </div>
    <div class="flex gap-2 mt-auto text-sm">
        <a href="{{ route('specialists.show', $specialist) }}" class="px-4 py-2 rounded-full" style="border: 1px solid rgba(201,162,75,.4); color: var(--rasta-gold-light);">پروفایل</a>
        <a href="{{ route('bookings.create') }}" class="px-4 py-2 rounded-full font-bold" style="background: var(--rasta-gold); color: #1A1410;">رزرو</a>
    </div>
</div>
