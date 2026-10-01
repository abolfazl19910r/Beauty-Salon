<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

/** کد یک‌بارمصرف اتصال ربات (فقط hash ذخیره می‌شود)؛ ۱۰ دقیقه اعتبار، یک روز بعد پاک می‌شود (model:prune). */
class BotLinkCode extends Model
{
    use MassPrunable;

    protected $fillable = ['user_id', 'salon_id', 'code_hash', 'expires_at', 'used_at'];

    protected $casts = ['expires_at' => 'datetime', 'used_at' => 'datetime'];

    public function prunable(): Builder
    {
        return static::where('created_at', '<', now()->subDay());
    }
}
