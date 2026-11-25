@extends('layouts.app')

@section('title', 'نوبت‌های من')

{{--
    بسته‌ی ۴ ظاهری (۲۰۲۶-۱۱-۲۵، پیش‌نمایش CuAfterBookings): تب «پیش رو / گذشته / لغوشده» با شمار جای فیلتر وضعیت و تاریخ؛ هر ردیف
    جعبه‌ی روز، ساعت، خدمت و کارشناس، وضعیت و پرداخت به فارسی، یک کار اصلی با نام (CustomerBookingActions::primary) و منوی «⋯»
    (جزئیات و رسید، افزودن به تقویم، تماس با سالن، جداکننده، «لغو نوبت…» با یک خط پیامد مالی) جای آیکون‌های بی‌برچسب.
--}}
@php $now = now(); @endphp

@section('content')
    <div class="max-w-5xl mx-auto fade-in space-y-4" data-customer-bookings>
        <h1 class="text-xl md:text-2xl font-extrabold text-m-tx">نوبت‌های من</h1>

        <nav class="flex gap-2 overflow-x-auto pb-1" aria-label="بازه‌ی نوبت‌ها" data-cu-tabs>
            @foreach (\App\Services\Customer\CustomerBookingListFilter::TABS as $key => $label)
                <a href="{{ route('bookings.index', ['tab' => $key]) }}" data-cu-tab="{{ $key }}" @if ($tab === $key) aria-current="page" @endif
                   class="shrink-0 inline-flex items-center gap-1.5 rounded-full px-4 py-2 text-sm font-bold border transition-colors {{ $tab === $key ? 'bg-m-tx text-m-bg border-transparent' : 'border-m-gold/25 text-m-tx hover:bg-m-gold/10' }}">
                    {{ $label }} <span class="{{ $tab === $key ? 'opacity-80' : 'text-m-tx/55' }}">{{ fa_num($tabCounts[$key]) }}</span>
                </a>
            @endforeach
        </nav>

        <div class="m-card overflow-hidden">
            @if ($bookings->isEmpty())
                <div class="py-14 px-4 text-center" data-cu-empty>
                    <p class="text-m-tx/65">{{ match ($tab) { 'past' => 'هنوز نوبت گذشته‌ای ندارید.', 'cancelled' => 'نوبت لغوشده‌ای ندارید.', default => 'نوبت پیش رویی ندارید.' } }}</p>
                    @if ($tab === 'upcoming')
                        <a href="{{ route('bookings.create') }}" class="btn-gold inline-block mt-4 px-6 py-2.5 text-sm font-bold">رزرو نوبت</a>
                    @endif
                </div>
            @else
                <ul>
                    @foreach ($bookings as $booking)
                        @php
                            $actions = \App\Services\Customer\CustomerBookingActions::primary($booking, $mapUrl, $now);
                            $canCancel = \App\Services\Customer\CustomerBookingActions::canCancel($booking);
                            $isToday = $booking->booking_time->isSameDay($now);
                            $isOpen = in_array($booking->status, \App\Services\Customer\CustomerBookingListFilter::OPEN_STATUSES, true);
                        @endphp
                        <li class="grid gap-x-4 gap-y-2 px-4 md:px-5 py-4 border-b border-m-gold/10 last:border-b-0 items-center grid-cols-[3.5rem_minmax(0,1fr)] md:grid-cols-[3.5rem_minmax(0,1fr)_auto]" data-cu-row="{{ $booking->id }}">
                            @include('bookings.partials.day-box', ['at' => $booking->booking_time])
                            <div class="min-w-0">
                                <div class="flex items-baseline gap-2 flex-wrap">
                                    <b class="text-lg leading-tight text-m-tx" dir="ltr">{{ fa_time($booking->booking_time) }}</b>
                                    <a href="{{ route('bookings.show', $booking) }}" class="font-bold text-m-tx hover:underline">{{ $booking->service?->name ?? '—' }}</a>
                                    @if ($__courseLabel = $booking->courseSessionLabel())<span class="text-xs px-2 py-0.5 rounded-full bg-m-gold/15 text-m-gold2">{{ $__courseLabel }}</span>@endif
                                </div>
                                <p class="text-sm text-m-tx/65 mt-0.5">@if ($booking->specialist)با {{ $booking->specialist->name }}@endif</p>
                                <div class="flex flex-wrap gap-1.5 mt-2">
                                    @if ($isToday && $isOpen)
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold" style="{{ \App\Support\Bookings\BookingLabels::pillStyle('info') }}">امروز</span>
                                    @else
                                        <x-booking-status :status="$booking->status" audience="customer" class="!font-bold" />
                                    @endif
                                    @unless ($booking->customer_course_id)
                                        <x-payment-status :booking="$booking" class="!font-bold" />
                                    @endunless
                                </div>
                            </div>
                            <div class="col-start-2 md:col-start-auto flex items-center gap-2 md:justify-end">
                                @foreach ($actions as $__i => $__action)
                                    <a href="{{ $__action['url'] }}" @if (! empty($__action['external'])) target="_blank" rel="noopener" @endif data-cu-action="{{ $__action['kind'] }}"
                                       class="{{ $__i === 0 && in_array($__action['kind'], ['pay', 'review'], true) ? 'btn-gold' : 'm-btn-ghost' }} px-4 py-2 text-sm font-bold whitespace-nowrap">{{ $__action['label'] }}</a>
                                @endforeach
                                <x-customer.row-menu :label="'کارهای بیشتر برای نوبت '.($booking->service?->name ?? '')">
                                    <a href="{{ route('bookings.show', $booking) }}" class="cu-menu__item" role="menuitem">جزئیات و رسید</a>
                                    @if ($isOpen && $booking->booking_time->isFuture())
                                        <a href="{{ route('bookings.calendar', $booking) }}" class="cu-menu__item" role="menuitem" data-cu-calendar>افزودن به تقویم</a>
                                    @endif
                                    @if ($salon?->phone)
                                        <a href="tel:{{ $salon->phone }}" class="cu-menu__item" role="menuitem">تماس با سالن</a>
                                    @endif
                                    @if ($canCancel)
                                        @php $__note = \App\Services\Customer\CustomerBookingActions::cancelNote($booking); @endphp
                                        <span class="cu-menu__sep" role="separator"></span>
                                        <form method="POST" action="{{ route('bookings.cancel', $booking) }}" data-ui-confirm="نوبت «{{ $booking->service?->name }}» ({{ fa_date($booking->booking_time, true) }}) لغو شود؟ {{ $__note }}" data-cu-cancel>
                                            @csrf @method('PUT')
                                            <button type="submit" class="cu-menu__item cu-menu__item--danger" role="menuitem">لغو نوبت…</button>
                                        </form>
                                        <span class="cu-menu__note" data-cu-cancel-note>{{ $__note }}</span>
                                    @endif
                                </x-customer.row-menu>
                            </div>
                        </li>
                    @endforeach
                </ul>
                @if ($bookings->hasPages())
                    <div class="p-4 border-t border-m-gold/10">{{ $bookings->links('pagination::simple-tailwind') }}</div>
                @endif
            @endif
        </div>
    </div>
@endsection
