<?php

namespace App\Models;

use App\Traits\BelongsToSalon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** یادداشت کوتاه یک جلسه‌ی دوره (ناحیه، تنظیمات دستگاه، واکنش پوست)؛ پیش‌فرض فقط برای کادر */
class CourseSessionNote extends Model
{
    use BelongsToSalon;

    public const MAX_LENGTH = 2000;

    protected $fillable = ['salon_id', 'customer_course_id', 'booking_id', 'specialist_id', 'note', 'visible_to_customer'];

    protected $casts = ['visible_to_customer' => 'boolean'];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function specialist(): BelongsTo
    {
        return $this->belongsTo(Specialist::class);
    }
}
