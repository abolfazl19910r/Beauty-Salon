<?php

namespace App\Support;

/**
 * تعداد «قطعه»ی یک پیامک، همان‌طور که کاوه‌نگار حساب و فاکتور می‌کند (تصمیم ۲۰۲۶-۰۹-۳۰: سهمیه بر اساس قطعه).
 *
 * - متن فارسی (یا هر نویسه‌ی خارج از الفبای GSM) یونیکد (UCS-2) فرستاده می‌شود: تا ۷۰ نویسه یک قطعه، بیشتر از آن هر ۶۷
 *   نویسه یک قطعه. نویسه با واحد UTF-16 شمرده می‌شود، پس هر ایموجی دو نویسه است؛ هر خط جدید یک نویسه.
 * - متن کاملاً لاتین (GSM-7): تا ۱۶۰ نویسه یک قطعه، بیشتر از آن هر ۱۵۳؛ نویسه‌های ^{}\[~]|€ دو واحد.
 */
final class SmsParts
{
    private const GSM_BASIC = "@£\$¥èéùìòÇ\nØø\rÅåΔ_ΦΓΛΩΠΨΣΘΞÆæßÉ !\"#¤%&'()*+,-./0123456789:;<=>?¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà";

    private const GSM_EXTENDED = '^{}\\[~]|€';

    public static function count(string $text): int
    {
        if ($text === '') {
            return 1;
        }

        $gsmLength = self::gsmLength($text);
        if ($gsmLength !== null) {
            return $gsmLength <= 160 ? 1 : (int) ceil($gsmLength / 153);
        }

        $units = self::ucs2Length($text);

        return $units <= 70 ? 1 : (int) ceil($units / 67);
    }

    /** طول متن به واحد UTF-16 (همان شمارشی که برای پیامک یونیکد به کار می‌رود) */
    public static function ucs2Length(string $text): int
    {
        return intdiv(strlen(mb_convert_encoding($text, 'UTF-16LE', 'UTF-8')), 2);
    }

    /** طول GSM-7، یا null اگر متن نویسه‌ی غیر GSM دارد */
    private static function gsmLength(string $text): ?int
    {
        $length = 0;
        foreach (mb_str_split($text) as $char) {
            if (str_contains(self::GSM_EXTENDED, $char)) {
                $length += 2;
            } elseif (str_contains(self::GSM_BASIC, $char)) {
                $length++;
            } else {
                return null;
            }
        }

        return $length;
    }
}
