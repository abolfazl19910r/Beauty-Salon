<?php

namespace App\Models;

use App\Traits\BelongsToSalon;
use Illuminate\Database\Eloquent\Model;

/** تنظیمات دوره‌های درمانی هر سالن (تصمیم ۷: سوختن جلسه‌ی لغو دیرهنگام، پیش‌فرض خاموش) */
class CourseSetting extends Model
{
    use BelongsToSalon;

    protected $fillable = ['salon_id', 'burn_late_cancellation'];

    protected $casts = ['burn_late_cancellation' => 'boolean'];

    public static function forSalon(int $salonId): self
    {
        return static::withoutGlobalScope('salon')->where('salon_id', $salonId)->first()
            ?? new static(['salon_id' => $salonId, 'burn_late_cancellation' => false]);
    }
}
