<?php

namespace App\Models;

use App\Providers\RouteServiceProvider;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Support\CurrentSalon;
use Illuminate\Database\Eloquent\Casts\Attribute;
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

    /**
     * ⭐ ابطال خودکار توکن‌های اپ موبایل (بسته‌ی ۱ اپلیکیشن، ۲۰۲۶-۱۰-۱۰):
     *  - تغییر رمز (از هر مسیری — رمز در ۸ جا عوض می‌شود: پروفایل‌ها، بازیابی، تنظیم مدیر، ...) همه‌ی
     *    دستگاه‌ها را خارج می‌کند؛ همان انتظار کاربری که رمزش لو رفته.
     *  - تغییر user_type/salon_id (مثلاً AdminSpecialistService مشتری را کادر می‌کند): توکن اپ قبلی دیگر
     *    به حساب درستی اشاره نمی‌کند (EnsureApiAudience هم wrong_app می‌داد؛ اینجا پاک هم می‌شود).
     *  - حذف کاربر: personal_access_tokens کلید خارجی ندارد (morph)، پس ردیف یتیم نماند.
     * فقط برای save مدل اجرا می‌شود، نه update گروهی کوئری.
     */
    protected static function booted(): void
    {
        static::updated(function (User $user) {
            if ($user->wasChanged(['password', 'user_type', 'salon_id'])) {
                $user->tokens()->delete();
                // بسته‌ی ۶: «مرا به خاطر بسپار» و دستگاه‌های شناخته‌شده‌ی وب هم با تغییر رمز باطل می‌شوند
                $user->revokeRememberedDevices();
            }
        });

        static::deleting(function (User $user) {
            $user->tokens()->delete();
        });
    }

    /**
     * «مرا به خاطر بسپار» وب (بسته‌ی ۶): دستگاه‌های شناخته‌شده پاک و remember_token عوض می‌شود (کوکی remember همه‌ی مرورگرها بی‌اثر).
     */
    public function revokeRememberedDevices(): void
    {
        \App\Models\TrustedDevice::query()->where('user_id', $this->id)->delete();
        $this->forceFill(['remember_token' => \Illuminate\Support\Str::random(60)])->saveQuietly();
    }

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
        'marketing_sms_opted_out_at' => 'datetime',
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
        return \App\Models\UserNotification::limitToCurrentSalon($this->morphMany(
            \App\Models\UserNotification::class,
            'notifiable'
        )->orderBy('created_at', 'desc'));
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withPivot('salon_id');
    }

    /** نقش این کاربر در هر سالن (salon_admins.role) — حافظه‌ی همین نمونه، برای is_admin مؤثر */
    private array $salonRoleCache = [];

    /**
     * ⭐ مالک/منشی چندسالنی (تصمیم ۵، ۲۰۲۶-۱۰-۲۲): نقش‌هایی که در سالن جاری کار می‌کنند. بیرون از سالن (سوپرادمین، کنسول، صف
     * بدون سالن) همه‌ی نقش‌ها. درون سالن: فقط نقش‌های سیستمی یا همان سالن (roles.salon_id) که یا سراسری داده شده‌اند
     * (role_user.salon_id = null: سوپرادمین و ردیف‌های قدیمی کاربرانِ تک‌سالنی) یا در همین سالن. فیلتر روی مجموعه‌ی بارشده است
     * (نه فقط scope کوئری Role) چون نقش‌ها ممکن است پیش از ست شدن CurrentSalon بار شده باشند.
     */
    public function effectiveRoles(bool $withPermissions = false): \Illuminate\Support\Collection
    {
        $roles = ($withPermissions ? $this->loadMissing('roles.permissions') : $this->loadMissing('roles'))->roles;
        $salonId = app(CurrentSalon::class)->id();

        if ($salonId === null) {
            return $roles;
        }

        return $roles->filter(function (Role $role) use ($salonId) {
            $pivotSalon = $role->pivot?->salon_id;

            return ($role->salon_id === null || (int) $role->salon_id === $salonId)
                && ($pivotSalon === null || (int) $pivotSalon === $salonId);
        })->values();
    }

    /**
     * is_admin مؤثر (تصمیم ۵): ستون users.is_admin سراسری است و دور زدن کامل مجوزها را می‌دهد. کسی که در یک سالن مالک (is_admin)
     * و در سالن دیگر منشی (یا فقط متخصص) است، آنجا نباید این دور زدن را داشته باشد — پس وقتی سالن جاری ست است، فقط اگر در
     * همان سالن مالک باشد. فقط کم می‌کند، هرگز زیاد نمی‌کند (ستون false همیشه false).
     */
    protected function isAdmin(): Attribute
    {
        return Attribute::get(function ($value) {
            if (! $value) {
                return false;
            }

            $salonId = app(CurrentSalon::class)->id();
            if ($salonId === null || ! $this->exists || $this->hasRole('super-admin')) {
                return true;
            }

            // درون یک سالن فقط مالکِ همان سالن؛ منشی یا غیرعضو (مثلاً متخصص که در سالن دیگری مالک است) نه
            return $this->salonRoleIn($salonId) === 'owner';
        });
    }

    /** owner | staff | null (عضو این سالن نیست) */
    public function salonRoleIn(int $salonId): ?string
    {
        if (! array_key_exists($salonId, $this->salonRoleCache)) {
            $this->salonRoleCache[$salonId] = \Illuminate\Support\Facades\DB::table('salon_admins')
                ->where('user_id', $this->id)->where('salon_id', $salonId)->value('role');
        }

        return $this->salonRoleCache[$salonId];
    }

    public function forgetSalonRoles(): void
    {
        $this->salonRoleCache = [];
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
            return $this->effectiveRoles()->contains('name', $role);
        }

        return (bool) $role->intersect($this->effectiveRoles())->count();
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
            $role = app(RoleRepositoryInterface::class)->findByNameOrFail($role);
        }

        // در پنل سالن: نقش فقط در همین سالن (role_user.salon_id، تصمیم ۵)
        $salonId = app(CurrentSalon::class)->id();
        $roleId = $role instanceof Role ? $role->id : (int) $role;
        \App\Support\RoleAssignments::give($this, [$roleId], $salonId);
        $this->unsetRelation('roles');

        return $this;
    }

    public function removeRole($role): static
    {
        if (is_string($role)) {
            $role = app(RoleRepositoryInterface::class)->findByNameOrFail($role);
        }

        $salonId = app(CurrentSalon::class)->id();
        $roleId = $role instanceof Role ? $role->id : (int) $role;
        \Illuminate\Support\Facades\DB::table('role_user')->where('user_id', $this->id)->where('role_id', $roleId)
            ->when($salonId !== null, fn ($q) => $q->where(fn ($w) => $w->whereNull('salon_id')->orWhere('salon_id', $salonId)))
            ->delete();
        $this->unsetRelation('roles');

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

        // نقش‌ها با مجوزهایشان یک‌جا (بدون N+1 روی هر نقش)؛ فقط نقش‌های مؤثر در سالن جاری (تصمیم ۵)
        foreach ($this->effectiveRoles(true) as $role) {
            if ($role->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    public function getAllPermissions()
    {
        return $this->effectiveRoles(true)->flatMap->permissions->unique('id');
    }

    /**
     * صفحه‌ی خانه‌ی کاربر بعد از ورود (و وقتی کاربرِ واردشده صفحه‌ی مهمان را باز می‌کند).
     * یک‌جا تعریف شده تا ورود و RedirectIfAuthenticated از هم جدا نشوند.
     *
     * نقش «specialist» در عمل به متخصص‌ها داده نمی‌شود (متخصص با شماره به users وصل می‌شود)،
     * پس متخصص بودن از رکورد specialist خوانده می‌شود؛ مدیر سالن اولویت دارد.
     */
    public function homePath(): string
    {
        if ($this->hasRole('super-admin')) {
            return '/superadmin/dashboard';
        }

        if ($this->hasRole('specialists') || $this->hasRole('specialist')) {
            return RouteServiceProvider::SPECIALIST_HOME;
        }

        if ($this->is_admin) {
            return RouteServiceProvider::HOME;
        }

        if ($this->user_type === 'staff'
            && app(CurrentSalon::class)->allSalons(fn () => $this->specialist()->exists())) {
            return RouteServiceProvider::SPECIALIST_HOME;
        }

        return RouteServiceProvider::USER_HOME;
    }

    private ?bool $specialistAccountCache = null;

    /**
     * کادری که رکورد متخصص دارد (با شماره، در هر سالن). نقش «specialists» در عمل داده نمی‌شود (homePath)، پس منوی سایت سالن
     * با این تشخیص می‌دهد (۲۰۲۶-۱۰-۲۲: متخصص بعد از رزرو برای خودش در سایت سالن لینکی به پنلش نمی‌دید).
     */
    public function isSpecialistAccount(): bool
    {
        return $this->specialistAccountCache ??= $this->user_type === 'staff'
            && app(CurrentSalon::class)->allSalons(fn () => $this->specialist()->exists());
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
        // باشگاه مشتریان در پلن سالن مشتری نیست ← امتیاز تازه داده نمی‌شود (امتیاز قبلی قابل استفاده می‌ماند) — بسته‌ی پلن‌ها
        $salonId = $this->salon_id ?? app(\App\Support\CurrentSalon::class)->id();
        if ($salonId && ! Salon::featureEnabledFor((int) $salonId, \App\Support\Features\Feature::Loyalty)) {
            return;
        }

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
        return app(\App\Services\Loyalty\LoyaltyLedger::class)->balance($this->id);
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
