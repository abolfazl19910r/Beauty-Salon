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
    public const LABELS = ['pay' => 'پرداخت', 'directions' => 'مسیریابی', 'reschedule' => 'تغییر زمان', 'review' => 'ثبت نظر', 'rebook' => 'رزرو دوباره', 'details' => 'جزئیات'];

    /**
     * نوع کار(ها)ی یک نوبت، بی نشانی — همان قاعده برای وب (primary) و API اپ مشتری (بسته‌ی ۵د).
     *
     * @return list<string> یک یا دو کار، اولی اصلی
     */
    public static function kinds(Booking $booking, bool $hasMap, ?Carbon $now = null): array
    {
        $now ??= Carbon::now();
        $open = in_array($booking->status, CustomerBookingListFilter::OPEN_STATUSES, true);

        return match (true) {
            $open && $booking->isPayable() => ['pay'],
            $open && $booking->booking_time->isSameDay($now) && $hasMap => ['directions'],
            $open && $booking->canBeRescheduled() => ['reschedule'],
            $booking->status === 'completed' => $booking->rating === null ? ['review', 'rebook'] : ['rebook'],
            ! $open => ['rebook'],
            default => ['details'],
        };
    }

    /** @return list<array{kind: string, label: string, url: string, external?: bool}> یک یا دو کار، اولی اصلی */
    public static function primary(Booking $booking, ?string $mapUrl, ?Carbon $now = null): array
    {
        return array_map(fn (string $kind) => match ($kind) {
            'pay' => ['kind' => 'pay', 'label' => self::LABELS['pay'], 'url' => route('payment.show', $booking)],
            'directions' => ['kind' => 'directions', 'label' => self::LABELS['directions'], 'url' => (string) $mapUrl, 'external' => true],
            'reschedule' => ['kind' => 'reschedule', 'label' => self::LABELS['reschedule'], 'url' => route('bookings.reschedule', $booking)],
            'review' => ['kind' => 'review', 'label' => self::LABELS['review'], 'url' => route('reviews.create', $booking)],
            'rebook' => ['kind' => 'rebook', 'label' => self::LABELS['rebook'], 'url' => route('bookings.create', array_filter(['service' => $booking->service_id, 'specialist' => $booking->specialist_id]))],
            default => ['kind' => 'details', 'label' => self::LABELS['details'], 'url' => route('bookings.show', $booking)],
        }, self::kinds($booking, (bool) $mapUrl, $now));
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
