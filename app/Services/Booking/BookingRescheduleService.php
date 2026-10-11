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
        $date = date('Y-m-d', strtotime($bookingTime));
        $time = date('H:i', strtotime($bookingTime));

        if (! in_array($time, $booking->specialist->getAvailableSlots($date), true)) {
            throw BookingNotAvailableException::slotTaken(
                "Reschedule slot {$bookingTime} is not available for booking #{$booking->id}.",
                ['booking_id' => $booking->id, 'booking_time' => $bookingTime],
            );
        }

        DB::transaction(function () use ($booking, $bookingTime) {
            // کل مدت خدمت با نوبت‌های دیگر، استراحت و ساعت کاری؛ با قفل ردیف متخصص (۲۰۲۶-۱۰-۰۱)
            $this->bookingService->assertBookingFits((int) $booking->specialist_id, $bookingTime, (int) $booking->service_id, $booking->id);

            $oldTime = $booking->booking_time;
            $newStatus = $booking->specialist->auto_confirm_bookings ? 'confirmed' : 'pending';

            $this->bookingRepository->update($booking, [
                'booking_time' => $bookingTime,
                'status' => $newStatus,
            ]);

            $booking->specialist->notify(new BookingRescheduledNotification($booking, $oldTime));

            $message = SmsText::rescheduledForCustomer($booking->fresh(['service']), $newStatus !== 'confirmed');
            app(BotMessenger::class)->send($booking->user, $message, NotificationEvents::BOOKING_RESCHEDULED_CUSTOMER, $booking->salon_id);
            $this->smsService->send($booking->user->phone, $message, $booking->salon_id);
        });

        return $booking->refresh();
    }
}
