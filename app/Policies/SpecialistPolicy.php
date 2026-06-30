<?php

namespace App\Policies;

use App\Models\Specialist;
use App\Models\SpecialistLeave;
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

    public function deleteLeave(User $user, SpecialistLeave $leave): bool
    {
        return $user->hasRole('specialist')
            && $user->specialist?->id === $leave->specialist_id;
    }

    public function delete(User $user, Specialist $specialist): bool
    {
        return false;
    }
}
