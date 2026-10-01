<?php

namespace App\Models;

use App\Support\Idempotency;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

/**
 * کلید idempotency یک فرم (App\Support\Idempotency). بدون سالن: صاحب (owner_id) کاربر یا متخصص است و scope نوع کار را
 * می‌گوید؛ رکورد ساخته‌شده از مسیر عادی و scope سالن خودش خوانده می‌شود.
 */
class IdempotencyKey extends Model
{
    use MassPrunable;

    protected $fillable = ['scope', 'owner_id', 'key', 'fingerprint', 'resource_id'];

    public function prunable(): Builder
    {
        return static::where('created_at', '<', now()->subHours(Idempotency::TTL_HOURS));
    }
}
