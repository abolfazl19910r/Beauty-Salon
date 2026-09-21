<?php

namespace App\Services\Leave;

use App\Models\Leave;
use App\Models\Specialist;
use App\Notifications\Leave\LeaveStatusNotification;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Repositories\Contracts\LeaveRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;

class LeaveService
{
    public function __construct(
        protected readonly LeaveRepositoryInterface $leaveRepository,
        protected readonly BookingRepositoryInterface $bookingRepository,
        protected readonly UserRepositoryInterface $userRepository,
    ) {}

    public function store(Specialist $specialist, array $data): array
    {
        $conflict = $this->findConflictReason($specialist, $data['start_date'], $data['end_date']);

        if ($conflict) {
            return ['success' => false, 'message' => $conflict, 'leave' => null];
        }

        $leave = $this->leaveRepository->createForSpecialist($specialist, [
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'reason' => $data['reason'] ?? null,
            'status' => 'pending',
        ]);

        return [
            'success' => true,
            'message' => 'درخواست مرخصی با موفقیت ثبت شد.',
            'leave' => $leave,
        ];
    }

    public function updateStatus(Leave $leave, string $status, ?string $rejectReason = null): array
    {
        if ($status === 'approved') {
            $conflict = $this->findConflictReason(
                $leave->specialist,
                $leave->start_date->toDateString(),
                $leave->end_date->toDateString(),
                excludeLeaveId: $leave->id
            );

            if ($conflict) {
                return ['success' => false, 'message' => $conflict];
            }

            $leave->approve();
        } else {
            $leave->reject($rejectReason);
        }

        $this->notifySpecialistUser($leave);

        return ['success' => true, 'message' => 'وضعیت مرخصی با موفقیت بروزرسانی شد.'];
    }

    private function notifySpecialistUser(Leave $leave): void
    {
        $specialist = $leave->specialist;
        $user = $this->userRepository->findByPhone($specialist->phone);

        $user?->notify(new LeaveStatusNotification($leave));
    }

    private function findConflictReason(
        Specialist $specialist,
        string $startDate,
        string $endDate,
        ?int $excludeLeaveId = null
    ): ?string {
        $hasOverlap = $this->leaveRepository->hasOverlappingApprovedLeave(
            $specialist->id,
            $startDate,
            $endDate,
            $excludeLeaveId
        );

        if ($hasOverlap) {
            return 'این بازه زمانی با یک مرخصی تاییدشده‌ی دیگر تداخل دارد.';
        }

        $hasBooking = $this->bookingRepository->hasBookingInRange(
            $specialist->id,
            "{$startDate} 00:00:00",
            "{$endDate} 23:59:59"
        );

        if ($hasBooking) {
            return 'در این بازه زمانی نوبت‌هایی برای این متخصص ثبت شده است.';
        }

        return null;
    }
}
