{{--
    کار هر ردیف نوبت در پنل مدیر (بسته‌ی ۲ ظاهری): یک دکمه‌ی متنی برای کار اصلی + (اگر $withMenu) منوی «⋯».
    ورودی: $booking، $withMenu (پیش‌فرض true)
--}}
@php
    $withMenu = $withMenu ?? true;
    $isFuture = $booking->booking_time?->isFuture();
    $canCancel = ! in_array($booking->status, ['cancelled', 'completed', 'no_show'], true);
@endphp
<div class="flex items-center gap-1.5 justify-end" data-booking-actions="{{ $booking->id }}">
    @if ($booking->status === 'pending' && auth()->user()?->hasPermission('approve-bookings'))
        <form action="{{ route('admin.bookings.update', $booking) }}" method="POST" data-ui-once>
            @csrf @method('PUT')
            <input type="hidden" name="status" value="confirmed">
            <button type="submit" class="adm-act adm-act--primary" data-primary-action="confirm">تأیید</button>
        </form>
    @elseif ($booking->status === 'pending_payment' && $isFuture && \Illuminate\Support\Facades\Route::has('admin.bookings.payment-reminder') && auth()->user()?->hasPermission('edit-bookings'))
        <form action="{{ route('admin.bookings.payment-reminder', $booking) }}" method="POST" data-ui-once
              data-ui-confirm="پیامک یادآوری پیش‌پرداخت برای {{ \App\Support\Fa::digits($booking->user?->name ?? 'مشتری') }} فرستاده شود؟ از سهمیه‌ی پیامک سالن کم می‌شود.">
            @csrf
            <button type="submit" class="adm-act" data-primary-action="remind">یادآوری پرداخت</button>
        </form>
    @else
        <a href="{{ route('admin.bookings.show', $booking) }}" class="adm-act adm-act--ghost" data-primary-action="details">جزئیات</a>
    @endif

    @if ($withMenu)
        <x-admin.row-menu :label="'کارهای بیشتر برای نوبت '.\App\Support\Fa::digits($booking->user?->name ?? '#'.$booking->id)">
            <a href="{{ route('admin.bookings.show', $booking) }}" role="menuitem" class="adm-menu__item">جزئیات نوبت</a>
            @if ($canCancel && auth()->user()?->hasPermission('edit-bookings'))
                <a href="{{ route('admin.bookings.edit', $booking) }}" role="menuitem" class="adm-menu__item">ویرایش یا جابه‌جایی</a>
            @endif
            @if ($canCancel && auth()->user()?->hasPermission('delete-bookings'))
                <span class="adm-menu__sep" role="separator"></span>
                <form action="{{ route('admin.bookings.update', $booking) }}" method="POST" data-ui-once
                      data-ui-confirm="لغو نوبت {{ \App\Support\Fa::digits($booking->user?->name ?? '') }} ({{ fa_date($booking->booking_time, true) }})؟ به مشتری خبر داده می‌شود.">
                    @csrf @method('PUT')
                    <input type="hidden" name="status" value="cancelled">
                    <button type="submit" role="menuitem" class="adm-menu__item adm-menu__item--danger" data-menu-cancel>لغو نوبت…</button>
                </form>
            @endif
        </x-admin.row-menu>
    @endif
</div>
