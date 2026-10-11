<?php

namespace App\Services\Booking;

use App\Exceptions\BookingActionNotAllowedException;
use App\Models\Booking;
use App\Models\User;
use App\Notifications\Review\NewReviewNotification;
use Illuminate\Support\Facades\DB;

/**
 * امتیاز مشتری به نوبت خودش — وب و اپ (۲۰۲۶-۱۰-۱۱).
 *
 * فقط نوبت انجام‌شده و فقط یک بار. قبلاً هر نوبتی (حتی لغوشده یا آینده) و هر چند بار امتیاز می‌گرفت و هر بار ۱۰
 * امتیاز باشگاه مشتریان اضافه می‌شد — یعنی امتیاز باشگاه (و کد تخفیف پاداش) با ارسال تکراری فرم بی‌نهایت می‌شد.
 */
class BookingRatingService
{
    public const LOYALTY_POINTS = 10;

    public function rate(User $user, Booking $booking, int $rating, ?string $review): Booking
    {
        DB::transaction(function () use ($user, $booking, $rating, $review) {
            $locked = Booking::query()->lockForUpdate()->findOrFail($booking->id);

            if ((int) $locked->user_id !== (int) $user->id) {
                throw BookingActionNotAllowedException::because('این نوبت متعلق به شما نیست.');
            }
            if ($locked->status !== 'completed') {
                throw BookingActionNotAllowedException::because('فقط به نوبت انجام‌شده می‌توانید امتیاز بدهید.');
            }
            if ($locked->rating !== null) {
                throw BookingActionNotAllowedException::because('به این نوبت قبلاً امتیاز داده‌اید.');
            }

            $locked->update(['rating' => $rating, 'review' => $review]);
            $user->addLoyaltyPoints(self::LOYALTY_POINTS, 'ثبت نظر برای نوبت #'.$locked->id);
        });

        $booking->refresh();
        $booking->specialist?->notify(new NewReviewNotification($booking));

        return $booking;
    }
}
