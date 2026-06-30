<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserWallet;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserWalletPolicy
{
    use HandlesAuthorization;

    public function view(User $user, UserWallet $wallet): bool
    {
        return $user->is_admin || $wallet->user_id === $user->id;
    }

    public function withdraw(User $user, UserWallet $wallet): bool
    {
        return $wallet->user_id === $user->id;
    }

    public function charge(User $user, UserWallet $wallet): bool
    {
        return $wallet->user_id === $user->id;
    }
}
