@extends('layouts.specialist')

@section('title', 'داشبورد')

{{--
    بسته‌ی ۳ ظاهری (۲۰۲۶-۱۱-۲۵، پیش‌نمایش SpAfterDash/SpAfterMobile): «نوبت بعدی» بالای صفحه با ساعت درشت و کار مستقیم، سه عدد کنارش
    (درآمد ماه شمسی از کیف پول، قابل برداشت، امتیاز)، بقیه‌ی امروز (فقط اگر هست)، «۷ روز آینده» و میله‌های درآمد ۷ روز گذشته.
    «آمار کل» به صفحه‌ی گزارش رفت؛ معرفی اپ یک نوار کوچک پایین صفحه است.
--}}
@php
    $cancelledBy = ['customer' => 'مشتری', 'specialist' => 'خودتان', 'admin' => 'مدیر سالن', 'system' => 'سیستم (پرداخت نشد)'];
    $firstName = auth()->user()->name ?? 'کارشناس';
    $nextIsToday = $nextBooking && $nextBooking->booking_time->isToday();
    $barMax = max(1, collect($weeklyIncome)->max('total'));
@endphp

@section('content')
    <div class="fade-in space-y-5" data-spec-dashboard>

        <div>
            <h2 class="text-xl md:text-2xl font-extrabold text-[var(--specialist-text)]">سلام، {{ $firstName }}</h2>
            <p class="mt-1 text-sm text-[var(--specialist-text-dim)]" data-spec-today-line>
                {{ $todayPersian }}.
                @if ($todayBookingsCount > 0)
                    امروز {{ fa_num($todayBookingsCount) }} نوبت دارید.
                @else
                    امروز نوبتی ندارید.
                @endif
            </p>
        </div>

        <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_17rem]">
            {{-- نوبت بعدی --}}
            <section class="specialist-card p-5 md:p-6 flex flex-col gap-4" aria-label="نوبت بعدی" data-next-booking="{{ $nextBooking?->id }}">
                @if ($nextBooking)
                    <div class="flex items-baseline justify-between gap-3">
                        <span class="text-sm text-[var(--specialist-text-dim)]">نوبت بعدی، {{ $nextIsToday ? 'امروز' : fa_date($nextBooking->booking_time) }}</span>
                        <b class="text-4xl font-black leading-none text-[var(--specialist-text)]" dir="ltr">{{ fa_time($nextBooking->booking_time) }}</b>
                    </div>
                    <div class="flex flex-col gap-1">
                        <b class="text-lg text-[var(--specialist-text)]">{{ $nextBooking->user?->name ?? '—' }}</b>
                        <span class="text-sm text-[var(--specialist-text-dim)]">
                            {{ $nextBooking->service?->name ?? '—' }}@if ($nextBooking->service?->duration)، {{ fa_num($nextBooking->service->duration) }} دقیقه@endif
                            @if ($__courseLabel = $nextBooking->courseSessionLabel())<span class="text-xs mr-1 px-2 py-0.5 rounded-full" style="background:rgb(var(--m-gold) / .15); color:rgb(var(--m-gold2));">{{ $__courseLabel }}</span>@endif
                        </span>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @if ($nextBooking->status === 'pending')
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold" style="{{ \App\Support\Bookings\BookingLabels::pillStyle('info') }}" data-booking-status="pending">منتظر تأیید شما</span>
                        @else
                            <x-booking-status :status="$nextBooking->status" class="!font-bold !px-3" />
                        @endif
                        @unless ($nextBooking->customer_course_id)
                            <x-payment-status :booking="$nextBooking" class="!font-bold !px-3" />
                        @endunless
                    </div>
                    @if ($nextBooking->due_at_salon > 0)
                        <div class="flex justify-between pt-3 border-t text-[15px]" style="border-color: var(--specialist-border);" data-due-at-salon>
                            <span class="text-[var(--specialist-text-dim)]">دریافت در سالن</span>
                            <b>@money($nextBooking->due_at_salon) تومان</b>
                        </div>
                    @endif
                    <div class="flex flex-wrap gap-2.5">
                        @include('specialist.partials.primary-action', ['booking' => $nextBooking, 'action' => $nextAction, 'size' => 'lg'])
                        @if ($nextBooking->user?->phone)
                            <a href="tel:{{ $nextBooking->user->phone }}" class="spec-act spec-act--ghost spec-act--lg" data-call-customer>تماس با مشتری</a>
                        @endif
                    </div>
                @else
                    <div class="py-6 text-center">
                        <p class="font-bold text-[var(--specialist-text)]">نوبت پیش رویی ندارید</p>
                        <p class="mt-1 text-sm text-[var(--specialist-text-dim)]">نوبت تازه که ثبت شود همین‌جا دیده می‌شود.</p>
                    </div>
                @endif
            </section>

            {{-- سه عدد --}}
            <div class="grid grid-cols-2 lg:grid-cols-1 gap-3">
                <div class="specialist-card px-4 py-3.5 flex flex-col lg:flex-row lg:items-center justify-between gap-1" data-stat="month-income">
                    <span class="text-sm text-[var(--specialist-text-dim)]">درآمد {{ $monthLabel }}</span>
                    <b class="text-lg">@money($monthIncome) <span class="text-xs font-medium text-[var(--specialist-text-dim)]">تومان</span></b>
                </div>
                <a href="{{ route('specialist.wallet.index') }}" class="specialist-card px-4 py-3.5 flex flex-col lg:flex-row lg:items-center justify-between gap-1" data-stat="withdrawable">
                    <span class="text-sm text-[var(--specialist-text-dim)]">قابل برداشت</span>
                    <b class="text-lg text-[var(--specialist-text)]">@money($withdrawable) <span class="text-xs font-medium text-[var(--specialist-text-dim)]">تومان</span></b>
                </a>
                <div class="specialist-card px-4 py-3.5 hidden lg:flex items-center justify-between gap-1" data-stat="rating">
                    <span class="text-sm text-[var(--specialist-text-dim)]">امتیاز</span>
                    @if ($averageRating === null)
                        <span class="text-sm text-[var(--specialist-text-dim)]">هنوز نظری ثبت نشده</span>
                    @else
                        <b class="text-lg">{{ fa_num($averageRating, 1) }} <span class="text-xs font-medium text-[var(--specialist-text-dim)]">از ۵</span></b>
                    @endif
                </div>
            </div>
        </div>

        {{-- بقیه‌ی امروز (نوبت‌های دیگر امروز، نیامده‌ها و لغوشده‌های جمع‌شده؛ ۲۰۲۶-۱۰-۲۲) --}}
        @if ($todayOthers->isNotEmpty() || $todayCancelled->isNotEmpty())
            <section class="specialist-card overflow-hidden" aria-labelledby="spec-today-h" data-spec-today>
                <div class="px-5 py-3.5 border-b" style="border-color: var(--specialist-border);">
                    <h2 id="spec-today-h" class="text-base font-extrabold">بقیه‌ی امروز</h2>
                </div>
                <ol class="text-sm">
                    @foreach ($todayOthers as $booking)
                        <li class="flex flex-wrap items-center gap-x-4 gap-y-1 px-5 py-3 border-b last:border-b-0 {{ in_array($booking->status, ['no_show', 'completed'], true) ? 'opacity-70' : '' }}" style="border-color: var(--specialist-border);" data-today-booking="{{ $booking->id }}">
                            <b class="min-w-[3rem]" dir="ltr">{{ fa_time($booking->booking_time) }}</b>
                            <span class="flex-1 min-w-[10rem]">
                                <a href="{{ route('specialist.bookings.show', $booking) }}" class="font-bold hover:underline">{{ $booking->user?->name ?? '—' }}</a>،
                                {{ $booking->service?->name ?? '—' }}
                                @unless ($booking->customer_course_id)
                                    @php $__pay = \App\Support\Bookings\BookingLabels::paymentInfo($booking); @endphp
                                    <span class="block text-xs mt-0.5" data-payment="{{ $booking->payment_status === 'paid' ? 'paid' : 'unpaid' }}">
                                        <span class="{{ $__pay['class'] }}">{{ $__pay['label'] }}</span>
                                        @if ($booking->due_at_salon > 0 && $booking->status !== 'no_show')
                                            <span class="text-[var(--specialist-text-dim)]">؛ دریافت در سالن: {{ fa_num($booking->due_at_salon) }} تومان</span>
                                        @endif
                                    </span>
                                @endunless
                            </span>
                            <x-booking-status :status="$booking->status" />
                        </li>
                    @endforeach
                </ol>
                @if ($todayCancelled->isNotEmpty())
                    <details class="px-5 py-3 border-t" style="border-color: var(--specialist-border);" data-today-cancelled>
                        <summary class="cursor-pointer text-sm text-[var(--specialist-text-dim)]">لغوشده‌های امروز ({{ fa_num($todayCancelled->count()) }})</summary>
                        <div class="space-y-3 mt-3">
                            @foreach ($todayCancelled as $booking)
                                <a href="{{ route('specialist.bookings.show', $booking) }}" class="flex justify-between items-start gap-3 text-sm" data-cancelled-booking="{{ $booking->id }}">
                                    <div>
                                        <p>{{ $booking->user?->name ?? '—' }} <span class="text-[var(--specialist-text-dim)]">، {{ $booking->service?->name ?? '—' }}</span></p>
                                        <p class="text-xs text-[var(--specialist-text-dim)]">لغو توسط {{ $cancelledBy[$booking->cancelled_by] ?? 'نامشخص' }}@if ($booking->cancellation_reason)؛ {{ \Illuminate\Support\Str::limit($booking->cancellation_reason, 60) }}@endif</p>
                                    </div>
                                    <span class="font-bold text-[var(--specialist-text-dim)]" dir="ltr">{{ fa_time($booking->booking_time) }}</span>
                                </a>
                            @endforeach
                        </div>
                    </details>
                @endif
            </section>
        @endif

        <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_19rem] items-start">
            {{-- ۷ روز آینده --}}
            <section class="specialist-card overflow-hidden" aria-labelledby="spec-up-h" data-spec-upcoming>
                <div class="px-5 py-3.5 flex items-center justify-between border-b" style="border-color: var(--specialist-border);">
                    <h2 id="spec-up-h" class="text-base font-extrabold">۷ روز آینده</h2>
                    <a href="{{ route('specialist.bookings.index') }}" class="text-sm font-bold" style="color: rgb(var(--m-gold2));">همه‌ی نوبت‌ها</a>
                </div>
                @if ($upcomingDays->isNotEmpty())
                    <ol class="text-sm">
                        @foreach ($upcomingDays as $booking)
                            <li class="flex flex-wrap md:flex-nowrap items-center gap-x-4 gap-y-1 px-5 py-3 border-b last:border-b-0" style="border-color: var(--specialist-border);" data-upcoming-booking="{{ $booking->id }}">
                                <span class="md:min-w-[8rem] text-[var(--specialist-text-dim)]">{{ fa_date($booking->booking_time) }}</span>
                                <b class="min-w-[3rem]" dir="ltr">{{ fa_time($booking->booking_time) }}</b>
                                <a href="{{ route('specialist.bookings.show', $booking) }}" class="flex-1 min-w-[8rem] hover:underline">{{ $booking->user?->name ?? '—' }}، {{ $booking->service?->name ?? '—' }}</a>
                                <x-booking-status :status="$booking->status" class="!font-bold" />
                            </li>
                        @endforeach
                    </ol>
                @else
                    <p class="px-5 py-8 text-center text-sm text-[var(--specialist-text-dim)]">در ۷ روز آینده نوبتی ندارید.</p>
                @endif
            </section>

            {{-- درآمد ۷ روز گذشته (همان منبع کارت «درآمد ماه»: درآمد خالص کیف پول) --}}
            <section class="specialist-card p-5 flex flex-col gap-3" aria-labelledby="spec-rev-h" data-spec-revenue>
                <h2 id="spec-rev-h" class="text-base font-extrabold">درآمد ۷ روز گذشته</h2>
                <div class="flex items-end gap-2 h-32" role="img" aria-label="درآمد روزانه از {{ $weeklyIncome[0]['label'] }} تا {{ $weeklyIncome[6]['label'] }}: @foreach ($weeklyIncome as $d){{ $d['label'] }} {{ fa_money($d['total']) }} تومان{{ $loop->last ? '' : '، ' }}@endforeach">
                    @foreach ($weeklyIncome as $d)
                        <div class="flex-1 flex flex-col items-center gap-1.5 h-full justify-end" title="{{ $d['label'] }}: {{ fa_money($d['total']) }} تومان" data-revenue-day>
                            <div class="w-full rounded-md" style="height: {{ $d['total'] > 0 ? max(8, (int) round($d['total'] / $barMax * 100)) : 4 }}px; background: {{ $d['total'] <= 0 ? 'rgb(var(--m-tx) / .08)' : ($d['today'] ? 'rgb(var(--m-gold))' : 'rgb(var(--m-gold) / .45)') }};"></div>
                            <span class="text-[11.5px] {{ $d['today'] ? 'font-bold text-[var(--specialist-text)]' : 'text-[var(--specialist-text-dim)]' }}">{{ $d['today'] ? $d['label'] : $d['day'] }}</span>
                        </div>
                    @endforeach
                </div>
                <p class="text-sm text-[var(--specialist-text-dim)]">آمار کل نوبت‌ها در <a href="{{ route('specialist.reports.index') }}" class="font-bold" style="color: rgb(var(--m-gold2));">گزارش</a>.</p>
            </section>
        </div>

        {{-- ۲۰۲۶-۱۱-۰۶: معرفی «ماهرو همکار»؛ قابل‌بستن (۳۰ روز)، و در حالت نصب‌شده (اپ/PWA) دیده نمی‌شود. بسته‌ی ۳ ظاهری: نوار کوچک پایین صفحه --}}
        <div class="rounded-2xl px-4 py-3 flex items-center gap-3 text-sm border border-dashed" style="border-color: rgb(var(--m-gold) / .45);" data-staff-app-promo hidden>
            <span class="flex-1 min-w-0 text-[var(--specialist-text-dim)]">اپ «ماهرو همکار» را نصب کنید تا اعلان نوبت‌ها روی گوشی بیاید.</span>
            <a href="{{ route('specialist.install') }}" class="font-bold shrink-0" style="color: rgb(var(--m-gold2));">نصب</a>
            <button type="button" data-staff-app-promo-close aria-label="بستن" class="shrink-0 p-1 text-[var(--specialist-inactive)] hover:text-[var(--specialist-text)]">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18" /></svg>
            </button>
        </div>
        <script @nonce>
            (function () {
                var box = document.querySelector('[data-staff-app-promo]');
                if (!box) return;
                var key = 'mahru:staff-app-promo-closed';
                var standalone = (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) || window.navigator.standalone === true;
                var closedAt = 0;
                try { closedAt = parseInt(localStorage.getItem(key) || '0', 10) || 0; } catch (e) {}
                // آیفون بنر «افزودن به صفحه‌ی اصلی» خودش را دارد (partials/pwa-client) — دو پیشنهاد هم‌زمان نه
                var ios = /iPhone|iPad|iPod/.test(navigator.userAgent) || (/Macintosh/.test(navigator.userAgent) && navigator.maxTouchPoints > 1);
                if (standalone || ios || Date.now() - closedAt < 30 * 24 * 3600 * 1000) return;
                box.hidden = false;
                box.querySelector('[data-staff-app-promo-close]').addEventListener('click', function () {
                    box.hidden = true;
                    try { localStorage.setItem(key, String(Date.now())); } catch (e) {}
                });
            })();
        </script>
    </div>
@endsection
