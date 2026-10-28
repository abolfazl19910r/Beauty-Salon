<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * یک پیام اپ به یک نفر (بسته‌ی ۴) با متن پیامک پشتیبانش. شناسه همان چیزی است که اپ در رسید برمی‌گرداند.
 *
 * شناسه UUID نسخه‌ی ۷ است (HasUuids از Laravel 12؛ تصمیم ابوالفضل ۲۰۲۶-۱۰-۲۸): ۴۸ بیت اول زمان ساخت به میلی‌ثانیه است،
 * پس «جدیدترین پیام‌ها» با orderByDesc('id') گرفته می‌شوند (دقیق‌تر از created_at ثانیه‌ای و روی همان ایندکس کلید اصلی).
 */
class PushMessage extends Model
{
    use HasUuids, Prunable;

    protected $fillable = [
        'user_id', 'salon_id', 'app', 'event_key', 'urgency', 'mode', 'title', 'body', 'data',
        'sms_phone', 'sms_text', 'sms_feature', 'sms_parts', 'fallback_due_at', 'acked_at', 'sms_sent_at', 'sms_reason',
    ];

    protected $hidden = ['sms_phone'];

    protected $casts = [
        'data' => 'array',
        'fallback_due_at' => 'datetime',
        'acked_at' => 'datetime',
        'sms_sent_at' => 'datetime',
    ];

    /** پاک‌سازی روزانه (model:prune): آمار ابرمدیر فقط بازه‌ی اخیر را می‌خواهد */
    public function prunable(): Builder
    {
        return static::query()->where('created_at', '<', now()->subDays((int) config('push.retention_days')));
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(PushDelivery::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
