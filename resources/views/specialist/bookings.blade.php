@extends('layouts.specialist')

@section('title', 'نوبت‌ها')

{{--
    بسته‌ی ۳ ظاهری (۲۰۲۶-۱۱-۲۵، پیش‌نمایش SpAfterBookings؛ تصمیم‌های «الف»): تب «پیش رو / منتظر تأیید / گذشته / لغوشده» با شمار و یک
    جستجو (نام یا موبایل مشتری) جای فرم فیلتر. هر ردیف: ساعت و تاریخ فارسی، مشتری و خدمت، پرداخت، وضعیت، یک کار اصلی
    (partials/primary-action: تأیید / انجام شد / جزئیات) و منوی «⋯» (جزئیات، تماس، «مشتری نیامد…» برای تأییدشده‌ی گذشته،
    جداکننده، «لغو نوبت…» با دلیل). نوبت «منتظر پرداخت» برچسب خودش را دارد و کار اصلی ندارد.
--}}
@php
    $noShowLabels = app(\App\Services\Booking\NoShowRecord::class)->labelsFor($bookings->pluck('user_id'));
    $now = now();
@endphp

@section('content')
    <div class="fade-in space-y-4" data-spec-bookings>
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="text-xl md:text-2xl font-extrabold">نوبت‌های من</h2>
                <p class="mt-1 text-sm text-[var(--specialist-text-dim)]" data-waiting-line>
                    @if ($waitingCount > 0)
                        {{ fa_num($waitingCount) }} نوبت منتظر تأیید شماست.
                    @else
                        نوبتی منتظر تأیید شما نیست.
                    @endif
                </p>
            </div>
            <form method="GET" action="{{ route('specialist.bookings.index') }}" class="flex items-center gap-2 w-full sm:w-auto" role="search" data-spec-search>
                <input type="hidden" name="tab" value="{{ $tab }}">
                <label for="spec-q" class="sr-only">جستجوی نام یا موبایل مشتری</label>
                <input id="spec-q" type="search" name="q" value="{{ $search }}" placeholder="نام یا موبایل مشتری" maxlength="100"
                       class="flex-1 sm:w-64 rounded-full px-4 py-2 text-sm border">
                <button type="submit" class="spec-act spec-act--ghost">جستجو</button>
                @if ($search !== null && $search !== '')
                    <a href="{{ route('specialist.bookings.index', ['tab' => $tab]) }}" class="text-sm text-[var(--specialist-text-dim)] hover:underline whitespace-nowrap">پاک کردن</a>
                @endif
            </form>
        </div>

        <nav class="flex gap-2 overflow-x-auto pb-1" aria-label="بازه‌ی نوبت‌ها" data-spec-tabs>
            @foreach (\App\Services\Specialist\SpecialistBookingListFilter::TABS as $key => $label)
                <a href="{{ route('specialist.bookings.index', array_filter(['tab' => $key, 'q' => $search])) }}"
                   @if ($tab === $key) aria-current="page" @endif data-spec-tab="{{ $key }}"
                   class="shrink-0 inline-flex items-center gap-1.5 rounded-full px-4 py-2 text-sm font-bold border transition-colors {{ $tab === $key ? 'text-[rgb(var(--m-bg))] bg-[rgb(var(--m-tx))] border-transparent' : 'border-[var(--specialist-border)] text-[var(--specialist-text)] hover:bg-m-tx/5' }}">
                    {{ $label }} <span class="{{ $tab === $key ? 'opacity-80' : 'text-[var(--specialist-text-dim)]' }}">{{ fa_num($tabCounts[$key]) }}</span>
                </a>
            @endforeach
        </nav>

        <div class="specialist-card overflow-hidden">
            @if ($bookings->isEmpty())
                <div class="text-center py-14 px-4 text-[var(--specialist-text-dim)]" data-spec-empty>
                    @if ($search)
                        <p>نوبتی با «{{ $search }}» در این بخش پیدا نشد.</p>
                    @else
                        <p>{{ match ($tab) { 'pending' => 'نوبتی منتظر تأیید شما نیست.', 'past' => 'هنوز نوبت گذشته‌ای ندارید.', 'cancelled' => 'نوبت لغوشده‌ای ندارید.', default => 'نوبت پیش رویی ندارید.' } }}</p>
                    @endif
                </div>
            @else
                <ul>
                    @foreach ($bookings as $booking)
                        @php
                            $action = in_array($booking->status, ['pending', 'confirmed'], true)
                                ? \App\Services\Specialist\SpecialistDashboardService::primaryAction($booking, $now)
                                : null;
                            $canCancel = in_array($booking->status, ['pending', 'confirmed'], true);
                            $canNoShow = $booking->status === 'confirmed' && $booking->booking_time->lte($now);
                            $who = $booking->user?->name ?? 'مشتری';
                            $dateLabel = $booking->booking_time->isToday() ? 'امروز، '.fa_date($booking->booking_time, weekday: false) : fa_date($booking->booking_time);
                        @endphp
                        <li class="booking-row grid gap-x-4 gap-y-2 px-4 md:px-5 py-4 border-b last:border-b-0 items-center grid-cols-[4.5rem_minmax(0,1fr)] md:grid-cols-[7.5rem_minmax(0,1fr)_auto_auto]"
                            style="border-color: var(--specialist-border);" data-booking-row="{{ $booking->id }}">
                            <div class="flex flex-col">
                                <b class="text-lg leading-tight" dir="ltr" style="text-align:right">{{ fa_time($booking->booking_time) }}</b>
                                <span class="text-xs text-[var(--specialist-text-dim)]">{{ $dateLabel }}</span>
                            </div>
                            <div class="min-w-0">
                                <b class="block md:truncate">
                                    <a href="{{ route('specialist.bookings.show', $booking) }}" class="hover:underline">{{ $who }}</a>، {{ $booking->service?->name ?? '—' }}
                                    @if ($__courseLabel = $booking->courseSessionLabel())<span class="text-xs mr-1 px-2 py-0.5 rounded-full font-medium" style="background:rgb(var(--m-gold) / .15); color:rgb(var(--m-gold2));">{{ $__courseLabel }}</span>@endif
                                </b>
                                <span class="block text-xs mt-0.5 text-[var(--specialist-text-dim)]" data-row-payment>
                                    @unless ($booking->customer_course_id)
                                        @php $__pay = \App\Support\Bookings\BookingLabels::paymentInfo($booking); @endphp
                                        <span class="{{ $__pay['class'] }}">{{ $__pay['label'] }}</span>@if ($booking->due_at_salon > 0 && in_array($booking->status, ['pending', 'confirmed'], true))؛ دریافت در سالن @money($booking->due_at_salon) تومان@endif
                                    @endunless
                                </span>
                                <x-no-show-badge :label="$noShowLabels[$booking->user_id] ?? null" />
                            </div>
                            <div class="col-start-2 md:col-start-auto">
                                <x-booking-status :status="$booking->status" class="!font-bold" />
                            </div>
                            <div class="col-start-2 md:col-start-auto flex items-center gap-2 md:justify-end booking-actions">
                                @if ($action)
                                    @include('specialist.partials.primary-action', ['booking' => $booking, 'action' => $action])
                                @endif
                                <x-specialist.row-menu :label="'کارهای بیشتر برای نوبت '.$who">
                                    <a href="{{ route('specialist.bookings.show', $booking) }}" class="spec-menu__item" role="menuitem">جزئیات نوبت</a>
                                    @if ($booking->user?->phone)
                                        <a href="tel:{{ $booking->user->phone }}" class="spec-menu__item" role="menuitem">تماس با مشتری</a>
                                    @endif
                                    @if ($canNoShow)
                                        <form method="POST" action="{{ route('specialist.bookings.no-show', $booking) }}" data-ui-confirm="{{ $who }} نیامد؟ پیش‌پرداخت برنمی‌گردد و جلسه‌ی دوره (اگر هست) می‌سوزد." data-spec-no-show>
                                            @csrf @method('PUT')
                                            <button type="submit" class="spec-menu__item" role="menuitem">مشتری نیامد…</button>
                                        </form>
                                    @endif
                                    @if ($canCancel)
                                        <span class="spec-menu__sep" role="separator"></span>
                                        <button type="button" class="spec-menu__item spec-menu__item--danger" role="menuitem" data-spec-cancel
                                                data-ui-click="specCancel" data-ui-args="{{ json_encode([route('specialist.bookings.cancel', $booking), $who, $booking->service?->name ?? '—', $dateLabel.'، '.fa_time($booking->booking_time)]) }}">لغو نوبت…</button>
                                    @endif
                                </x-specialist.row-menu>
                            </div>
                        </li>
                    @endforeach
                </ul>
                @if ($bookings->hasPages())
                    <div class="p-4 border-t" style="border-color: var(--specialist-border);">{{ $bookings->links() }}</div>
                @endif
            @endif
        </div>
    </div>

    {{-- لغو نوبت: دلیل اجباری (به مشتری اطلاع داده می‌شود) --}}
    <dialog id="spec-cancel-dialog" class="specialist-card p-0 w-[min(28rem,calc(100vw-2rem))] text-[var(--specialist-text)] backdrop:bg-black/60" aria-labelledby="spec-cancel-h">
        <form method="POST" id="spec-cancel-form" class="p-6 space-y-4">
            @csrf @method('PUT')
            <h3 id="spec-cancel-h" class="text-lg font-extrabold text-m-bad">لغو نوبت</h3>
            <p class="text-sm text-[var(--specialist-text-dim)]" id="spec-cancel-msg"></p>
            <label for="spec-cancel-reason" class="block text-sm font-bold">دلیل لغو (برای مشتری فرستاده می‌شود)</label>
            <textarea id="spec-cancel-reason" name="cancel_reason" required rows="3" maxlength="500" class="w-full rounded-xl p-3 border" placeholder="مثلاً: بیماری"></textarea>
            <div class="flex gap-2">
                <button type="submit" class="flex-1 rounded-full py-2.5 font-bold bg-m-bad text-m-onsolid">لغو نوبت</button>
                <button type="button" class="flex-1 spec-act spec-act--ghost" data-ui-click="specCancelClose">انصراف</button>
            </div>
        </form>
    </dialog>
@endsection

@push('scripts')
    <script @nonce>
        uiActions.register({
            specCancel: function (event, url, name, service, when) {
                var dialog = document.getElementById('spec-cancel-dialog');
                document.getElementById('spec-cancel-form').action = url;
                document.getElementById('spec-cancel-msg').textContent = 'نوبت «' + service + '» ' + name + ' (' + when + ') لغو شود؟';
                document.getElementById('spec-cancel-reason').value = '';
                var menu = event.target.closest('[data-spec-menu-root]');
                if (menu) menu.open = false;
                dialog.showModal();
                document.getElementById('spec-cancel-reason').focus();
            },
            specCancelClose: function () { document.getElementById('spec-cancel-dialog').close(); },
        });
    </script>
@endpush
