<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ReviewToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'token',
        'booking_id',
        'user_id',
        'is_used',
        'expires_at',
        'used_at',
    ];

    protected $casts = [
        'is_used' => 'boolean',
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($reviewToken) {
            if (!$reviewToken->token) {
                $reviewToken->token = (string) Str::uuid();
            }

            if (!$reviewToken->expires_at) {
                $reviewToken->expires_at = now()->addDays(7);
            }
        });
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeValid($query)
    {
        return $query->where('is_used', false)
            ->where('expires_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    public function scopeUsed($query)
    {
        return $query->where('is_used', true);
    }

    public function isValid(): bool
    {
        return !$this->is_used && $this->expires_at->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function markAsUsed(): bool
    {
        return $this->update([
            'is_used' => true,
            'used_at' => now(),
        ]);
    }

    public static function createForBooking(Booking $booking): self
    {
        self::where('booking_id', $booking->id)->delete();

        return self::create([
            'booking_id' => $booking->id,
            'user_id' => $booking->user_id,
        ]);
    }

    public static function findValidToken(string $token): ?self
    {
        return self::where('token', $token)
            ->valid()
            ->first();
    }
}
