<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
        // ⭐ Customer identity redesign (confirmed 2026-08-30): salon_id/user_type only ever set
        // explicitly at the two creation points that matter (CustomerRegisteredController::store()
        // sets both; admin/specialist creation leaves both at their defaults — null salon_id,
        // 'customer' user_type default is overwritten to 'staff' only by the backfill migration
        // for existing rows, per-request logic doesn't need to set it going forward since neither
        // AdminSpecialistService nor the staff RegisteredUserController assign salon_id/user_type
        // themselves).
        'salon_id',
        'user_type',
        'verification_code',
        'verification_code_expire_at',
        'phone_verified_at',
        'login_verification_code',
        'login_verification_code_expire_at',
        'two_factor_enabled',
        'two_factor_code',
        'two_factor_code_expires_at',
        'password_changed_at',
        'password_strength_score',
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
        'two_factor_enabled' => 'boolean',
        'two_factor_code_expires_at' => 'datetime',
        'password_changed_at' => 'datetime',
        'password_strength_score' => 'integer',
    ];

    public function securityLogs(): HasMany
    {
        return $this->hasMany(SecurityLog::class);
    }

    public function receivesBroadcastNotificationsOn()
    {
        return 'users.'.$this->id;
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

    /**
     * ⭐ Phase 1 SaaS multi-tenant (feat/saas-multi-tenant-salons, commit 2): v1 always has at
     * most one row here per admin user (SuperAdminService::createSalonWithAdmin() enforces
     * "at most one owner per salon", and a user is only ever owner of one salon in v1) — phase 2
     * ("چند ادمین روی یک سالن") is what actually uses the many-to-many shape this enables.
     */
    public function salons(): BelongsToMany
    {
        return $this->belongsToMany(Salon::class, 'salon_admins')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function hasRole($role): bool
    {
        if (is_string($role)) {
            return $this->roles->contains('name', $role);
        }

        return (bool) $role->intersect($this->roles)->count();
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
            if (! $this->hasRole($role)) {
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
        return ! is_null($this->phone_verified_at);
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
     * ⚠️ Integration (R-AdminLoyalty phase): Previously expires_at was always addYear()
     * * hardcoded. Now it reads from loyalty_settings (key points_expiry_months)
     * * — the same source that BookingObserver and LoyaltyService also read.
     */
    public function addLoyaltyPoints(int $points, string $description = '', ?int $bookingId = null): void
    {
        try {
            $expiryMonths = (int) LoyaltySetting::getValue('points_expiry_months', 12);
            $expiryMonths = $expiryMonths > 0 ? $expiryMonths : 12;

            $loyaltyPoint = LoyaltyPoint::create([
                'user_id' => $this->id,
                'booking_id' => $bookingId,
                'points' => $points,
                'description' => $description,
                'type' => 'earned',
                'expires_at' => now()->addMonths($expiryMonths),
            ]);

            \Illuminate\Support\Facades\Cache::forget("user:{$this->id}:loyalty_points");

            // ⭐ Fix: this raw entry point (used by BookingObserver for normal booking points,
            // BookingController::rate() for quick-rating bonus points, and ReviewService::createReview()
            // for full-review bonus points) never actually notified the customer that they earned
            // points — only LoyaltyService::earnPointsFromBooking() (a completely different, unused
            // code path) had that notification wired. Customers submitting a review, in particular,
            // never found out they'd been awarded points. Dispatching it here covers all three
            // real call sites at once, through the same PointsEarned notification/event key already
            // used elsewhere (LOYALTY_POINTS_EARNED_CUSTOMER, settings-gated, admin can toggle it).
            $this->notify(new \App\Notifications\Loyalty\PointsEarned($loyaltyPoint));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('❌ Failed to add loyalty points', [
                'user_id' => $this->id,
                'points' => $points,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function getTotalLoyaltyPoints(): int
    {
        try {
            return \App\Models\LoyaltyPoint::where('user_id', $this->id)
                ->where(function ($query) {
                    $query->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })
                ->sum('points');
        } catch (\Exception $e) {
            return 0;
        }
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(UserWallet::class);
    }

    public function getOrCreateWallet(): UserWallet
    {
        if (! $this->wallet) {
            $this->wallet()->create([
                'balance' => 0,
                'total_deposited' => 0,
                'total_spent' => 0,
            ]);
            $this->load('wallet');
        }

        return $this->wallet;
    }

    public function hasBalance(float $amount): bool
    {
        $wallet = $this->getOrCreateWallet();

        return $wallet->balance >= $amount;
    }
}
