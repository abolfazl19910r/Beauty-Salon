<?php

namespace App\Services\Booking;

use App\Exceptions\BookingNotAvailableException;
use App\Models\Booking;
use App\Notifications\Booking\BookingRescheduledNotification;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Services\Bot\BotMessenger;
use App\Services\SMSService;
use App\Support\Notifications\NotificationEvents;
use App\Support\Sms\SmsText;
use Illuminate\Support\Facades\DB;

/**
 * جابه‌جایی نوبت توسط خود مشتری — وب (BookingRescheduleController) و اپ «ماهرو» (۲۰۲۶-۱۰-۱۱؛ منطق از کنترلر وب
 * منتقل شد، بدون تغییر رفتار). مجاز بودن (BookingPolicy::reschedule) را فراخواننده چک می‌کند.
 */
class BookingRescheduleService
{
    public function __construct(
        private readonly BookingRepositoryInterface $bookingRepository,
        private readonly BookingService $bookingService,
        private readonly SMSService $smsService,
    ) {}

    /**
     * @throws BookingNotAvailableException ساعت آزاد نیست
     */
    public function reschedule(Booking $booking, string $bookingTime): Booking
    {
        \App\Services\Salon\SalonPauseService::assertOpen((int) $booking->salon_id);

        $date = date('Y-m-d', strtotime($bookingTime));
        $time = date('H:i', strtotime($bookingTime));

        if (! in_array($time, $booking->specialist->getAvailableSlots($date), true)) {
            throw BookingNotAvailableException::slotTaken(
                "Reschedule slot {$bookingTime} is not available for booking #{$booking->id}.",
                ['booking_id' => $booking->id, 'booking_time' => $bookingTime],
            );
        }

        // جلسه‌ی دوره‌ی درمانی (بسته‌ی ب): فقط در بازه‌ی مجاز همان دوره (فاصله‌ی حداقل از جلسه‌ی قبلی، تا پایان مهلت)
        if ($booking->customer_course_id && ($course = \App\Models\CustomerCourse::withoutGlobalScopes()->find($booking->customer_course_id))) {
            $at = \Illuminate\Support\Carbon::parse($bookingTime);
            if ($course->expires_at && $at->gt($course->expires_at)) {
                throw new \App\Services\Courses\CourseException('این زمان بعد از پایان مهلت دوره است.');
            }
            // فقط جلسه‌های پیش از این جلسه (نه جلسه‌های بعدی که شاید از قبل رزرو شده‌اند)
            $earliest = $course->earliestNextSession($booking->id, \Illuminate\Support\Carbon::parse($booking->booking_time));
            if ($at->lt($earliest)) {
                throw new \App\Services\Courses\CourseException(sprintf('جلسه‌ی دوره زودتر از %s ممکن نیست.', verta($earliest)->format('Y/m/d')));
            }
        }

        DB::transaction(function () use ($booking, $bookingTime) {
            // کل مدت خدمت با نوبت‌های دیگر، استراحت و ساعت کاری؛ با قفل ردیف متخصص (۲۰۲۶-۱۰-۰۱)
            $this->bookingService->assertBookingFits((int) $booking->specialist_id, $bookingTime, (int) $booking->service_id, $booking->id, $booking->duration_minutes ? (int) $booking->duration_minutes : null);

            $oldTime = $booking->booking_time;
            $newStatus = $booking->specialist->auto_confirm_bookings ? 'confirmed' : 'pending';

            $this->bookingRepository->update($booking, [
                'booking_time' => $bookingTime,
                'status' => $newStatus,
            ]);

            $booking->specialist->notify(new BookingRescheduledNotification($booking, $oldTime));

            $message = SmsText::rescheduledForCustomer($booking->fresh(['service']), $newStatus !== 'confirmed');
            app(BotMessenger::class)->send($booking->user, $message, NotificationEvents::BOOKING_RESCHEDULED_CUSTOMER, $booking->salon_id);
            // پوش اپ یا پیامک (بسته‌ی ۴). پیامک این پیام از پیش پشت سوییچ تنظیمات نبود (همان رفتار)؛ سوییچ «اپ» رویداد اعمال می‌شود.
            $messenger = app(\App\Services\Push\AppMessenger::class);
            $outgoing = new \App\Services\Push\Outgoing(
                recipient: $booking->user,
                phone: $booking->user->phone,
                text: $message,
                salonId: $booking->salon_id,
                smsFeature: \App\Support\Features\Feature::SmsCustomer,
                pushKey: NotificationEvents::BOOKING_RESCHEDULED_CUSTOMER,
                // جابه‌جایی را خود مشتری کرده (وب یا اپ) — برای خودش فوری نیست
                urgency: \App\Services\Push\PushUrgency::Normal,
                data: ['booking_id' => $booking->id],
                inbox: true,
            );
            $this->settingsAllowPush($booking) ? $messenger->deliver($outgoing) : $this->smsService->send($booking->user->phone, $message, $booking->salon_id, \App\Support\Features\Feature::SmsCustomer);
        });

        return $booking->refresh();
    }

    private function settingsAllowPush(Booking $booking): bool
    {
        return app(\App\Services\Notification\NotificationSettingService::class)
            ->isEnabled(NotificationEvents::BOOKING_RESCHEDULED_CUSTOMER, 'push', $booking->salon_id);
    }
}
