<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reward extends Model
{
    protected $fillable = [
        'title',
        'description',
        'required_points',
        'discount_type',
        'discount_amount',
        'is_active',
        'max_uses',
        'used_count'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'required_points' => 'integer',
        'discount_amount' => 'decimal:2',
        'max_uses' => 'integer',
        'used_count' => 'integer'
    ];

    public function isAvailableForUser(User $user): bool
    {
        if (!$this->is_active) return false;

        if ($this->max_uses && $this->used_count >= $this->max_uses) return false;

        $userPoints = LoyaltyPoint::where('user_id', $user->id)->sum('points');
        return $userPoints >= $this->required_points;
    }

    public function incrementUsage()
    {
        $this->increment('used_count');
    }
}
