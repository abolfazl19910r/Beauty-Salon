<?php

namespace App\Support;

/**
 * شبای ایران: «IR» + ۲ رقم کنترلی + ۲۲ رقم (ISO 13616).
 * رقم کنترلی با mod 97 استاندارد IBAN بررسی می‌شود: چهار نویسه‌ی اول به انتها می‌رود، I=18 و R=27،
 * و باقی‌مانده‌ی تقسیم عدد حاصل بر ۹۷ باید ۱ باشد. این چک هر اشتباه تک‌رقمی و هر جابه‌جایی دو رقم
 * کنار هم را می‌گیرد؛ یعنی می‌گوید شبا «خوش‌ساخت» است، نه این‌که حسابی با این شبا وجود دارد یا مال چه کسی است.
 */
final class Iban
{
    public const COUNTRY = 'IR';

    public const DIGITS = 24;

    public static function normalize(string $iban): string
    {
        return strtoupper(str_replace(' ', '', $iban));
    }

    public static function isValid(?string $iban): bool
    {
        $iban = self::normalize((string) $iban);

        if (! preg_match('/^IR[0-9]{'.self::DIGITS.'}$/', $iban)) {
            return false;
        }

        return self::mod97(substr($iban, 4).self::countryDigits().substr($iban, 2, 2)) === 1;
    }

    /**
     * ساخت شبای معتبر از ۲۲ رقم حساب (BBAN) — برای factoryها و تست‌ها.
     */
    public static function fromBban(string $bban): string
    {
        if (! preg_match('/^[0-9]{22}$/', $bban)) {
            throw new \InvalidArgumentException('BBAN must be exactly 22 digits.');
        }

        $check = 98 - self::mod97($bban.self::countryDigits().'00');

        return self::COUNTRY.str_pad((string) $check, 2, '0', STR_PAD_LEFT).$bban;
    }

    private static function countryDigits(): string
    {
        return (string) (ord('I') - 55).(string) (ord('R') - 55);
    }

    private static function mod97(string $digits): int
    {
        $remainder = 0;

        foreach (str_split($digits, 7) as $chunk) {
            $remainder = (int) ($remainder.$chunk) % 97;
        }

        return $remainder;
    }
}
