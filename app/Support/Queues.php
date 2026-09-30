<?php

namespace App\Support;

/**
 * نام صف‌ها (تصمیم ۲۰۲۶-۰۹-۳۰). ترتیب worker: کد تأیید، بقیه‌ی پیامک‌ها، پول، پیش‌فرض، گزارش.
 *
 * - otp: کد ورود، ۲FA و تأیید تلفن. worker اختصاصی (Docker/supervisor) فقط همین صف را می‌خواند تا کد هیچ‌وقت پشت
 *   یادآوری‌های دسته‌ای یا یک PDF سنگین نماند؛ worker اصلی هم اول همین را می‌خواند. روی هاست بدون worker دائمی
 *   (QUEUE_WORK_VIA_SCHEDULER=true) کد بعد از پاسخ و در همان درخواست فرستاده می‌شود (dispatchOtp) — نه تا یک دقیقه بعد.
 * - sms: یادآوری نوبت و کانال پیامک اعلان‌ها.
 * - payments: تسویه‌ی درگاه متخصص و لغو نوبت‌های پرداخت‌نشده.
 * - reports: تولید PDF گزارش (کند؛ timeout خودش).
 * - default: listenerها و بقیه‌ی اعلان‌ها.
 */
final class Queues
{
    public const OTP = 'otp';

    public const SMS = 'sms';

    public const PAYMENTS = 'payments';

    public const DEFAULT = 'default';

    public const REPORTS = 'reports';

    public static function workerOrder(): string
    {
        return implode(',', [self::OTP, self::SMS, self::PAYMENTS, self::DEFAULT, self::REPORTS]);
    }

    /**
     * ارسال job کد تأیید: با worker دائمی روی صف otp؛ روی هاست بدون worker بعد از فرستادن پاسخ، در همان پردازه.
     */
    public static function dispatchOtp(object $job): void
    {
        if (config('queue.work_via_scheduler')) {
            dispatch($job)->afterResponse();

            return;
        }

        dispatch($job);
    }
}
