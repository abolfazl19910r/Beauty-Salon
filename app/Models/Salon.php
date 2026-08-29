<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ⭐ Phase 1 SaaS multi-tenant (feat/saas-multi-tenant-salons, commit 2). See the "⭐⭐ فیچر
 * برنامه‌ریزی‌شده (بازنگری نهایی — SaaS چندسالنی)" section of Rasta_unified_prompt.md for the
 * full architecture. `slug` is the salon's immutable public URL (/s/{slug}); `name` is the
 * display name its own admin can rename later — kept as two separate columns specifically so
 * renaming never breaks a bookmarked/SMS'd link.
 */
class Salon extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'max_specialists_count',
        'module_permissions',
        'subscription_type',
        'subscription_started_at',
        'subscription_ends_at',
        'is_suspended',
        'created_by',
    ];

    protected $casts = [
        'module_permissions' => 'array',
        'subscription_started_at' => 'datetime',
        'subscription_ends_at' => 'datetime',
        'is_suspended' => 'boolean',
    ];

    /**
     * v1: exactly one 'owner' row per salon (enforced in SuperAdminService::createSalonWithAdmin(),
     * not here — see salon_admins migration docblock for why this is an app-level rule rather
     * than a DB constraint). Phase 2 ("چند ادمین روی یک سالن") adds 'staff' rows to the same table.
     */
    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'salon_admins')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function owner(): ?User
    {
        return $this->admins()->wherePivot('role', 'owner')->first();
    }

    public function specialists(): HasMany
    {
        return $this->hasMany(Specialist::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Deliberately separate from is_suspended — a salon can be un-suspended but still past its
     * subscription_ends_at, or suspended while its subscription is technically still active
     * (a manual super-admin action, e.g. pending a support issue). Both middleware checks
     * (EnsureAdminSalonActive, ResolveSalonFromRoute) test both conditions independently.
     */
    public function hasActiveSubscription(): bool
    {
        return ! $this->is_suspended && $this->subscription_ends_at->isFuture();
    }
}
