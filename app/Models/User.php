<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
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
        'verification_code',
        'verification_code_expire_at',
        'phone_verified_at',
        'login_verification_code',
        'login_verification_code_expire_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'verification_code',
        'login_verification_code',
    ];

    protected $casts = [
        'password' => 'hashed',
        'phone_verified_at' => 'datetime',
        'is_admin' => 'boolean',
        'verification_code_expire_at' => 'datetime',
        'login_verification_code_expire_at' => 'datetime',
    ];

    public function receivesBroadcastNotificationsOn()
    {
        return 'users.' . $this->id;
    }

    public function notifications()
    {
        return $this->morphMany(
            \App\Models\UserNotification::class,
            'notifiable'
        )->orderBy('created_at', 'desc');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

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

    public function routeNotificationForSms()
    {
        return $this->phone;
    }

    public function hasVerifiedPhone(): bool
    {
        return !is_null($this->phone_verified_at);
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

    public function routeNotificationForKavenegar(Notification $notification): string
    {
        return $this->phone;
    }

    public function hasPermission($permission)
    {
        if ($this->is_admin) {
            return true;
        }

        foreach ($this->roles as $role) {
            if ($role->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    public function getAllPermissions()
    {
        return $this->roles->flatMap->permissions->unique('id');
    }

    public function specialist(): HasOne
    {
        return $this->hasOne(Specialist::class, 'phone', 'phone');
    }

    /**
     *
     * @param int $points
     * @param string $description
     * @param int|null $bookingId
     * @return void
     */
    public function addLoyaltyPoints(int $points, string $description = '', ?int $bookingId = null): void
    {
        try {
            LoyaltyPoint::create([
                'user_id' => $this->id,
                'booking_id' => $bookingId,
                'points' => $points,
                'description' => $description,
                'type' => 'earned',
                'expires_at' => now()->addYear(),
            ]);

            \Illuminate\Support\Facades\Log::info('🎁 Loyalty points added to user', [
                'user_id' => $this->id,
                'points' => $points,
                'description' => $description,
                'booking_id' => $bookingId
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('❌ Failed to add loyalty points', [
                'user_id' => $this->id,
                'points' => $points,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     *
     * @return int
     */
    public function getTotalLoyaltyPoints(): int
    {
        try {
            return \App\Models\LoyaltyPoint::where('user_id', $this->id)
                ->where(function($query) {
                    $query->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })
                ->sum('points');
        } catch (\Exception $e) {
            return 0;
        }
    }
}
