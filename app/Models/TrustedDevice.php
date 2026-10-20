<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * دستگاه شناخته‌شده‌ی ورود وب (بسته‌ی ۶): App\Services\Auth\TrustedDeviceService. مال کاربر است (TenantModelClassificationTest).
 */
class TrustedDevice extends Model
{
    protected $fillable = ['user_id', 'token_hash', 'user_agent', 'ip_address', 'last_used_at', 'expires_at'];

    protected $casts = ['last_used_at' => 'datetime', 'expires_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
