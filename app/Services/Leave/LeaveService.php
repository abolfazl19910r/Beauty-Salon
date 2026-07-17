<?php

namespace App\Services\Leave;

use App\Models\Leave;
use App\Models\Specialist;
use App\Notifications\LeaveStatusNotification;

class LeaveService
{
    /**
     * Registering a new leave request — before registering, conflicts with other approved leaves and already booked appointments are checked (something that was not possible in the old version of
     * SpecialistLeave).
     * @return array{success: bool, message: string, leave: ?Leave}
     */
    public function store(Specialist $specialist, array $data): array
    {
        $conflict = $this->findConflictReason($specialist, $data['start_date'], $data['end_date']);

        if ($conflict) {
            return ['success' => false, 'message' => $conflict, 'leave' => null];
        }

        $leave = $specialist->leaves()->create([
            'start_date' => $data['start_date'],
            'end_date'   => $data['end_date'],
            'reason'     => $data['reason'] ?? null,
            'status'     => 'pending',
        ]);

        return [
            'success' => true,
            'message' => 'درخواست مرخصی با موفقیت ثبت شد.',
            'leave'   => $leave,
        ];
    }

    /**
     * Approve or reject a leave. On approval, a conflict check is performed again (because
     * another leave or shift may have been registered
     * between the request registration and its approval). In both cases, a notification (database + SMS) is sent to the specialist
     * — which was not done at all in the old version (AdminSpecialistLeaveController).
     * @return array{success: bool, message: string}
     */
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

        $leave->specialist->notify(new LeaveStatusNotification($leave));

        return ['success' => true, 'message' => 'وضعیت مرخصی با موفقیت بروزرسانی شد.'];
    }

    private function findConflictReason(
        Specialist $specialist,
        string $startDate,
        string $endDate,
        ?int $excludeLeaveId = null
    ): ?string {
        $hasOverlap = $specialist->leaves()
            ->where('status', 'approved')
            ->when($excludeLeaveId, fn ($q) => $q->where('id', '!=', $excludeLeaveId))
            ->overlapping($startDate, $endDate)
            ->exists();

        if ($hasOverlap) {
            return 'این بازه زمانی با یک مرخصی تاییدشده‌ی دیگر تداخل دارد.';
        }

        $hasBooking = $specialist->bookings()
            ->whereBetween('booking_time', ["{$startDate} 00:00:00", "{$endDate} 23:59:59"])
            ->whereNotIn('status', ['cancelled'])
            ->exists();

        if ($hasBooking) {
            return 'در این بازه زمانی نوبت‌هایی برای این متخصص ثبت شده است.';
        }

        return null;
    }
}
