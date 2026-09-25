<?php

namespace App\Models;

use App\Support\CurrentSalon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationSetting extends Model
{
    use HasFactory;

    /**
     * مثل BelongsToSalon (ولی بدون global scope — خواندن همیشه با salon_id صریح است، چون اعلان‌های صف‌شده سالن
     * جاری ندارند): ردیفی که در بافت یک سالن ساخته می‌شود مال همان سالن است، مگر salon_id صریحاً داده شود (حتی null).
     */
    protected static function booted(): void
    {
        static::creating(function (self $setting) {
            if (! array_key_exists('salon_id', $setting->getAttributes())) {
                $setting->salon_id = app(CurrentSalon::class)->id();
            }
        });
    }

    protected $fillable = [
        'salon_id',
        'event_key',
        'label',
        'sms_enabled',
        'database_enabled',
        'telegram_enabled',
    ];

    protected $casts = [
        'sms_enabled' => 'boolean',
        'database_enabled' => 'boolean',
        'telegram_enabled' => 'boolean',
    ];
}
