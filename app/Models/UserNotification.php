<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;

class UserNotification extends DatabaseNotification
{
    use HasFactory;

    protected $table = 'user_notifications';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'type',
        'user_id',
        'notifiable_type',
        'notifiable_id',
        'data',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }

            if (empty($model->user_id)) {
                $model->user_id = static::ownerUserId($model->notifiable_type, $model->notifiable_id);
            }
        });
    }

    /**
     * user_id کلید خارجی به users است؛ برای اعلانی که به مدل Specialist فرستاده می‌شه، صاحبش کاربرِ همون متخصصه
     * (ممکنه null باشه — متخصصی که ادمین قبل از ثبت‌نام خودش ساخته). قبلاً notifiable_id بی‌توجه به نوع اینجا
     * می‌نشست: روی MySQL خطای کلید خارجی (تغییر زمان نوبت ۵۰۰، امتیاز نظر داده نمی‌شد) یا نسبت‌دادن اعلان به یک
     * کاربر بی‌ربط.
     */
    public static function ownerUserId(?string $notifiableType, $notifiableId): ?int
    {
        if (! $notifiableId) {
            return null;
        }

        return match ($notifiableType) {
            (new User)->getMorphClass() => (int) $notifiableId,
            (new Specialist)->getMorphClass() => Specialist::withoutGlobalScopes()->whereKey($notifiableId)->value('user_id'),
            default => null,
        };
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
