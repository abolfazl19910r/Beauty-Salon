<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * استثنای دستی سوپرادمین روی یک امکان برای یک سالن (روشن یا خاموش اجباری، بالاتر از پلن)، با تاریخ انقضای اختیاری.
 * فقط سوپرادمین می‌نویسد و همیشه با salon_id صریح خوانده می‌شود (FeatureResolver)، پس scope سالن ندارد.
 */
class SalonFeatureOverride extends Model
{
    protected $fillable = ['salon_id', 'feature', 'enabled', 'note', 'set_by', 'expires_at'];

    protected $casts = [
        'enabled' => 'boolean',
        'expires_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        $forget = fn (self $o) => app(\App\Services\Plans\FeatureResolver::class)->forget((int) $o->salon_id);
        static::saved($forget);
        static::deleted($forget);
    }

    public function salon(): BelongsTo
    {
        return $this->belongsTo(Salon::class);
    }

    public function setBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'set_by');
    }

    public function scopeInEffect(Builder $query): Builder
    {
        return $query->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    public function isInEffect(): bool
    {
        return $this->expires_at === null || $this->expires_at->isFuture();
    }
}
