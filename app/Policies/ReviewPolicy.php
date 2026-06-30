<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReviewPolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): ?bool
    {
        if ($user->is_admin || $user->hasRole('manager')) {
            return true;
        }

        return null;
    }

    public function view(User $user, Review $review): bool
    {
        if ($review->user_id === $user->id) {
            return true;
        }

        if ($user->hasRole('specialist') && $review->specialist_id === $user->specialist?->id) {
            return true;
        }

        return false;
    }

    public function create(User $user): true
    {
        return true;
    }

    public function update(User $user, Review $review): bool
    {
        return $review->user_id === $user->id && !$review->is_approved;
    }

    public function delete(User $user, Review $review): bool
    {
        return false;
    }

    public function respond(User $user, Review $review): bool
    {
        return $user->hasRole('specialist')
            && $review->specialist_id === $user->specialist?->id;
    }

    public function moderate(User $user, Review $review): bool
    {
        return false;
    }
}
