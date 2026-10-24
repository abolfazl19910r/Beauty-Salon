<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'salon_id',
        'subject_type',
        'subject_id',
        'event',
        'level',
        'ip_address',
        'user_agent',
        'context',
    ];

    protected $casts = [
        'context' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $log) {
            $log->created_at ??= now();
        });
    }

    public function salon(): BelongsTo
    {
        return $this->belongsTo(Salon::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getEventLabelAttribute(): string
    {
        return match ($this->event) {
            'login_attempt' => 'تلاش برای ورود',
            'session_terminated' => 'پایان یک نشست',
            'all_sessions_terminated' => 'پایان تمام نشست‌های دیگر',
            'payment_attempt' => 'تلاش برای پرداخت امن',
            'profile_change' => 'تغییر اطلاعات پروفایل',
            'booking_created' => 'ثبت نوبت',
            'booking_cancelled' => 'لغو نوبت',
            'booking_rescheduled' => 'جابه‌جایی نوبت',
            'review_created' => 'ثبت نظر',
            'ticket_created' => 'ثبت تیکت',
            'ticket_message_created' => 'پیام تیکت',
            'salon_created' => 'ثبت‌نام سالن',
            'password_changed' => 'تغییر رمز',
            'phone_changed' => 'تغییر شماره‌ی موبایل',
            'account_deleted' => 'حذف حساب',
            'payment_started' => 'شروع پرداخت',
            'payment_paid' => 'پرداخت موفق',
            'payment_failed' => 'پرداخت ناموفق',
            default => $this->event,
        };
    }
}
