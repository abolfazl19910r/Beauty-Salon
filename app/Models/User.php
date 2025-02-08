<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'phone',
        'password',
        'is_admin',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
        'is_admin' => 'boolean',
    ];

    public function hasVerifiedPhone(): bool
    {
        return !is_null($this->phone_verified_at);
    }

    public function routeNotificationForSms()
    {
        return $this->phone;
    }

    public function markPhoneAsVerified(): bool
    {
        return $this->forceFill([
            'phone_verified_at' => $this->freshTimestamp(),
            'verification_code' => null,
            'verification_code_expire_at' => null,
        ])->save();
    }

    public function loyaltyPoints(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LoyaltyPoint::class);
    }

    public function getCurrentPoints(): int
    {
        return LoyaltyPoint::getCurrentBalance($this->id);
    }

    public function getExpiringPoints($days = 30): int
    {
        return LoyaltyPoint::getExpiringPoints($this->id, $days);
    }

    public function getAvailableRewards()
    {
        $currentPoints = $this->getCurrentPoints();
        return Reward::where('is_active', true)
            ->where('required_points', '<=', $currentPoints)
            ->orderBy('required_points')
            ->get();
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
