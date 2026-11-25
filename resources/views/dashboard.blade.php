@extends('layouts.app')

@section('title', 'داشبورد')

{{--
    بسته‌ی ۴ ظاهری (۲۰۲۶-۱۱-۲۵، پیش‌نمایش CuAfterDash/CuAfterMobile): کارت «نوبت امروز» (یا «نوبت بعدی») با مسیریابی، تقویم و تغییر
    زمان؛ کیف پول و باشگاه کنارش؛ «نوبت‌های بعدی» در یک فهرست؛ «نظرتان چه بود؟» با ستاره‌ی مستقیم؛ «دوباره رزرو کنید».
    داده: CustomerDashboardService::overview().
--}}
@section('content')
    <div class="max-w-6xl mx-auto fade-in space-y-5" data-customer-dashboard>
        <div>
            <h1 class="text-xl md:text-2xl font-extrabold text-m-tx">سلام، {{ auth()->user()->name }}</h1>
            <p class="mt-1 text-sm text-m-tx/65" data-today-line>
                {{ $todayPersian }}.
                @if ($todayCount === 1)
                    امروز یک نوبت دارید.
                @elseif ($todayCount > 1)
                    امروز {{ fa_num($todayCount) }} نوبت دارید.
                @endif
            </p>
        </div>

        <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_19rem]">
            {{-- نوبت امروز / نوبت بعدی --}}
            <section class="m-card p-5 md:p-6 flex flex-col gap-4" aria-label="{{ $nextIsToday ? 'نوبت امروز' : 'نوبت بعدی' }}" data-next-booking="{{ $nextBooking?->id }}">
                @if ($nextBooking)
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex flex-col gap-1 min-w-0">
                            <span class="text-sm text-m-tx/65">{{ $nextIsToday ? 'نوبت امروز' : 'نوبت بعدی، '.fa_date($nextBooking->booking_time) }}</span>
                            <b class="text-lg text-m-tx">{{ $nextBooking->service?->name ?? '—' }}</b>
                            <span class="text-sm text-m-tx/70">{{ implode('، ', array_filter([$nextBooking->specialist ? 'با '.$nextBooking->specialist->name : null, $nextBooking->service?->duration ? fa_num($nextBooking->service->duration).' دقیقه' : null])) }}</span>
                        </div>
                        <b class="text-4xl font-black leading-none text-m-tx shrink-0" dir="ltr">{{ fa_time($nextBooking->booking_time) }}</b>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <x-booking-status :status="$nextBooking->status" audience="customer" class="!font-bold !px-3" />
                        @unless ($nextBooking->customer_course_id)
                            <x-payment-status :booking="$nextBooking" class="!font-bold !px-3" />
                        @endunless
                    </div>
                    @if ($salon && ($salon->address || $salon->phone))
                        <p class="text-sm text-m-tx/70 leading-7" data-salon-contact>
                            {{ $salon->address }}@if ($salon->address && $salon->phone)، @endif
                            @if ($salon->phone)تلفن سالن <a href="tel:{{ $salon->phone }}" dir="ltr" class="font-bold text-m-tx">{{ \App\Support\Fa::digits($salon->phone) }}</a>@endif
                        </p>
                    @endif
                    <div class="flex flex-wrap gap-2">
                        @if ($nextBooking->status === 'pending_payment' && ! $nextBooking->customer_course_id)
                            <a href="{{ route('payment.show', $nextBooking) }}" class="btn-gold px-5 py-2.5 text-sm font-bold" data-next-pay>پرداخت پیش‌پرداخت</a>
                        @endif
                        @if ($mapUrl)
                            <a href="{{ $mapUrl }}" target="_blank" rel="noopener" class="m-btn-ghost px-4 py-2.5 text-sm font-bold" data-directions>مسیریابی</a>
                        @endif
                        <a href="{{ route('bookings.calendar', $nextBooking) }}" class="m-btn-ghost px-4 py-2.5 text-sm font-bold" data-add-to-calendar>افزودن به تقویم</a>
                        @if ($canChange)
                            <a href="{{ route('bookings.reschedule', $nextBooking) }}" class="m-btn-ghost px-4 py-2.5 text-sm font-bold" data-reschedule>تغییر زمان</a>
                        @endif
                    </div>
                    <p class="text-xs text-m-tx/60 pt-3 border-t border-m-gold/15" data-change-deadline>
                        @if ($canChange)
                            تغییر زمان یا لغو تا {{ fa_num($changeHours) }} ساعت پیش از نوبت ممکن است.
                        @else
                            کمتر از {{ fa_num($changeHours) }} ساعت به نوبت مانده؛ تغییر زمان یا لغو دیگر از این‌جا ممکن نیست. برای هماهنگی با سالن تماس بگیرید.
                        @endif
                    </p>
                @else
                    <div class="py-6 text-center">
                        <p class="font-bold text-m-tx">نوبت پیش رویی ندارید</p>
                        <p class="mt-1 text-sm text-m-tx/65">هر وقت خواستید، یک نوبت تازه بگیرید.</p>
                        <a href="{{ route('bookings.create') }}" class="btn-gold inline-block mt-4 px-6 py-2.5 text-sm font-bold">رزرو نوبت</a>
                    </div>
                @endif
            </section>

            {{-- کیف پول و باشگاه (موبایل: دو کارت کوچک کنار هم) --}}
            <div class="grid grid-cols-2 lg:grid-cols-1 gap-3 content-start">
                <section class="m-card p-4 flex flex-col gap-2" aria-label="موجودی کیف پول" data-wallet-card>
                    <span class="text-sm text-m-tx/65">موجودی کیف پول</span>
                    <b class="text-lg text-m-tx">@money($walletBalance) <span class="text-xs font-medium text-m-tx/60">تومان</span></b>
                    <a href="{{ route('wallet.charge') }}" class="text-sm font-bold text-m-gold2">شارژ کیف پول</a>
                </section>
                @if ($toRate)
                    {{-- موبایل: «نظر شما» کنار کیف پول؛ دسکتاپ پایین‌تر کارت کامل --}}
                    <section class="m-card p-4 flex flex-col gap-2 lg:hidden" aria-label="نظر شما">
                        <span class="text-sm text-m-tx/65">نظر شما</span>
                        <span class="text-sm text-m-tx truncate">{{ $toRate->service?->name }}، {{ fa_date($toRate->booking_time, weekday: false) }}</span>
                        <a href="#rate-last" class="text-sm font-bold text-m-gold2">ثبت نظر</a>
                    </section>
                @endif
                @if ($loyaltyOn)
                    <section class="m-card p-4 flex flex-col gap-2 {{ $toRate ? 'hidden lg:flex' : '' }}" aria-label="باشگاه مشتریان" data-loyalty-card>
                        <span class="text-sm text-m-tx/65">باشگاه مشتریان</span>
                        @if ($loyaltyPoints > 0)
                            <b class="text-lg text-m-tx">{{ fa_num($loyaltyPoints) }} <span class="text-xs font-medium text-m-tx/60">امتیاز</span></b>
                        @else
                            <b class="text-sm text-m-tx">هنوز امتیازی ندارید</b>
                            <span class="text-xs text-m-tx/60 leading-6">بعد از هر نوبت انجام‌شده امتیاز می‌گیرید و با آن کد تخفیف می‌سازید.</span>
                        @endif
                        <a href="{{ route('loyalty.index') }}" class="text-sm font-bold text-m-gold2">جزئیات باشگاه</a>
                    </section>
                @endif
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_19rem] items-start">
            {{-- نوبت‌های بعدی --}}
            <section class="m-card overflow-hidden" aria-labelledby="cu-up-h" data-upcoming>
                <div class="px-5 py-3.5 flex items-center justify-between border-b border-m-gold/15">
                    <h2 id="cu-up-h" class="text-base font-extrabold text-m-tx">نوبت‌های بعدی</h2>
                    <a href="{{ route('bookings.index') }}" class="text-sm font-bold text-m-gold2">همه‌ی نوبت‌ها</a>
                </div>
                @if ($upcoming->isNotEmpty())
                    <ol>
                        @foreach ($upcoming as $booking)
                            <li class="flex items-center gap-3 px-5 py-3 border-b border-m-gold/10 last:border-b-0" data-upcoming-booking="{{ $booking->id }}">
                                @include('bookings.partials.day-box', ['at' => $booking->booking_time])
                                <a href="{{ route('bookings.show', $booking) }}" class="flex-1 min-w-0 hover:underline">
                                    <b class="block text-sm text-m-tx truncate">{{ $booking->service?->name ?? '—' }}</b>
                                    <span class="block text-xs text-m-tx/65">ساعت {{ fa_time($booking->booking_time) }}@if ($booking->specialist)، با {{ $booking->specialist->name }}@endif</span>
                                </a>
                                @unless ($booking->customer_course_id)
                                    <x-payment-status :booking="$booking" />
                                @endunless
                            </li>
                        @endforeach
                    </ol>
                @else
                    <p class="px-5 py-8 text-center text-sm text-m-tx/60">نوبت دیگری ندارید.</p>
                @endif
            </section>

            <div class="space-y-4">
                {{-- نظرتان چه بود؟ --}}
                @if ($toRate)
                    <section id="rate-last" class="m-card p-5 flex flex-col gap-3" aria-labelledby="cu-rate-h" data-rate-last="{{ $toRate->id }}">
                        <h2 id="cu-rate-h" class="text-base font-extrabold text-m-tx">نظرتان چه بود؟</h2>
                        <p class="text-sm text-m-tx/70">{{ $toRate->service?->name }}@if ($toRate->specialist) با {{ $toRate->specialist->name }}@endif، {{ fa_date($toRate->booking_time, weekday: false) }}</p>
                        <form method="POST" action="{{ route('bookings.rate', $toRate) }}" class="flex gap-1" aria-label="امتیاز از ۱ تا ۵">
                            @csrf
                            @for ($i = 1; $i <= 5; $i++)
                                <button type="submit" name="rating" value="{{ $i }}" class="text-3xl leading-none text-m-tx/25 hover:text-m-gold focus-visible:text-m-gold transition-colors" aria-label="{{ fa_num($i) }} ستاره" data-star="{{ $i }}">★</button>
                            @endfor
                        </form>
                        <a href="{{ route('bookings.show', $toRate) }}" class="text-xs text-m-tx/60 hover:underline">نوشتن نظر کامل</a>
                    </section>
                @endif

                {{-- دوباره رزرو کنید --}}
                @if ($rebook?->service || $suggestion)
                    <section class="m-card p-5 flex flex-col gap-3" aria-labelledby="cu-again-h" data-rebook>
                        <h2 id="cu-again-h" class="text-base font-extrabold text-m-tx">دوباره رزرو کنید</h2>
                        @if ($rebook?->service)
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <b class="block text-sm text-m-tx truncate">{{ $rebook->service->name }}</b>
                                    @if ($rebook->specialist)<span class="text-xs text-m-tx/60">آخرین بار با {{ $rebook->specialist->name }}</span>@endif
                                </div>
                                <a href="{{ route('bookings.create', array_filter(['service' => $rebook->service_id, 'specialist' => $rebook->specialist_id])) }}" class="m-btn-ghost px-4 py-2 text-sm font-bold shrink-0" data-rebook-last>رزرو</a>
                            </div>
                        @endif
                        @if ($suggestion)
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <b class="block text-sm text-m-tx truncate">{{ $suggestion->name }}</b>
                                    <span class="text-xs text-m-tx/60">از @money($suggestion->price) تومان</span>
                                </div>
                                <a href="{{ route('bookings.create', ['service' => $suggestion->id]) }}" class="m-btn-ghost px-4 py-2 text-sm font-bold shrink-0" data-rebook-suggestion="{{ $suggestion->id }}">رزرو</a>
                            </div>
                        @endif
                    </section>
                @endif
            </div>
        </div>
    </div>
@endsection
