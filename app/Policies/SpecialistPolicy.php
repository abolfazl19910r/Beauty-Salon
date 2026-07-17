<?php

namespace App\Policies;

use App\Models\Leave;
use App\Models\Specialist;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SpecialistPolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): ?bool
    {
        if ($user->is_admin || $user->hasRole('manager')) {
            return true;
        }

        return null;
    }

    public function view(User $user, Specialist $specialist): bool
    {
        return $user->hasRole('specialist')
            && $user->specialist?->id === $specialist->id;
    }

    public function update(User $user, Specialist $specialist): bool
    {
        return $user->hasRole('specialist')
            && $user->specialist?->id === $specialist->id;
    }

    public function manageBookings(User $user, Specialist $specialist): bool
    {
        return $user->hasRole('specialist')
            && $user->specialist?->id === $specialist->id;
    }

    public function manageWallet(User $user, Specialist $specialist): bool
    {
        return $user->hasRole('specialist')
            && $user->specialist?->id === $specialist->id;
    }

    public function manageSchedule(User $user, Specialist $specialist): bool
    {
        return $user->hasRole('specialist')
            && $user->specialist?->id === $specialist->id;
    }

    public function manageLeaves(User $user, Specialist $specialist): bool
    {
        return $user->hasRole('specialist')
            && $user->specialist?->id === $specialist->id;
    }

    /**
     * ⭐ Critical fix (SpecialistLeave→Leave migration): This method previously type-hinted
     * SpecialistLeave $leave. After the migration,
     * SpecialistLeaveController::destroy() passes an instance of Leave —
     * Because Leave and SpecialistLeave are two completely separate classes (no parent/child relationship, just both on the leaves table), passing Leave as a parameter
     * that SpecialistLeave expects would immediately throw a fatal TypeError.
     */
    public function deleteLeave(User $user, Leave $leave): bool
    {
        return $user->hasRole('specialist')
            && $user->specialist?->id === $leave->specialist_id;
    }

    public function delete(User $user, Specialist $specialist): bool
    {
        return false;
    }
}
