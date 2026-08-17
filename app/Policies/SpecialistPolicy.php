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

    /**
     * ⭐ Critical fix (test-writing session 6, 2026-08-16): every ability below previously
     * required `$user->hasRole('specialist') && ...`. Nothing anywhere in the real
     * registration/specialist-creation flow ever assigns that role to a user — the
     * link between a User and a Specialist is entirely phone-match based
     * (`User::specialist()`), exactly as documented elsewhere in this project for the
     * withdrawal Form Request fix. The role check made every ability below permanently
     * false in production (confirmed with real HTTP requests: IBAN update, leave
     * index/store, schedule update, profile update, and review show/respond all
     * returned 403 for every specialist, with no way to unblock them short of an
     * admin manually visiting /admin/roles/assign — a step nowhere documented as part
     * of the specialist-creation workflow). `SpecialistWalletPolicy` in this same
     * codebase already used the correct phone-match-only pattern; these abilities are
     * now aligned with it.
     */
    public function view(User $user, Specialist $specialist): bool
    {
        return $user->specialist?->id === $specialist->id;
    }

    public function update(User $user, Specialist $specialist): bool
    {
        return $user->specialist?->id === $specialist->id;
    }

    public function manageBookings(User $user, Specialist $specialist): bool
    {
        return $user->specialist?->id === $specialist->id;
    }

    public function manageWallet(User $user, Specialist $specialist): bool
    {
        return $user->specialist?->id === $specialist->id;
    }

    public function manageSchedule(User $user, Specialist $specialist): bool
    {
        return $user->specialist?->id === $specialist->id;
    }

    public function manageLeaves(User $user, Specialist $specialist): bool
    {
        return $user->specialist?->id === $specialist->id;
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
        return $user->specialist?->id === $leave->specialist_id;
    }

    public function delete(User $user, Specialist $specialist): bool
    {
        return false;
    }
}
