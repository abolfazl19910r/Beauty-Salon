<?php

namespace App\Payments;

use Closure;

/**
 * ⭐ حد مبلغ هر پرداخت زرین‌پال (بسته‌ی 5ز، ۲۰۲۶-۱۰-۳۰): حداقل ۱۰٬۰۰۰ ریال (SDK رسمی PHP: «Minimum amount 10000 IRR») و حداکثر
 * ۱۰۰ میلیون تومان (کد ‎-41 جدول رسمی). پیش از شروع پرداخت (ZarinpalDriver::start) و برای «حداقل پیش‌پرداخت» در «تنظیمات مالی» و
 * «نحوه‌ی رزرو» کنترل می‌شود تا مشتری به‌جای خطای ‎-9/‎-41 درگاه پیام روشن ببیند.
 */
final class GatewayAmountLimits
{
    public const MIN_RIAL = 10_000;

    public const MAX_RIAL = 1_000_000_000;

    public const MIN_TOMAN = 1_000;

    public const MAX_TOMAN = 100_000_000;

    public const PREPAYMENT_MIN_MESSAGE = 'حداقل پیش‌پرداخت باید دست‌کم ۱٬۰۰۰ تومان باشد؛ زرین‌پال مبلغ کمتر از ۱۰٬۰۰۰ ریال را نمی‌پذیرد.';

    public const PREPAYMENT_MAX_MESSAGE = 'حداقل پیش‌پرداخت نمی‌تواند بیشتر از ۱۰۰ میلیون تومان (سقف هر پرداخت زرین‌پال) باشد.';

    /** پیام خطای مبلغ بیرون از حد، یا null. */
    public static function violation(int $amountRial): ?string
    {
        $toman = to_persian_num(number_format(intdiv($amountRial, 10), 0, '.', '٬'));

        return match (true) {
            $amountRial < self::MIN_RIAL => "مبلغ پرداخت آنلاین ({$toman} تومان) کمتر از حداقل مبلغ درگاه (۱٬۰۰۰ تومان) است؛ با سالن تماس بگیرید.",
            $amountRial > self::MAX_RIAL => "مبلغ پرداخت آنلاین ({$toman} تومان) بیشتر از سقف هر پرداخت درگاه (۱۰۰ میلیون تومان) است.",
            default => null,
        };
    }

    /**
     * قاعده‌ی «حداقل پیش‌پرداخت» (تومان): صفر فقط وقتی درصد پیش‌پرداخت هم صفر است (پیش‌پرداخت خاموش)؛ وگرنه بین ۱٬۰۰۰ تومان و
     * ۱۰۰ میلیون تومان — تا max(حداقل، درصد) هیچ‌وقت زیر حداقل درگاه نیفتد.
     */
    public static function prepaymentMinimumRule(Closure $percentage): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($percentage) {
            $minimum = (float) $value;
            if ($minimum > self::MAX_TOMAN) {
                $fail(self::PREPAYMENT_MAX_MESSAGE);
            } elseif (($minimum > 0 || (float) $percentage() > 0) && $minimum < self::MIN_TOMAN) {
                $fail(self::PREPAYMENT_MIN_MESSAGE);
            }
        };
    }
}
