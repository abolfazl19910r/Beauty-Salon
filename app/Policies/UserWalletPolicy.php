<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserWallet;
use App\Models\UserWalletTransaction;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserWalletPolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): ?bool
    {
        if ($user->is_admin || $user->hasRole('manager')) {
            return true;
        }

        return null;
    }

    public function view(User $user, UserWallet $wallet): bool
    {
        return $wallet->user_id === $user->id;
    }

    public function charge(User $user, UserWallet $wallet): bool
    {
        return $wallet->user_id === $user->id;
    }

    public function viewTransaction(User $user, UserWalletTransaction $transaction): bool
    {
        return $transaction->wallet->user_id === $user->id;
    }
}
