<?php

namespace App\Models;

use App\Traits\BelongsToSalon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * یک درخواست یا تصمیم رضایت عکس دوره (۲۰۲۶-۱۰-۲۸) — App\Services\Courses\PhotoConsentService.
 */
class CoursePhotoConsent extends Model
{
    use BelongsToSalon;

    public const PENDING = 'pending';

    public const GRANTED = 'granted';

    public const DECLINED = 'declined';

    public const WITHDRAWN = 'withdrawn';

    public const CANCELLED = 'cancelled';

    public const STATUS_LABELS = [
        self::PENDING => 'در انتظار پاسخ مشتری',
        self::GRANTED => 'موافقت',
        self::DECLINED => 'مخالفت',
        self::WITHDRAWN => 'پس گرفتن رضایت',
        self::CANCELLED => 'درخواست بسته شد',
    ];

    public const SOURCE_LABELS = [
        'app' => 'اپ مشتری',
        'web' => 'سایت سالن',
        'sms_link' => 'لینک پیامک',
        'in_person' => 'ثبت حضوری',
    ];

    protected $fillable = [
        'salon_id', 'customer_course_id', 'user_id', 'status', 'source', 'text_version', 'requested_by', 'requested_at', 'token_hash',
        'expires_at', 'last_sent_at', 'send_count', 'decided_at', 'recorded_by', 'ip_address', 'user_agent',
    ];

    protected $hidden = ['token_hash'];

    protected $casts = [
        'requested_at' => 'datetime',
        'expires_at' => 'datetime',
        'last_sent_at' => 'datetime',
        'decided_at' => 'datetime',
        'send_count' => 'integer',
    ];

    public function customerCourse(): BelongsTo
    {
        return $this->belongsTo(CustomerCourse::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /** درخواست بی پاسخی که مهلتش نگذشته */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', self::PENDING)->where('expires_at', '>', now());
    }

    public function isOpen(): bool
    {
        return $this->status === self::PENDING && $this->expires_at?->isFuture();
    }

    /** برچسب سابقه: «منقضی» برای درخواست بی پاسخ گذشته، «ثبت حضوری» برای منبع in_person */
    public function label(): string
    {
        if ($this->status === self::PENDING && ! $this->isOpen()) {
            return 'درخواست بی پاسخ ماند (منقضی)';
        }

        $label = self::STATUS_LABELS[$this->status] ?? $this->status;

        return $this->source ? $label.' — '.(self::SOURCE_LABELS[$this->source] ?? $this->source) : $label;
    }
}
