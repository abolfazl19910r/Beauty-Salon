<?php

namespace App\Models;

use App\Traits\BelongsToSalon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * عکس قبل/بعد یک جلسه (تصمیم ۶): فقط دیسک خصوصی course_photos، فقط با رضایت ثبت‌شده روی دوره، دیدنی برای کادر همان سالن و خود
 * مشتری؛ مشتری می‌تواند عکس‌هایش را حذف کند. حذف ردیف = حذف فایل.
 */
class CourseSessionPhoto extends Model
{
    use BelongsToSalon;

    public const DISK = 'course_photos';

    public const MAX_PER_SESSION = 4;

    public const KINDS = ['before' => 'قبل', 'after' => 'بعد'];

    protected $fillable = ['salon_id', 'customer_course_id', 'booking_id', 'kind', 'path', 'uploaded_by'];

    protected static function booted(): void
    {
        static::deleted(fn (self $photo) => Storage::disk(self::DISK)->delete($photo->path));
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function customerCourse(): BelongsTo
    {
        return $this->belongsTo(CustomerCourse::class);
    }
}
