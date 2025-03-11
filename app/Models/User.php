<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        'phone_verified_at' => 'datetime',
        'is_admin' => 'boolean',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     *
     * @param string $role
     * @return bool
     */
    public function hasRole($role): bool
    {
        if (is_string($role)) {
            return $this->roles->contains('name', $role);
        }

        return !! $role->intersect($this->roles)->count();
    }

    public function hasAnyRole($roles)
    {
        if (is_string($roles)) {
            return $this->hasRole($roles);
        }

        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    public function hasAllRoles($roles)
    {
        if (is_string($roles)) {
            return $this->hasRole($roles);
        }

        foreach ($roles as $role) {
            if (!$this->hasRole($role)) {
                return false;
            }
        }

        return true;
    }

    public function assignRole($role): static
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->firstOrFail();
        }

        $this->roles()->syncWithoutDetaching($role);

        return $this;
    }

    public function removeRole($role): static
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->firstOrFail();
        }

        $this->roles()->detach($role);

        return $this;
    }

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

    public function loyaltyPoints(): HasMany
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
