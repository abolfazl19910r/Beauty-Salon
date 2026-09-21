<?php

namespace App\Policies;

use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SupportTicketPolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return null;
    }

    public function view(User $user, SupportTicket $ticket): bool
    {
        return $ticket->user_id === $user->id;
    }

    public function reply(User $user, SupportTicket $ticket): bool
    {
        return $ticket->user_id === $user->id;
    }
}
