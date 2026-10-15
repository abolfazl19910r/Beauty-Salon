<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * گیرنده‌ی پوش یک نشست اپ (بسته‌ی ۴). یک ردیف برای هر توکن Sanctum؛ با حذف توکن (خروج، ابطال، انقضای ۹۰ روزه) پاک می‌شود.
 */
class PushDevice extends Model
{
    public const APP_CUSTOMER = 'customer';

    public const APP_STAFF = 'staff';

    public const PROVIDER_FCM = 'fcm';

    public const PROVIDER_NTFY = 'ntfy';

    protected $fillable = [
        'user_id', 'personal_access_token_id', 'salon_id', 'app', 'provider', 'token', 'token_hash',
        'app_version', 'manufacturer', 'model', 'os_version', 'last_receipt_at', 'disabled_at', 'disabled_reason',
    ];

    protected $hidden = ['token'];

    protected $casts = [
        'last_receipt_at' => 'datetime',
        'disabled_at' => 'datetime',
    ];

    public static function hashToken(string $provider, string $token): string
    {
        return hash('sha256', $provider.'|'.$token);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('disabled_at');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(PushDelivery::class);
    }

    public function disable(string $reason): void
    {
        $this->forceFill(['disabled_at' => now(), 'disabled_reason' => mb_substr($reason, 0, 64)])->save();
    }
}
