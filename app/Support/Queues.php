<?php

namespace App\Support;

/**
 * نام صف‌ها (تصمیم ۲۰۲۶-۰۹-۳۰). ترتیب worker: کد تأیید، بقیه‌ی پیامک‌ها، پول، پیش‌فرض، گزارش.
 *
 * - otp: کد ورود، ۲FA، تأیید تلفن و بازیابی رمز. worker اختصاصی (Docker/supervisor) فقط همین صف را می‌خواند تا کد هیچ‌وقت پشت
 *   یادآوری‌های دسته‌ای یا یک PDF سنگین نماند؛ worker اصلی هم اول همین را می‌خواند. روی هاست بدون worker دائمی
 *   (QUEUE_WORK_VIA_SCHEDULER=true) کد بعد از پاسخ و در همان درخواست فرستاده می‌شود (dispatchOtp) — نه تا یک دقیقه بعد.
 * - push: پوش اپ‌ها (بسته‌ی ۴). پشت کد تأیید و پیش از پیامک: مهلت «بدون رسید → پیامک» (۶۰ ثانیه برای پیام فوری) از لحظه‌ی
 *   صف شدن شمرده می‌شود و پوشی که پشت یادآوری‌های دسته‌ای بماند بی‌دلیل پیامک می‌شود.
 * - sms: یادآوری نوبت و کانال پیامک اعلان‌ها (و پیامک پشتیبان پوش).
 * - payments: تسویه‌ی درگاه متخصص و لغو نوبت‌های پرداخت‌نشده.
 * - reports: تولید PDF گزارش (کند؛ timeout خودش).
 * - default: listenerها و بقیه‌ی اعلان‌ها.
 */
final class Queues
{
    public const OTP = 'otp';

    public const PUSH = 'push';

    public const SMS = 'sms';

    public const PAYMENTS = 'payments';

    public const DEFAULT = 'default';

    public const REPORTS = 'reports';

    public static function workerOrder(): string
    {
        return implode(',', [self::OTP, self::PUSH, self::SMS, self::PAYMENTS, self::DEFAULT, self::REPORTS]);
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

    /**
     * ارسال پوش (بسته‌ی ۴): روی هاست بدون worker دائمی، پوشی که در یک درخواست وب ساخته شد بعد از فرستادن پاسخ در همان پردازه
     * می‌رود (مثل کد تأیید) — نه تا یک دقیقه‌ی بعد، که مهلت ۶۰ ثانیه‌ای پیام فوری را بی‌دلیل به پیامک می‌کشاند. بقیه (کنسول، worker)
     * عادی صف push. تلاش دوباره‌ی FCM در حالت «بعد از پاسخ» نیست؛ پیامک پشتیبان (صف sms) همچنان سر موعد اجرا می‌شود.
     */
    public static function dispatchPush(object $job): void
    {
        if (config('queue.work_via_scheduler') && ! app()->runningInConsole()) {
            dispatch($job)->afterResponse();

            return;
        }

        dispatch($job)->afterCommit();
    }
}
