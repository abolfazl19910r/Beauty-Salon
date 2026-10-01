<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

/**
 * لینک کوتاه پیامک (App\Services\Links\ShortLinkService). عمداً بدون سالن: کد تصادفی ۶ حرفی است و فقط به آدرسی اشاره می‌کند
 * که خود برنامه ساخته؛ ۳۰ روز بعد از انقضا پاک می‌شود (model:prune).
 */
class ShortLink extends Model
{
    use MassPrunable;

    protected $fillable = ['code', 'target_url', 'expires_at'];

    protected $casts = ['expires_at' => 'datetime'];

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function prunable(): Builder
    {
        return static::where('expires_at', '<', now()->subDays(30));
    }
}
