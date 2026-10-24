<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * بایگانی هویت و IP حساب حذف‌شده (بسته‌ی مجوز 5د؛ ماده‌ی ۶۶۸ آیین دادرسی کیفری و ماده‌ی ۵۹ هـ قانون تجارت الکترونیکی).
 * سطح پلتفرم، فقط ابرمدیر (SuperAdmin\RetainedDataController)؛ نام، شماره و تاریخچه‌ی IP رمزنگاری‌شده با APP_KEY.
 * بعد از purge_after با retention:prune پاک می‌شود.
 */
class DeletedAccountArchive extends Model
{
    protected $fillable = [
        'user_id', 'salon_id', 'user_type', 'name', 'phone', 'account_created_at', 'ip_history',
        'deletion_ip', 'deletion_user_agent', 'deleted_by', 'outcome', 'deleted_at', 'purge_after',
    ];

    protected $casts = [
        'name' => 'encrypted',
        'phone' => 'encrypted',
        'ip_history' => 'encrypted:array',
        'account_created_at' => 'datetime',
        'deleted_at' => 'datetime',
        'purge_after' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $archive) {
            if ($archive->isDirty('phone')) {
                $archive->phone_hash = self::hashPhone((string) $archive->phone);
            }
        });
    }

    public static function hashPhone(string $phone): string
    {
        return hash_hmac('sha256', preg_replace('/\D+/', '', $phone) ?? '', (string) config('app.key'));
    }

    public static function findByPhone(string $phone): Builder
    {
        return self::query()->where('phone_hash', self::hashPhone($phone));
    }

    public function salon()
    {
        return $this->belongsTo(Salon::class);
    }
}
