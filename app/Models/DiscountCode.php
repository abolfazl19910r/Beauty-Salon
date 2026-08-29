<?php

namespace App\Models;

use App\Traits\BelongsToSalon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscountCode extends Model
{
    use HasFactory, BelongsToSalon;

    protected $fillable = [
        'code',
        'type',
        'amount',
        'max_uses',
        'used_count',
        'expires_at',
        'is_active',
        'user_id',
        'max_amount',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'used_count' => 'integer',
        'max_uses' => 'integer',
        'expires_at' => 'datetime',
    ];

    public function bookings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Booking::class, 'discount_code', 'code');
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isValid(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->used_count >= $this->max_uses) {
            return false;
        }

        if ($this->expires_at) {
            if (is_string($this->expires_at)) {
                $expiresAt = \Carbon\Carbon::parse($this->expires_at);
            } else {
                $expiresAt = $this->expires_at;
            }

            if ($expiresAt->isPast()) {
                return false;
            }
        }

        return true;
    }

    public function incrementUsage(): void
    {
        $this->increment('used_count');
    }

    public function isForSpecificUser(): bool
    {
        return $this->user_id !== null;
    }

    public function canBeUsedBy(?int $userId): bool
    {
        if ($this->user_id === null) {
            return true;
        }

        return $this->user_id == $userId;
    }
}
