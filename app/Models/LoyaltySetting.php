<?php

namespace App\Models;

use App\Traits\BelongsToSalon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoyaltySetting extends Model
{
    use BelongsToSalon, HasFactory;

    protected $table = 'loyalty_settings';

    protected $fillable = [
        'key',
        'value',
        'description',
    ];

    public static function getValue(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();

        return $setting ? $setting->value : $default;
    }

    public static function setValue(string $key, string|int|null $value, ?string $description = null): void
    {
        static::updateOrCreate(['key' => $key], ['value' => (string) ($value ?? ''), 'description' => $description]);
    }

    /** بسته‌ی 5ه، تصمیم ۹: امتیاز نوبت‌های «بدون پیش‌پرداخت» — کلید مدیر و دو عدد که خودش وارد می‌کند (هیچ پیش‌فرضی در کد نیست) */
    public const NO_PREPAY_ENABLED = 'no_prepay_points_enabled';

    public const NO_PREPAY_FIXED = 'no_prepay_fixed_points';

    public const NO_PREPAY_PER_AMOUNT = 'no_prepay_amount_per_point';

    /**
     * @return array{fixed: int, per_amount: int}|null null = خاموش یا یکی از دو عدد وارد نشده (پس امتیازی داده نمی‌شود)
     */
    public static function noPrepaymentPoints(): ?array
    {
        $values = static::whereIn('key', [self::NO_PREPAY_ENABLED, self::NO_PREPAY_FIXED, self::NO_PREPAY_PER_AMOUNT])->pluck('value', 'key');

        if (($values[self::NO_PREPAY_ENABLED] ?? '0') !== '1') {
            return null;
        }

        $fixed = $values[self::NO_PREPAY_FIXED] ?? '';
        $per = $values[self::NO_PREPAY_PER_AMOUNT] ?? '';
        if (! is_numeric($fixed) || ! is_numeric($per) || (int) $per < 1) {
            return null;
        }

        return ['fixed' => (int) $fixed, 'per_amount' => (int) $per];
    }
}
