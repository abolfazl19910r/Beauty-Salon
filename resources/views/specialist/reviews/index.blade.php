@extends('layouts.specialist')

@section('title', 'نظرات و ارزیابی‌ها')

@push('styles')
    <style>
        .jcal-wrapper { position: relative; }
        .jcal-popup {
            display: none;
            position: absolute;
            top: calc(100% + 6px);
            right: 0;
            z-index: 9999;
            width: 280px;
            background-color: var(--specialist-surface);
            border: 1px solid var(--specialist-border);
            border-radius: 10px;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.6);
            padding: 12px;
        }
        .jcal-popup.open { display: block; }
        .jcal-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
        .jcal-header button { background: none; border: none; color: var(--specialist-text); cursor: pointer; padding: 4px 8px; border-radius: 6px; }
        .jcal-header button:hover { background-color: rgba(216,174,224,0.12); }
        .jcal-title { color: var(--specialist-plum-light); font-weight: bold; font-size: 13px; }
        .jcal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px; text-align: center; }
        .jcal-weekday { font-size: 10px; color: var(--specialist-text-dim); opacity: 0.7; padding: 4px 0; }
        .jcal-day { font-size: 12px; color: var(--specialist-text); padding: 6px 0; border-radius: 6px; cursor: pointer; }
        .jcal-day:hover { background-color: rgba(216,174,224,0.15); }
        .jcal-day.jcal-empty { cursor: default; }
        .jcal-day.jcal-empty:hover { background-color: transparent; }
        .jcal-day.jcal-selected { background-color: var(--specialist-plum-mid); color: #250D2B; font-weight: bold; }
        .jcal-day.jcal-today { border: 1px solid var(--specialist-plum-mid); }
    </style>
@endpush

@section('content')
    <div class="fade-in space-y-6">

        {{-- Stat cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="specialist-cta rounded-xl p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm mb-1 opacity-80">میانگین امتیاز</p>
                        <p class="text-3xl font-bold persian-number">{{ number_format($averageRating, 1) }}</p>
                        <p class="text-xs mt-1 opacity-70">از 5 ستاره</p>
                    </div>
                    <svg class="w-9 h-9 opacity-80" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                    </svg>
                </div>
            </div>

            <div class="specialist-card p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-[var(--specialist-plum-muted)] mb-1">تعداد نظرات</p>
                        <p class="text-2xl font-bold text-[var(--specialist-text)] persian-number">{{ number_format($stats['total']) }}</p>
                    </div>
                    <svg class="w-8 h-8 text-[var(--specialist-plum-mid)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                    </svg>
                </div>
            </div>

            <div class="specialist-card p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-[var(--specialist-plum-muted)] mb-1">نظرات 5 ستاره</p>
                        <p class="text-2xl font-bold text-emerald-300 persian-number">{{ number_format($stats['five_star']) }}</p>
                    </div>
                    <svg class="w-8 h-8 text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2 10.5a1.5 1.5 0 113 0v6a1.5 1.5 0 01-3 0v-6zM6 10.333v5.43a2 2 0 001.106 1.79l.05.025A4 4 0 008.943 18h5.416a2 2 0 001.962-1.608l1.2-6A2 2 0 0015.56 8H12V4a2 2 0 00-2-2 1 1 0 00-1 1v.667a4 4 0 01-.8 2.4L6.8 7.933a4 4 0 00-.8 2.4z" />
                    </svg>
                </div>
            </div>

            <div class="specialist-card p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-[var(--specialist-plum-muted)] mb-1">نیاز به پاسخ</p>
                        <p class="text-2xl font-bold text-amber-300 persian-number">{{ $reviews->where('specialist_response', null)->count() }}</p>
                    </div>
                    <svg class="w-8 h-8 text-amber-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        {{-- Rating distribution --}}
        <div class="specialist-card p-6">
            <h3 class="text-sm font-bold text-[var(--specialist-plum-light)] font-serif-fa mb-5 flex items-center">
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                توزیع امتیازات
            </h3>
            <div class="space-y-4">
                @foreach([5, 4, 3, 2, 1] as $rating)
                    @php
                        $count = $stats[['five_star', 'four_star', 'three_star', 'two_star', 'one_star'][$rating - 1]];
                        $percentage = $stats['total'] > 0 ? ($count / $stats['total']) * 100 : 0;
                    @endphp
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-1 w-16">
                            <span class="font-semibold text-[var(--specialist-text-dim)] persian-number">{{ $rating }}</span>
                            <svg class="w-4 h-4 text-[var(--specialist-plum-mid)]" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <div class="rating-bar">
                                <div class="rating-bar-fill" style="width: {{ $percentage }}%"></div>
                            </div>
                        </div>
                        <span class="text-sm font-medium text-[var(--specialist-text-dim)] w-12 text-left persian-number">{{ number_format($count) }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Filters --}}
        <div class="specialist-card p-6">
            <button onclick="toggleFilters()" type="button" class="w-full flex items-center justify-between text-sm font-bold text-[var(--specialist-text)]">
                <span class="flex items-center">
                    <svg class="w-5 h-5 ml-2 text-[var(--specialist-plum-mid)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    فیلتر نظرات
                </span>
                <svg id="filter-icon" class="w-5 h-5 text-[var(--specialist-inactive)] transition-transform" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <form method="GET" id="filterForm" class="hidden mt-5">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs text-[var(--specialist-plum-muted)] mb-2">امتیاز</label>
                        <select name="rating" class="w-full rounded-lg px-4 py-2 text-[var(--specialist-text)] focus:outline-none focus:ring-2 focus:ring-[var(--specialist-plum-mid)]" style="background-color: var(--specialist-bg); border: 1px solid var(--specialist-border);">
                            <option value="">همه امتیازات</option>
                            @for($i = 5; $i >= 1; $i--)
                                <option value="{{ $i }}" {{ request('rating') == $i ? 'selected' : '' }}>{{ $i }} ستاره</option>
                            @endfor
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs text-[var(--specialist-plum-muted)] mb-2">وضعیت پاسخ</label>
                        <select name="responded" class="w-full rounded-lg px-4 py-2 text-[var(--specialist-text)] focus:outline-none focus:ring-2 focus:ring-[var(--specialist-plum-mid)]" style="background-color: var(--specialist-bg); border: 1px solid var(--specialist-border);">
                            <option value="">همه</option>
                            <option value="1" {{ request('responded') === '1' ? 'selected' : '' }}>پاسخ داده شده</option>
                            <option value="0" {{ request('responded') === '0' ? 'selected' : '' }}>بدون پاسخ</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs text-[var(--specialist-plum-muted)] mb-2">مرتب‌سازی</label>
                        <select name="sort_by" class="w-full rounded-lg px-4 py-2 text-[var(--specialist-text)] focus:outline-none focus:ring-2 focus:ring-[var(--specialist-plum-mid)]" style="background-color: var(--specialist-bg); border: 1px solid var(--specialist-border);">
                            <option value="latest" {{ request('sort_by') == 'latest' ? 'selected' : '' }}>جدیدترین</option>
                            <option value="oldest" {{ request('sort_by') == 'oldest' ? 'selected' : '' }}>قدیمی‌ترین</option>
                            <option value="highest_rating" {{ request('sort_by') == 'highest_rating' ? 'selected' : '' }}>بالاترین امتیاز</option>
                            <option value="lowest_rating" {{ request('sort_by') == 'lowest_rating' ? 'selected' : '' }}>پایین‌ترین امتیاز</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs text-[var(--specialist-plum-muted)] mb-2">از تاریخ</label>
                        <div class="jcal-wrapper">
                            <input type="text" name="date_from" id="date_from" value="{{ request('date_from') }}"
                                   class="w-full rounded-lg px-4 py-2 cursor-pointer text-[var(--specialist-text)] placeholder-[var(--specialist-inactive)] focus:outline-none focus:ring-2 focus:ring-[var(--specialist-plum-mid)]"
                                   style="background-color: var(--specialist-bg); border: 1px solid var(--specialist-border);"
                                   placeholder="1403/01/01" dir="ltr" autocomplete="off" readonly>
                            <div class="jcal-popup" id="jcal-popup-date_from"></div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs text-[var(--specialist-plum-muted)] mb-2">تا تاریخ</label>
                        <div class="jcal-wrapper">
                            <input type="text" name="date_to" id="date_to" value="{{ request('date_to') }}"
                                   class="w-full rounded-lg px-4 py-2 cursor-pointer text-[var(--specialist-text)] placeholder-[var(--specialist-inactive)] focus:outline-none focus:ring-2 focus:ring-[var(--specialist-plum-mid)]"
                                   style="background-color: var(--specialist-bg); border: 1px solid var(--specialist-border);"
                                   placeholder="1403/12/29" dir="ltr" autocomplete="off" readonly>
                            <div class="jcal-popup" id="jcal-popup-date_to"></div>
                        </div>
                    </div>
                </div>

                <div class="flex gap-3 mt-6">
                    <button type="submit" class="specialist-cta px-6 py-2 rounded-lg font-bold transition-opacity hover:opacity-90">
                        اعمال فیلتر
                    </button>
                    <a href="{{ route('specialist.reviews.index') }}" class="px-6 py-2 rounded-lg font-medium text-[var(--specialist-text-dim)] hover:bg-white/5 hover:text-[var(--specialist-text)] transition" style="border: 1px solid var(--specialist-border);">
                        حذف فیلترها
                    </a>
                </div>
            </form>
        </div>

        {{-- Reviews list --}}
        <div class="space-y-4">
            @forelse($reviews as $review)
                <div class="specialist-card overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-start justify-between mb-4 flex-wrap gap-3">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 specialist-cta rounded-full flex items-center justify-center font-bold text-lg flex-shrink-0">
                                    {{ mb_substr($review->user->name, 0, 1) }}
                                </div>
                                <div>
                                    <h4 class="font-bold text-[var(--specialist-text)]">{{ $review->user->name }}</h4>
                                    <p class="text-sm text-[var(--specialist-text-dim)]">{{ $review->service->name }}</p>
                                </div>
                            </div>
                            <div class="text-left">
                                <div class="flex items-center gap-1 mb-1">
                                    @for($i = 1; $i <= 5; $i++)
                                        <svg class="w-5 h-5 {{ $i <= $review->overall_rating ? 'text-[var(--specialist-plum-light)]' : 'text-[var(--specialist-inactive)]' }}" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                        </svg>
                                    @endfor
                                </div>
                                <p class="text-xs text-[var(--specialist-plum-muted)] persian-number">{{ verta($review->reviewed_at)->format('Y/m/d') }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4 p-4 rounded-lg" style="background-color: var(--specialist-bg); border: 1px solid var(--specialist-border);">
                            <div class="text-center">
                                <p class="text-xs text-[var(--specialist-plum-muted)] mb-1">کیفیت</p>
                                <p class="font-bold text-sky-300 persian-number">{{ $review->quality_rating }}/5</p>
                            </div>
                            <div class="text-center">
                                <p class="text-xs text-[var(--specialist-plum-muted)] mb-1">رفتار</p>
                                <p class="font-bold text-emerald-300 persian-number">{{ $review->behavior_rating }}/5</p>
                            </div>
                            <div class="text-center">
                                <p class="text-xs text-[var(--specialist-plum-muted)] mb-1">تمیزی</p>
                                <p class="font-bold text-teal-300 persian-number">{{ $review->cleanliness_rating }}/5</p>
                            </div>
                            <div class="text-center">
                                <p class="text-xs text-[var(--specialist-plum-muted)] mb-1">سرعت</p>
                                <p class="font-bold text-amber-300 persian-number">{{ $review->speed_rating }}/5</p>
                            </div>
                        </div>

                        @if($review->comment)
                            <div class="mb-4 p-4 rounded-lg" style="background-color: rgba(216, 174, 224, 0.08); border: 1px solid var(--specialist-border);">
                                <p class="text-[var(--specialist-text)] leading-relaxed">{{ $review->comment }}</p>
                            </div>
                        @endif

                        @if($review->specialist_response)
                            <div class="mb-4 p-4 rounded-lg" style="background-color: var(--specialist-bg); border: 1px solid var(--specialist-border);">
                                <div class="flex items-center gap-2 mb-2">
                                    <svg class="w-4 h-4 text-[var(--specialist-plum-mid)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                    </svg>
                                    <span class="text-sm font-bold text-[var(--specialist-plum-light)]">پاسخ شما:</span>
                                </div>
                                <p class="text-[var(--specialist-text-dim)]">{{ $review->specialist_response }}</p>
                                <p class="text-xs text-[var(--specialist-plum-muted)] mt-2 persian-number">{{ verta($review->responded_at)->format('Y/m/d H:i') }}</p>
                            </div>
                        @endif

                        <div class="flex gap-3">
                            <a href="{{ route('specialist.reviews.show', $review->id) }}"
                               class="flex-1 px-4 py-2 rounded-lg font-bold text-center transition text-[var(--specialist-text-dim)] hover:bg-white/5 hover:text-[var(--specialist-text)]"
                               style="border: 1px solid var(--specialist-border);">
                                مشاهده جزئیات
                            </a>
                            @if(!$review->specialist_response)
                                <a href="{{ route('specialist.reviews.show', $review->id) }}"
                                   class="specialist-cta flex-1 px-4 py-2 rounded-lg font-bold text-center transition-opacity hover:opacity-90">
                                    پاسخ دادن
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="specialist-card p-12 text-center text-[var(--specialist-inactive)]">
                    <svg class="w-20 h-20 mx-auto mb-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                    </svg>
                    <p>هنوز نظری دریافت نکرده‌اید.</p>
                </div>
            @endforelse
        </div>

        @if($reviews->hasPages())
            <div>
                {{ $reviews->links() }}
            </div>
        @endif
    </div>

    @push('scripts')
        <script>
            (function() {
                function div(a, b) { return Math.trunc(a / b); }

                function gregorianToJalali(gy, gm, gd) {
                    const g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
                    let jy;
                    if (gy > 1600) { jy = 979; gy -= 1600; } else { jy = 0; gy -= 621; }
                    const gy2 = (gm > 2) ? (gy + 1) : gy;
                    let days = (365 * gy) + div(gy2 + 3, 4) - div(gy2 + 99, 100) + div(gy2 + 399, 400) - 80 + gd + g_d_m[gm - 1];
                    jy += 33 * div(days, 12053);
                    days %= 12053;
                    jy += 4 * div(days, 1461);
                    days %= 1461;
                    if (days > 365) { jy += div(days - 1, 365); days = (days - 1) % 365; }
                    const jm = (days < 186) ? 1 + div(days, 31) : 7 + div(days - 186, 30);
                    const jd = 1 + ((days < 186) ? (days % 31) : ((days - 186) % 30));
                    return [jy, jm, jd];
                }

                function jalaliToGregorian(jy, jm, jd) {
                    let gy;
                    if (jy > 979) { gy = 1600; jy -= 979; } else { gy = 621; }
                    let days = (365 * jy) + (div(jy, 33) * 8) + div((jy % 33) + 3, 4) + 78 + jd + ((jm < 7) ? (jm - 1) * 31 : ((jm - 7) * 30) + 186);
                    gy += 400 * div(days, 146097);
                    days %= 146097;
                    if (days > 36524) {
                        gy += 100 * div(--days, 36524);
                        days %= 36524;
                        if (days >= 365) days++;
                    }
                    gy += 4 * div(days, 1461);
                    days %= 1461;
                    if (days > 365) { gy += div(days - 1, 365); days = (days - 1) % 365; }
                    const gd = days + 1;
                    const isLeap = (gy % 4 === 0 && gy % 100 !== 0) || (gy % 400 === 0);
                    const sal_a = [0, 31, isLeap ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
                    let gm = 0, remaining = gd;
                    for (gm = 1; gm <= 12; gm++) {
                        if (remaining <= sal_a[gm]) break;
                        remaining -= sal_a[gm];
                    }
                    return [gy, gm, remaining];
                }

                const jMonths = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
                const jWeekdays = ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'];

                function jalaliMonthLength(jy, jm) {
                    if (jm <= 6) return 31;
                    if (jm <= 11) return 30;
                    const g1 = jalaliToGregorian(jy, jm, 29);
                    const g2 = jalaliToGregorian(jy + 1, 1, 1);
                    const d1 = new Date(g1[0], g1[1] - 1, g1[2]);
                    const d2 = new Date(g2[0], g2[1] - 1, g2[2]);
                    const diffDays = Math.round((d2 - d1) / 86400000);
                    return 28 + diffDays;
                }

                function buildCalendar(input, popup) {
                    const today = new Date();
                    const [tjy, tjm, tjd] = gregorianToJalali(today.getFullYear(), today.getMonth() + 1, today.getDate());

                    let viewYear = tjy, viewMonth = tjm;
                    let selectedValue = input.value || '';

                    if (selectedValue.match(/^\d{4}\/\d{1,2}\/\d{1,2}$/)) {
                        const parts = selectedValue.split('/').map(Number);
                        viewYear = parts[0];
                        viewMonth = parts[1];
                    }

                    function render() {
                        const firstDayGregorian = jalaliToGregorian(viewYear, viewMonth, 1);
                        const firstDate = new Date(firstDayGregorian[0], firstDayGregorian[1] - 1, firstDayGregorian[2]);
                        const jsDay = firstDate.getDay();
                        const startOffset = (jsDay + 1) % 7;

                        const monthLength = jalaliMonthLength(viewYear, viewMonth);

                        let html = '<div class="jcal-header">';
                        html += '<button type="button" data-nav="prev">&#9658;</button>';
                        html += '<span class="jcal-title persian-number">' + jMonths[viewMonth - 1] + ' ' + viewYear + '</span>';
                        html += '<button type="button" data-nav="next">&#9664;</button>';
                        html += '</div>';
                        html += '<div class="jcal-grid">';
                        jWeekdays.forEach(w => { html += '<div class="jcal-weekday">' + w + '</div>'; });

                        for (let i = 0; i < startOffset; i++) {
                            html += '<div class="jcal-day jcal-empty"></div>';
                        }
                        for (let d = 1; d <= monthLength; d++) {
                            const isToday = (viewYear === tjy && viewMonth === tjm && d === tjd);
                            const dayValue = viewYear + '/' + String(viewMonth).padStart(2, '0') + '/' + String(d).padStart(2, '0');
                            const isSelected = (dayValue === selectedValue);
                            html += '<div class="jcal-day persian-number' + (isToday ? ' jcal-today' : '') + (isSelected ? ' jcal-selected' : '') + '" data-day="' + d + '">' + d + '</div>';
                        }
                        html += '</div>';
                        popup.innerHTML = html;

                        popup.querySelector('[data-nav="prev"]').addEventListener('click', function(e) {
                            e.stopPropagation();
                            viewMonth--;
                            if (viewMonth < 1) { viewMonth = 12; viewYear--; }
                            render();
                        });
                        popup.querySelector('[data-nav="next"]').addEventListener('click', function(e) {
                            e.stopPropagation();
                            viewMonth++;
                            if (viewMonth > 12) { viewMonth = 1; viewYear++; }
                            render();
                        });
                        popup.querySelectorAll('.jcal-day[data-day]').forEach(function(el) {
                            el.addEventListener('click', function(e) {
                                e.stopPropagation();
                                const d = parseInt(this.dataset.day, 10);
                                selectedValue = viewYear + '/' + String(viewMonth).padStart(2, '0') + '/' + String(d).padStart(2, '0');
                                input.value = selectedValue;
                                popup.classList.remove('open');
                                input.dispatchEvent(new Event('change'));
                            });
                        });
                    }

                    render();
                }

                function initCalendar(inputId, popupId) {
                    const input = document.getElementById(inputId);
                    const popup = document.getElementById(popupId);
                    if (!input || !popup) return;

                    input.addEventListener('click', function(e) {
                        e.stopPropagation();
                        document.querySelectorAll('.jcal-popup.open').forEach(p => { if (p !== popup) p.classList.remove('open'); });
                        buildCalendar(input, popup);
                        popup.classList.add('open');
                    });
                }

                document.addEventListener('DOMContentLoaded', function() {
                    initCalendar('date_from', 'jcal-popup-date_from');
                    initCalendar('date_to', 'jcal-popup-date_to');

                    document.addEventListener('click', function() {
                        document.querySelectorAll('.jcal-popup.open').forEach(p => p.classList.remove('open'));
                    });
                });
            })();

            function toggleFilters() {
                const form = document.getElementById('filterForm');
                const icon = document.getElementById('filter-icon');
                form.classList.toggle('hidden');
                icon.classList.toggle('rotate-180');
            }
        </script>
    @endpush
@endsection
