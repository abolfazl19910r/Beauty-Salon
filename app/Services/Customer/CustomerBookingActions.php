<?php

namespace App\Services\Customer;

use App\Models\Booking;
use App\Models\WalletSetting;
use App\Services\Api\CustomerPresenter;
use Illuminate\Support\Carbon;

/**
 * کار اصلی و پیامد لغو یک نوبت در «نوبت‌های من» مشتری (بسته‌ی ۴ ظاهری، ۲۰۲۶-۱۱-۲۵؛ پیش‌نمایش CuAfterBookings):
 * امروز ← «مسیریابی» (اگر سالن مکان دارد)؛ منتظر پرداخت ← «پرداخت»؛ آینده ← «تغییر زمان» (تا ۲۴ ساعت پیش)؛
 * انجام‌شده ← «ثبت نظر» (اگر نظری نیست) + «رزرو دوباره»؛ گذشته/لغوشده ← «رزرو دوباره»؛ بقیه ← «جزئیات».
 */
final class CustomerBookingActions
{
    /** @return list<array{kind: string, label: string, url: string, external?: bool}> یک یا دو کار، اولی اصلی */
    public static function primary(Booking $booking, ?string $mapUrl, ?Carbon $now = null): array
    {
        $now ??= Carbon::now();
        $open = in_array($booking->status, CustomerBookingListFilter::OPEN_STATUSES, true);
        $rebook = ['kind' => 'rebook', 'label' => 'رزرو دوباره', 'url' => route('bookings.create', array_filter(['service' => $booking->service_id, 'specialist' => $booking->specialist_id]))];

        if ($open && $booking->isPayable()) {
            return [['kind' => 'pay', 'label' => 'پرداخت', 'url' => route('payment.show', $booking)]];
        }
        if ($open && $booking->booking_time->isSameDay($now) && $mapUrl) {
            return [['kind' => 'directions', 'label' => 'مسیریابی', 'url' => $mapUrl, 'external' => true]];
        }
        if ($open && $booking->canBeRescheduled()) {
            return [['kind' => 'reschedule', 'label' => 'تغییر زمان', 'url' => route('bookings.reschedule', $booking)]];
        }
        if ($booking->status === 'completed') {
            return $booking->rating === null
                ? [['kind' => 'review', 'label' => 'ثبت نظر', 'url' => route('reviews.create', $booking)], $rebook]
                : [$rebook];
        }
        if (! $open) {
            return [$rebook];
        }

        return [['kind' => 'details', 'label' => 'جزئیات', 'url' => route('bookings.show', $booking)]];
    }

    /** لغو از سمت مشتری هنوز مجاز است؟ (همان قاعده‌ی BookingPolicy::cancel) */
    public static function canCancel(Booking $booking): bool
    {
        return in_array($booking->status, CustomerBookingListFilter::OPEN_STATUSES, true)
            && $booking->booking_time->gt(now()->addHours(CustomerPresenter::CANCEL_DEADLINE_HOURS));
    }

    /** یک خط پیامد مالی لغو، با جریمه‌ی لغو همان سالن (BookingObserver همین را حساب می‌کند) */
    public static function cancelNote(Booking $booking): string
    {
        $paid = (float) $booking->prepayment_amount;
        if ($booking->payment_status !== 'paid' || $paid <= 0) {
            return 'پولی پرداخت نشده؛ لغو هزینه‌ای ندارد.';
        }

        $fee = WalletSetting::get()->calculateCustomerCancellationFee($paid, $booking->booking_time);
        $refund = max(0, $paid - $fee);

        return $fee > 0
            ? fa_money($fee).' تومان جریمه‌ی لغو کم و '.fa_money($refund).' تومان به کیف پول برمی‌گردد.'
            : 'پیش‌پرداخت ('.fa_money($paid).' تومان) به کیف پول برمی‌گردد.';
    }
}
