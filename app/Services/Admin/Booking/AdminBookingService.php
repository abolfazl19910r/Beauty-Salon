<?php

namespace App\Services\Admin\Booking;

use App\Events\Booking\BookingCancelled;
use App\Models\Booking;

class AdminBookingService
{
    public function getStats(?string $date): array
    {
        $date = $date ?: today();

        return [
            'total' => Booking::whereDate('booking_time', $date)->count(),
            'confirmed' => Booking::whereDate('booking_time', $date)
                ->where('status', 'confirmed')->count(),
            'cancelled' => Booking::whereDate('booking_time', $date)
                ->where('status', 'cancelled')->count(),
        ];
    }

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
     * @return array{message: string}
     */
    public function updateFull(Booking $booking, array $validated): array
    {
        $oldStatus = $booking->status;
        $newStatus = $validated['status'] ?? $booking->status;

        $booking->update($this->buildUpdatePayload($validated, $newStatus, $oldStatus));

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
