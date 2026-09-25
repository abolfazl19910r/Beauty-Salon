<?php

namespace App\Payments;

use Throwable;

/**
 * ⭐ «درخواست اصلاً از سرور ما خارج شد یا نه؟» — برای تشخیص نتیجه‌ی نامعلوم (۲۰۲۶-۰۹-۲۶).
 *
 * وقتی درخواستی که پول جابه‌جا می‌کنه (تایید پرداخت، تسویه) بی‌پاسخ می‌مونه، دو حالت کاملاً متفاوت داریم:
 * - هرگز فرستاده نشد (نام میزبان پیدا نشد، اتصال TCP/TLS برقرار نشد) → درگاه چیزی ندیده؛ «ناموفق» ساده و امنه.
 * - فرستاده شد ولی پاسخ نرسید (timeout، قطع وسط راه، ۵xx) → شاید درگاه انجامش داده؛ نتیجه نامعلومه و نباید
 *   «ناموفق» فرض بشه (برگشت پول = پرداخت دوباره) یا کورکورانه تکرار بشه.
 * کدهای cURL: 5/6 resolve، 7 connect، 35 TLS handshake، 60 گواهی نامعتبر — همه پیش از فرستادن بدنه‌ی درخواست.
 * هر چیز دیگه (از جمله 28 timeout که ممکنه بعد از فرستادن باشه) نامعلوم حساب می‌شه.
 */
final class HttpFailure
{
    private const NEVER_SENT_CURL_ERRORS = [5, 6, 7, 35, 60];

    public static function neverSent(Throwable $e): bool
    {
        for ($current = $e; $current !== null; $current = $current->getPrevious()) {
            if (method_exists($current, 'getHandlerContext')) {
                $errno = $current->getHandlerContext()['errno'] ?? null;
                if ($errno !== null) {
                    return in_array((int) $errno, self::NEVER_SENT_CURL_ERRORS, true);
                }
            }

            if (preg_match('/cURL error (\d+):/', $current->getMessage(), $m)) {
                return in_array((int) $m[1], self::NEVER_SENT_CURL_ERRORS, true);
            }
        }

        return false;
    }
}
