<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LoyaltyPoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'booking_id',
        'points',
        'type',
        'description',
        'expires_at'
    ];

    protected $casts = [
        'points' => 'integer',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function scopeActive($query)
    {
        return $query->where(function($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    public function scopeEarned($query)
    {
        return $query->where('type', 'earned');
    }

    public function scopeSpent($query)
    {
        return $query->where('type', 'spent');
    }

    public static function calculatePointsForBooking(Booking $booking): int
    {
        return (int) floor($booking->prepayment_amount / 10000);
    }

    public static function getCurrentBalance($userId): int
    {
        return static::where('user_id', $userId)
            ->active()
            ->selectRaw('COALESCE(SUM(CASE WHEN type = "earned" THEN points ELSE -points END), 0) as balance')
            ->value('balance');
    }

    public static function getExpiringPoints($userId, $days = 30): int
    {
        return static::where('user_id', $userId)
            ->where('type', 'earned')
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now(), now()->addDays($days)])
            ->sum('points');
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function getRemainingDays(): ?int
    {
        if (!$this->expires_at) return null;
        return max(0, now()->diffInDays($this->expires_at, false));
    }
}
