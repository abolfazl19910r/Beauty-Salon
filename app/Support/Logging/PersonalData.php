<?php

namespace App\Support\Logging;

/**
 * ماسک داده‌ی شخصی در لاگ عمومی (laravel.log و خروجی stderr/`docker logs`) — بررسی لاگ‌ها ۲۰۲۶-۱۱-۰۵.
 *
 * لاگ عمومی فقط ۱۴ روز می‌ماند و برای عیب‌یابی است؛ تکلیف نگهداری ماده‌ی ۶۶۸ (اطلاعات کاربر و IP) با جدول security_logs،
 * security.log و payments.log است که خام می‌مانند. پس اینجا (ماده‌ی ۵۹ ب قانون تجارت الکترونیکی: «فقط به اندازه‌ی ضرورت»)
 * شماره‌ی موبایل و شبا نصفه نوشته می‌شوند: آخر شماره برای پیدا کردن کد در حالت «فقط لاگ» کافی است.
 */
final class PersonalData
{
    private const D = '[0-9۰-۹٠-٩]';

    /** متن آزاد (پیام، context کدشده به JSON، stacktrace) */
    public static function mask(string $text): string
    {
        $d = self::D;

        // موبایل ایران فقط با پیشوند (0، ‎+98، 0098، 98) — عدد ۱۰ رقمی بی پیشوند ممکن است مبلغ یا شناسه‌ی پرداخت باشد
        $text = preg_replace_callback(
            '/(?<![\p{L}\p{N}_+])((?:\+|00)?(?:98|۹۸)|[0۰٠])([9۹٩]'.$d.'{2})'.$d.'{3}('.$d.'{4})(?!\p{N})/u',
            fn (array $m) => $m[1].$m[2].'***'.$m[3],
            $text
        ) ?? $text;

        // شبا: IR + ۲۴ رقم ← دو رقم کنترل و چهار رقم آخر
        return preg_replace('/(?<![\p{L}\p{N}])IR(\d{2})\d{18}(\d{4})(?!\p{N})/iu', 'IR$1******************$2', $text) ?? $text;
    }

    public static function phone(?string $phone): ?string
    {
        return $phone === null ? null : self::mask($phone);
    }

    /** اثر یک رمز یا توکن (لینک نظر، …) برای تطبیق دو خط لاگ، بی خود توکن */
    public static function fingerprint(?string $secret): ?string
    {
        return $secret === null || $secret === '' ? null : substr(hash('sha256', $secret), 0, 12);
    }
}
