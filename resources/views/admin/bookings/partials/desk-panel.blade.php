{{--
    پنل جزئیات نوبت انتخاب‌شده در پوسته‌ی «میز پذیرش» (بسته‌ی ۲ب) — با fetch از admin.bookings.panel بار می‌شود.
    ورودی: $booking (با service/user/specialist)، $history [completed, no_show, last]
--}}
@php
    $user = auth()->user();
    $canCancel = ! in_array($booking->status, ['cancelled', 'completed', 'no_show'], true);
    $customer = \App\Support\Fa::digits($booking->user?->name ?? 'مشتری');
@endphp
<div class="desk-panel__body" data-desk-panel-body="{{ $booking->id }}">
    <div class="flex items-center justify-between gap-2">
        <b class="text-[15px]" style="color: var(--admin-text);">نوبت {{ \App\Support\Fa::digits($booking->id) }}</b>
        <x-booking-status :status="$booking->status" />
    </div>

    <dl class="desk-panel__grid">
        <dt>مشتری</dt><dd><b>{{ $booking->user?->name ?? '—' }}</b></dd>
        <dt>موبایل</dt><dd dir="ltr" class="text-right">{{ $booking->user ? \App\Support\Fa::digits($booking->user->phone) : '—' }}</dd>
        <dt>خدمت</dt><dd>{{ $booking->service?->name ?? '—' }}@if ($booking->service?->duration)، {{ fa_num($booking->service->duration) }} دقیقه@endif</dd>
        <dt>کارشناس</dt><dd>{{ $booking->specialist?->name ?? '—' }}</dd>
        <dt>زمان</dt><dd><b>{{ fa_date($booking->booking_time, false, true) }}، {{ fa_time($booking->booking_time) }}</b></dd>
        <dt>پرداخت</dt><dd><x-payment-status :booking="$booking" /></dd>
    </dl>

    <div class="flex gap-1.5">
        @if ($booking->status === 'pending' && $user?->hasPermission('approve-bookings'))
            <form action="{{ route('admin.bookings.update', $booking) }}" method="POST" class="flex-1" data-ui-once
                  data-desk-confirm data-ui-confirm="نوبت {{ $customer }} ({{ fa_date($booking->booking_time, true) }}) تأیید شود؟">
                @csrf @method('PUT')
                <input type="hidden" name="status" value="confirmed">
                <button type="submit" class="adm-act adm-act--primary w-full justify-center">تأیید <kbd dir="ltr">T</kbd></button>
            </form>
        @endif
        @if ($canCancel && $user?->hasPermission('edit-bookings'))
            <a href="{{ route('admin.bookings.edit', $booking) }}" class="adm-act flex-1 justify-center">جابه‌جایی</a>
        @endif
        <x-admin.row-menu :label="'کارهای بیشتر برای نوبت '.$customer">
            <a href="{{ route('admin.bookings.show', $booking) }}" role="menuitem" class="adm-menu__item">صفحه‌ی کامل نوبت</a>
            @if ($canCancel && $user?->hasPermission('delete-bookings'))
                <span class="adm-menu__sep" role="separator"></span>
                <form action="{{ route('admin.bookings.update', $booking) }}" method="POST" data-ui-once
                      data-ui-confirm="لغو نوبت {{ $customer }} ({{ fa_date($booking->booking_time, true) }})؟ به مشتری خبر داده می‌شود.">
                    @csrf @method('PUT')
                    <input type="hidden" name="status" value="cancelled">
                    <button type="submit" role="menuitem" class="adm-menu__item adm-menu__item--danger">لغو نوبت…</button>
                </form>
            @endif
        </x-admin.row-menu>
    </div>

    <div data-desk-history>
        <b class="text-[13px]" style="color: var(--admin-text);">سابقه‌ی مشتری</b>
        <p class="mt-1.5 leading-loose" style="color: var(--admin-text-dim);">
            {{ fa_num($history['completed']) }} نوبت انجام‌شده، {{ fa_num($history['no_show']) }} غیبت<br>
            @if ($history['last'])
                آخرین نوبت: {{ fa_date($history['last']->booking_time) }}، {{ $history['last']->service?->name ?? '—' }}
            @else
                نوبت انجام‌شده‌ی دیگری ندارد.
            @endif
        </p>
    </div>
</div>
