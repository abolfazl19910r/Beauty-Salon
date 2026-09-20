<?php

namespace App\Services\Admin\Booking;

use App\Events\Booking\BookingCancelled;
use App\Exceptions\BookingNotAvailableException;
use App\Models\Booking;
use App\Notifications\Booking\BookingRescheduledNotification;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Services\Booking\BookingService;
use Illuminate\Support\Facades\DB;

class AdminBookingService
{
    public function __construct(
        protected readonly BookingService $bookingService,
        protected readonly BookingRepositoryInterface $bookingRepository,
    ) {}

    public function updateStatus(Booking $booking, string $status): array
    {
        $oldStatus = $booking->status;

        $booking = $this->bookingRepository->update($booking, $this->buildUpdatePayload(['status' => $status], $status, $oldStatus));

        return $this->handlePostUpdateSideEffects($booking, $oldStatus);
    }

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
                $this->bookingRepository->update($booking, $this->buildUpdatePayload($validated, $newStatus, $oldStatus));
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
            $booking->user->notify(new BookingRescheduledNotification($booking, $oldBookingTime));
        }

        return $this->handlePostUpdateSideEffects($booking, $oldStatus);
    }

    private function buildUpdatePayload(array $payload, string $newStatus, string $oldStatus): array
    {
        if ($newStatus === 'cancelled' && $oldStatus !== 'cancelled') {
            $payload['cancelled_by'] = 'admin';
            $payload['cancelled_at'] = now();
        }

        return $payload;
    }

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
