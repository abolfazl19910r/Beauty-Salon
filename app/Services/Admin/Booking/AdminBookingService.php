<?php

namespace App\Services\Admin\Booking;

use App\Models\Booking;
use App\Services\RefundService;

class AdminBookingService
{
    protected RefundService $refundService;

    public function __construct(RefundService $refundService)
    {
        $this->refundService = $refundService;
    }

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
     * @return array{refund_warning: bool, message: string}
     */
    public function updateStatus(Booking $booking, string $status): array
    {
        $oldStatus = $booking->status;
        $booking->update(['status' => $status]);

        return $this->handlePostUpdateSideEffects($booking, $oldStatus);
    }

    /**
     * @return array{refund_warning: bool, message: string}
     */
    public function updateFull(Booking $booking, array $validated): array
    {
        $oldStatus = $booking->status;
        $booking->update($validated);

        return $this->handlePostUpdateSideEffects($booking, $oldStatus);
    }

    private function handlePostUpdateSideEffects(Booking $booking, string $oldStatus): array
    {
        $refundWarning = false;

        if ($booking->status === 'cancelled' &&
            $oldStatus !== 'cancelled' &&
            $booking->payment_status === 'paid' &&
            !$booking->refunded_at) {

            $refundResult = $this->refundService->processRefund($booking);
            $refundWarning = !$refundResult;
        }

        return [
            'refund_warning' => $refundWarning,
            'message' => match ($booking->status) {
                'confirmed' => 'نوبت با موفقیت تایید شد.',
                'cancelled' => 'نوبت با موفقیت لغو شد.',
                default => 'وضعیت نوبت با موفقیت بروزرسانی شد.',
            },
        ];
    }
}
