<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * ⭐ «اطلاعیه‌ی سراسری» ابرمدیر به همه‌ی سالن‌ها (۲۰۲۶-۱۰-۱۵) — بنر بالای پنل مدیر و/یا متخصص. سطح پلتفرم، بدون BelongsToSalon.
 * با announcements خود سالن (اطلاعیه‌ی سالن برای مشتری‌هایش) فرق دارد.
 */
class PlatformAnnouncement extends Model
{
    public const LEVELS = ['info' => 'اطلاع', 'warning' => 'هشدار', 'critical' => 'مهم / قطعی'];

    public const AUDIENCES = ['admins' => 'مدیران سالن‌ها', 'specialists' => 'متخصص‌ها', 'all' => 'مدیران و متخصص‌ها'];

    protected $fillable = ['title', 'body', 'level', 'audience', 'starts_at', 'ends_at', 'is_active', 'created_by'];

    protected $casts = ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'is_active' => 'boolean'];

    /** فعال و در بازه‌ی نمایش، برای پنل «admins» یا «specialists» */
    public function scopeVisibleTo(Builder $query, string $panel): Builder
    {
        return $query->where('is_active', true)
            ->whereIn('audience', [$panel, 'all'])
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->latest();
    }

    public function isLive(): bool
    {
        return $this->is_active && (! $this->starts_at || $this->starts_at->lte(now())) && (! $this->ends_at || $this->ends_at->gt(now()));
    }
}
