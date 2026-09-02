<?php

namespace App\Services\Admin\Booking;

use App\Events\Booking\BookingCancelled;
use App\Exceptions\BookingNotAvailableException;
use App\Models\Booking;
use App\Notifications\Booking\BookingRescheduledNotification;
use App\Services\Booking\BookingService;
use Illuminate\Support\Facades\DB;

class AdminBookingService
{
    public function __construct(protected readonly BookingService $bookingService) {}

    /**
     * @return array{message: string}
     */
    public function updateStatus(Booking $booking, string $status): array
    {
        $oldStatus = $booking->status;

        $booking->update($this->buildUpdatePayload(['status' => $status], $status, $oldStatus));

        return $this->handlePostUpdateSideEffects($booking, $oldStatus);
    }

    /**
     * ⭐ Fix (fix/admin-booking-slot-conflict, commit 4): previously this method updated
     * specialist_id/booking_time with zero availability check — the exact same gap the
     * create-flow fix (commit 2) closed for new bookings. Also previously silent about a
     * schedule change: the customer never learned their appointment moved, unlike the dedicated
     * self-service reschedule flow (BookingRescheduleController) which does SMS them.
     *
     * @return array{message: string}
     *
     * @throws BookingNotAvailableException
     */
    public function updateFull(Booking $booking, array $validated): array
    {
        $oldStatus = $booking->status;
        $newStatus = $validated['status'] ?? $booking->status;

        $newSpecialistId = (int) ($validated['specialist_id'] ?? $booking->specialist_id);
        $newBookingTime = $validated['booking_time'] ?? (string) $booking->booking_time;
        $scheduleChanged = $newSpecialistId !== (int) $booking->specialist_id
            || strtotime($newBookingTime) !== strtotime((string) $booking->booking_time);

        if ($scheduleChanged) {
            $this->bookingService->assertManualRescheduleAvailable($booking, $newSpecialistId, $newBookingTime);
        }

        $oldBookingTime = $booking->booking_time;

        DB::transaction(function () use ($booking, $validated, $newStatus, $oldStatus) {
            try {
                $booking->update($this->buildUpdatePayload($validated, $newStatus, $oldStatus));
            } catch (\Illuminate\Database\QueryException $e) {
                if ($this->bookingService->isDuplicateActiveSlotError($e)) {
                    throw BookingNotAvailableException::slotTaken(
                        "Race condition: slot taken concurrently while editing booking #{$booking->id}.",
                        ['booking_id' => $booking->id]
                    );
                }

                throw $e;
            }
        });

        if ($scheduleChanged) {
            // ⭐ The event key below is literally named "...CUSTOMER" (see
            // NotificationEvents::BOOKING_RESCHEDULED_CUSTOMER — "تغییر زمان نوبت — اطلاع به
            // مشتری"), yet BookingRescheduleController::update() sends this same notification to
            // $booking->specialist, not $booking->user. That mismatch is a separate, pre-existing
            // issue outside this branch's scope — flagged here rather than silently copied;
            // this admin-edit path notifies the customer, matching what the event name and
            // gate label both say it's for.
            $booking->user->notify(new BookingRescheduledNotification($booking, $oldBookingTime));
        }

        return $this->handlePostUpdateSideEffects($booking, $oldStatus);
    }

    /**
     * R-Observers addendum: previously cancelled_by='admin' was deliberately never set on the
     * model — the comment here explained that BookingObserver's wallet-refund branch AND
     * RefundService (a real gateway refund call) would both fire and double-refund. That
     * concern no longer applies: RefundService::processRefund() called a method
     * (PaymentService::refund()) that doesn't exist — it fatal-errored (an uncaught \Error, not
     * caught by its own \Exception catch block) every time an admin cancelled a paid booking,
     * with no actual refund ever happening. RefundService is no longer called from here.
     * Admin cancellations now go through the exact same wallet-credit path already used for
     * customer/specialist cancellations (BookingObserver already had an 'admin' branch ready,
     * it just never received cancelled_by='admin' to trigger it).
     *
     * cancelled_by/cancelled_at are merged into this SAME update() call (not a follow-up one)
     * so BookingObserver::updated() only fires once per cancellation — setting them in a second
     * update afterward would fire the observer twice and double-reverse the specialist/admin
     * wallet credits (see BookingObserver::reverseOriginalPayout()).
     */
    private function buildUpdatePayload(array $payload, string $newStatus, string $oldStatus): array
    {
        if ($newStatus === 'cancelled' && $oldStatus !== 'cancelled') {
            $payload['cancelled_by'] = 'admin';
            $payload['cancelled_at'] = now();
        }

        return $payload;
    }

    /**
     * @return array{message: string}
     */
    private function handlePostUpdateSideEffects(Booking $booking, string $oldStatus): array
    {
        if ($booking->status === 'cancelled' && $oldStatus !== 'cancelled') {
            event(new BookingCancelled($booking, 'admin'));
        }

        return [
            'message' => match ($booking->status) {
                'confirmed' => 'نوبت با موفقیت تایید شد.',
                'cancelled' => 'نوبت با موفقیت لغو شد.',
                default => 'وضعیت نوبت با موفقیت بروزرسانی شد.',
            },
        ];
    }
}
