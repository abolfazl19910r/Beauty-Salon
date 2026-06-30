<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BookingPolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): ?bool
    {
        if ($user->is_admin || $user->hasRole('manager')) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Booking $booking): bool
    {
        if ($booking->user_id === $user->id) {
            return true;
        }

        if ($user->hasRole('specialist') && $booking->specialist_id === $user->specialist?->id) {
            return true;
        }

        return false;
    }

    public function create(User $user): true
    {
        return true;
    }

    public function update(User $user, Booking $booking): bool
    {
        return $booking->user_id === $user->id && $booking->status === 'pending';
    }

    public function pay(User $user, Booking $booking): bool
    {
        return $booking->user_id === $user->id;
    }

    public function reschedule(User $user, Booking $booking): bool
    {
        if ($booking->user_id !== $user->id) {
            return false;
        }

        return $booking->canBeRescheduled();
    }

    public function changeStatus(User $user, Booking $booking): bool
    {
        return $user->hasRole('specialist') && $booking->specialist_id === $user->specialist?->id;
    }

    public function cancel(User $user, Booking $booking): bool
    {
        if ($booking->user_id !== $user->id) {
            return false;
        }

        return $booking->status !== 'cancelled'
            && $booking->booking_time > now()->addHours(24);
    }

    public function delete(User $user, Booking $booking): bool
    {
        return false;
    }
}
