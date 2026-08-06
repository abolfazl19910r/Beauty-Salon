<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
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
            default => $this->event,
        };
    }
}
