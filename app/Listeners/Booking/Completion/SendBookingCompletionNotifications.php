<?php

namespace App\Listeners\Booking\Completion;

use App\Events\Booking\Completed\BookingCompleted;
use App\Notifications\Booking\BookingStatusUpdated;
use App\Services\Review\ReviewService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Replaces two direct calls inside
 * SpecialistBookingManagementController::markAsCompleted() — Request
 * Poll and notify "completed" to the customer.
 */
class SendBookingCompletionNotifications implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly ReviewService $reviewService,
    ) {
    }

    public function handle(BookingCompleted $event): void
    {
        $booking = $event->booking;

        try {
            $this->reviewService->sendReviewRequest($booking);
        } catch (\Throwable $e) {
            Log::error('❌ خطا در ارسال درخواست نظرسنجی', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            $booking->user?->notify(new BookingStatusUpdated($booking, 'completed'));
        } catch (\Throwable $e) {
            Log::warning('❌ خطا در ارسال نوتیفیکیشن تکمیل نوبت به مشتری', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
