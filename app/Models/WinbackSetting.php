<?php

namespace App\Models;

use App\Traits\BelongsToSalon;
use Illuminate\Database\Eloquent\Model;

/** تنظیمات «پیگیری مشتریان غایب» یک سالن (یک ردیف برای هر سالن، ۲۰۲۶-۱۰-۱۴) */
class WinbackSetting extends Model
{
    use BelongsToSalon;

    public const DEFAULT_LAPSE_DAYS = 60;

    protected $fillable = ['salon_id', 'enabled', 'lapse_days', 'discount_type', 'discount_amount', 'discount_max_amount', 'discount_valid_days'];

    protected $casts = [
        'enabled' => 'boolean',
        'lapse_days' => 'integer',
        'discount_amount' => 'decimal:2',
        'discount_max_amount' => 'decimal:2',
        'discount_valid_days' => 'integer',
    ];

    protected $attributes = [
        'enabled' => false,
        'lapse_days' => self::DEFAULT_LAPSE_DAYS,
        'discount_valid_days' => 14,
    ];

    /** ردیف سالن یا یک نمونه‌ی ذخیره‌نشده با پیش‌فرض‌ها */
    public static function forSalon(int $salonId): self
    {
        return static::withoutGlobalScope('salon')->where('salon_id', $salonId)->first()
            ?? new static(['salon_id' => $salonId]);
    }

    public function hasDiscount(): bool
    {
        return in_array($this->discount_type, ['fixed', 'percentage'], true) && (float) $this->discount_amount > 0;
    }

    /** «۱۵٪» یا «۵۰,۰۰۰ تومان» */
    public function discountLabel(): string
    {
        return $this->discount_type === 'percentage'
            ? rtrim(rtrim(number_format((float) $this->discount_amount, 2, '.', ''), '0'), '.').'٪'
            : number_format((float) $this->discount_amount).' تومان';
    }
}
