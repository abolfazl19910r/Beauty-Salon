<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BookingPolicy
{
    use HandlesAuthorization;


    public function viewAny(User $user): bool
    {
        return $user->is_admin || $user->hasRole('manager');
    }

    public function view(User $user, Booking $booking): bool
    {
        return $user->is_admin ||
            $user->hasRole('manager') ||
            $booking->user_id === $user->id ||
            ($user->hasRole('specialist') && $booking->specialist_id === $user->specialist_id);
    }

    public function create(User $user): true
    {
        return true;
    }

    public function update(User $user, Booking $booking): bool
    {
        return $user->is_admin ||
            $user->hasRole('manager') ||
            ($booking->user_id === $user->id && $booking->status === 'pending');
    }

    public function changeStatus(User $user, Booking $booking): bool
    {
        return $user->is_admin ||
            $user->hasRole('manager') ||
            ($user->hasRole('specialist') && $booking->specialist_id === $user->specialist_id);
    }

    public function cancel(User $user, Booking $booking): bool
    {
        return $user->is_admin ||
            $user->hasRole('manager') ||
            ($booking->user_id === $user->id && $booking->status !== 'cancelled' && $booking->booking_time > now()->addHours(24));
    }

    public function delete(User $user, Booking $booking): bool
    {
        return $user->is_admin || $user->hasRole('manager');
    }
}
