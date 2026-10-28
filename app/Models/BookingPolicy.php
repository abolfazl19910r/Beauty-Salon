<?php

namespace App\Models;

use App\Traits\BelongsToSalon;
use Illuminate\Database\Eloquent\Model;

/**
 * «نحوه‌ی رزرو» و محدودیت‌های نوبت مشتری هر سالن (بسته‌ی 5ه، ۲۰۲۶-۱۰-۲۸). یک ردیف برای هر سالن؛ مثل WalletSetting::get() با current()
 * ساخته می‌شود. درصد و حداقل پیش‌پرداخت همچنان در WalletSetting است.
 */
class BookingPolicy extends Model
{
    use BelongsToSalon;

    public const MODE_NONE = 'none';

    public const MODE_PREPAY = 'prepay';

    public const ATTENDANCE_OFF = 'off';

    /** فقط مشتری‌های بدقول */
    public const ATTENDANCE_FLAGGED = 'flagged';

    public const ATTENDANCE_ALL = 'all';

    protected $fillable = [
        'payment_mode',
        'payment_mode_fallback_at',
        'daily_limit',
        'active_limit',
        'no_show_threshold',
        'no_show_window_days',
        'flagged_force_pending',
        'flagged_active_limit',
        'attendance_check',
    ];

    protected $casts = [
        'payment_mode_fallback_at' => 'datetime',
        'daily_limit' => 'integer',
        'active_limit' => 'integer',
        'no_show_threshold' => 'integer',
        'no_show_window_days' => 'integer',
        'flagged_force_pending' => 'boolean',
        'flagged_active_limit' => 'integer',
    ];

    public static function current(): self
    {
        return self::first() ?? self::create([])->refresh();
    }

    public function wantsPrepayment(): bool
    {
        return $this->payment_mode === self::MODE_PREPAY;
    }

    /** سقف عددی؛ خالی یا صفر = بی‌سقف (null) */
    public static function limit(?int $value): ?int
    {
        return $value !== null && $value > 0 ? $value : null;
    }
}
