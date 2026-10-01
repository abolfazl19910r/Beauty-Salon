<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * گفت‌وگوی وصل‌شده‌ی یک کاربر در بله یا تلگرام (ربات پلتفرم). بدون scope سالن: اتصال مال کاربر است (کادر ممکن است
 * در چند سالن باشد)؛ salon_id فقط سالنی است که اتصال از آن ساخته شد.
 */
class BotLink extends Model
{
    public const MESSENGERS = ['bale', 'telegram'];

    protected $fillable = ['user_id', 'messenger', 'chat_id', 'salon_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
