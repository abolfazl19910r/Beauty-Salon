<?php

namespace App\Policies;

use App\Models\SpecialistWallet;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SpecialistWalletPolicy
{
    use HandlesAuthorization;

    public function view(User $user, SpecialistWallet $wallet): bool
    {
        return $user->is_admin
            || $user->specialist?->id === $wallet->specialist_id;
    }

    public function requestWithdrawal(User $user, SpecialistWallet $wallet): bool
    {
        return $user->specialist?->id === $wallet->specialist_id;
    }

    public function updateIban(User $user, SpecialistWallet $wallet): bool
    {
        return $user->specialist?->id === $wallet->specialist_id;
    }
}
