<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReviewPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->is_admin || $user->hasRole('manager');
    }

    public function view(User $user, Review $review): bool
    {
        return $user->is_admin
            || $user->hasRole('manager')
            || $review->user_id === $user->id
            || ($user->hasRole('specialist') && $user->specialist?->id === $review->specialist_id);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Review $review): bool
    {
        return $review->user_id === $user->id && $review->canBeEdited();
    }

    public function respond(User $user, Review $review): bool
    {
        return $user->is_admin
            || ($user->hasRole('specialist') && $user->specialist?->id === $review->specialist_id);
    }

    public function delete(User $user, Review $review): bool
    {
        return $user->is_admin || $user->hasRole('manager');
    }
}
