<?php

namespace App\Services\Specialist;

use App\Events\Booking\BookingCancelled;
use App\Events\Booking\Completed\BookingCompleted;
use App\Exceptions\InvalidBookingStateException;
use App\Models\Booking;
use App\Notifications\Booking\BookingStatusUpdated;
use Illuminate\Support\Facades\DB;

/**
 * پذیرش، لغو و «انجام شد» نوبت از طرف متخصص — یک کد برای پنل وب و API اپ «ماهرو همکار» (بسته‌ی ۲ اپلیکیشن).
 * مالکیت نوبت را صداکننده چک می‌کند (وب ۴۰۳، API ۴۰۴). وضعیت هر بار داخل تراکنش با قفل ردیف دوباره خوانده
 * می‌شود تا تغییر هم‌زمان (لغو مشتری، لغو خودکار پرداخت‌نشده) بازنویسی نشود. برگشت پول/جریمه‌ی لغو مثل قبل در
 * BookingObserver با تغییر status به cancelled انجام می‌شود.
 *
 * هر متد true برمی‌گرداند اگر وضعیت عوض شد و false اگر نوبت از قبل در وضعیت مقصد بود.
 */
class SpecialistBookingActions
{
    /** فقط pending → confirmed (رفع ۲۰۲۶-۱۰-۱۰) */
    public function confirm(Booking $booking): bool
    {
        return DB::transaction(function () use ($booking) {
            $locked = $this->lock($booking);

            if ($locked->status === 'confirmed') {
                return false;
            }

            if ($locked->status !== 'pending') {
                throw InvalidBookingStateException::because('فقط نوبت‌های «در انتظار تأیید» قابل پذیرش هستند.');
            }

            $locked->update(['status' => 'confirmed']);
            $locked->user->notify(new BookingStatusUpdated($locked, 'confirmed'));
            $booking->setRawAttributes($locked->getAttributes(), true);

            return true;
        });
    }

    public function cancel(Booking $booking, ?string $reason): bool
    {
        $changed = DB::transaction(function () use ($booking, $reason) {
            $locked = $this->lock($booking);

            if ($locked->status === 'cancelled') {
                return false;
            }

            if ($locked->status === 'completed') {
                throw InvalidBookingStateException::because('نوبت‌های انجام شده قابل لغو نیستند.');
            }

            $locked->update([
                'status' => 'cancelled',
                'cancellation_reason' => $reason ?: 'دلیل مشخص نشده',
                'cancelled_by' => 'specialist',
                'cancelled_at' => now(),
            ]);
            $booking->setRawAttributes($locked->getAttributes(), true);

            return true;
        });

        if ($changed) {
            event(new BookingCancelled($booking, 'specialist'));
        }

        return $changed;
    }

    /** فقط confirmed → completed */
    public function complete(Booking $booking): bool
    {
        $changed = DB::transaction(function () use ($booking) {
            $locked = $this->lock($booking);

            if ($locked->status === 'completed') {
                return false;
            }

            if ($locked->status !== 'confirmed') {
                throw InvalidBookingStateException::because('فقط نوبت‌های تایید شده قابل علامت‌گذاری به عنوان «انجام شده» هستند.');
            }

            $locked->update(['status' => 'completed']);
            $booking->setRawAttributes($locked->getAttributes(), true);

            return true;
        });

        if ($changed) {
            event(new BookingCompleted($booking));
        }

        return $changed;
    }

    protected function lock(Booking $booking): Booking
    {
        return Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
    }
}
