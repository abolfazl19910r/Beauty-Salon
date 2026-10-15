<?php

namespace App\Services\Push;

/**
 * دسته‌ی پیام اپ (تصمیم ۲ گزینه‌ی «ج»، ۲۰۲۶-۱۰-۱۰ و تصمیم ۳ بسته‌ی ۴، ۲۰۲۶-۱۰-۱۵).
 *
 * - Urgent: لغو/تغییر نوبت، یادآوری پیش از نوبت — بدون رسید در ۶۰ ثانیه ← پیامک؛ دستگاه ناسالم ← مستقیم پیامک.
 * - Normal: تأیید/رسید پرداخت نوبت، برگشت پول، نوبت تازه برای متخصص، نتیجه‌ی برداشت و مرخصی، یادآوری دوره — ۱۰ دقیقه.
 * - Info: امتیاز، پاداش، کد تخفیف، درخواست نظر، پاسخ به نظر، نظر تازه — فقط پوش (در صندوق اعلان اپ می‌ماند).
 */
enum PushUrgency: string
{
    case Urgent = 'urgent';
    case Normal = 'normal';
    case Info = 'info';

    public function fallbackSeconds(): ?int
    {
        return match ($this) {
            self::Urgent => (int) config('push.fallback_seconds.urgent'),
            self::Normal => (int) config('push.fallback_seconds.normal'),
            self::Info => null,
        };
    }

    public function ttlSeconds(): int
    {
        return (int) config('push.ttl_seconds.'.$this->value, 3600);
    }

    /** کانال اعلان اندروید در اپ (همان نام‌ها در src/lib/push.ts) */
    public function androidChannel(): string
    {
        return match ($this) {
            self::Urgent => 'bookings',
            self::Normal => 'bookings',
            self::Info => 'general',
        };
    }
}
