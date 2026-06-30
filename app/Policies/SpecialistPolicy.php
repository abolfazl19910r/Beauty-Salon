<?php

namespace App\Policies;

use App\Models\Specialist;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SpecialistPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->is_admin || $user->hasRole('manager');
    }

    public function view(User $user, Specialist $specialist): bool
    {
        return $user->is_admin
            || $user->hasRole('manager')
            || $specialist->user_id === $user->id;
    }

    public function update(User $user, Specialist $specialist): bool
    {
        return $user->is_admin
            || $user->hasRole('manager')
            || $specialist->user_id === $user->id;
    }

    public function manageWallet(User $user, Specialist $specialist): bool
    {
        return $user->is_admin
            || $specialist->user_id === $user->id;
    }

    public function manageSchedule(User $user, Specialist $specialist): bool
    {
        return $user->is_admin
            || $user->hasRole('manager')
            || $specialist->user_id === $user->id;
    }

    public function delete(User $user, Specialist $specialist): bool
    {
        return $user->is_admin;
    }
}
