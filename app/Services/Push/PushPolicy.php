<?php

namespace App\Services\Push;

use App\Support\Notifications\NotificationEvents as E;

/**
 * دسته و عنوان پوش هر رویداد (بسته‌ی ۴). رویدادهایی که در تنظیمات اعلان ردیف ندارند (یادآوری نوبت، کد تخفیف، لغو خودکار
 * پرداخت‌نشده، برگشت پول) کلید جدای خودشان را دارند و ستون «اپ» برایشان همیشه روشن است.
 */
final class PushPolicy
{
    public const BOOKING_REMINDER_CUSTOMER = 'booking.reminder.customer';

    public const BOOKING_REMINDER_SPECIALIST = 'booking.reminder.specialist';

    public const BOOKING_CANCELLED_UNPAID_CUSTOMER = 'booking.cancelled_unpaid.customer';

    /** بسته‌ی 5ه: سالن «بدون پیش‌پرداخت» شد و نوبت در انتظار پرداخت حالا منتظر تأیید است */
    public const BOOKING_PREPAYMENT_WAIVED_CUSTOMER = 'booking.prepayment_waived.customer';

    public const DISCOUNT_CODE_CUSTOMER = 'discount.code.customer';

    /** درخواست رضایت عکس دوره از مشتری (۲۰۲۶-۱۰-۲۸)؛ همیشه روشن، مثل یادآوری نوبت */
    public const COURSE_PHOTO_CONSENT_CUSTOMER = 'course.photo_consent.customer';

    public const TEST = 'push.test';

    /** push:live-check — آزمون مسیر پیامک پشتیبان حالت «فعال»؛ دسته را خود دستور می‌دهد */
    public const LIVE_CHECK = 'push.live_check';

    /** @var array<string, array{0: PushUrgency, 1: string}> */
    private const MAP = [
        E::BOOKING_CREATED_SPECIALIST => [PushUrgency::Normal, 'نوبت تازه'],
        E::BOOKING_CONFIRMED_CUSTOMER => [PushUrgency::Normal, 'نوبت شما تأیید شد'],
        E::BOOKING_PAID_PENDING_APPROVAL_CUSTOMER => [PushUrgency::Normal, 'پرداخت نوبت انجام شد'],
        E::BOOKING_COMPLETED_REVIEW_REQUEST => [PushUrgency::Info, 'نظرتان درباره‌ی نوبت'],
        E::BOOKING_CANCELLED_CUSTOMER => [PushUrgency::Urgent, 'لغو نوبت'],
        E::BOOKING_CANCELLED_SPECIALIST => [PushUrgency::Urgent, 'لغو نوبت'],
        E::BOOKING_RESCHEDULED_CUSTOMER => [PushUrgency::Urgent, 'تغییر زمان نوبت'],
        E::BOOKING_RESCHEDULED_SPECIALIST => [PushUrgency::Urgent, 'تغییر زمان نوبت'],
        E::WITHDRAWAL_APPROVED_SPECIALIST => [PushUrgency::Normal, 'برداشت تأیید شد'],
        E::WITHDRAWAL_REJECTED_SPECIALIST => [PushUrgency::Normal, 'برداشت رد شد'],
        E::LEAVE_STATUS_SPECIALIST => [PushUrgency::Normal, 'نتیجه‌ی درخواست مرخصی'],
        E::REVIEW_NEW_SPECIALIST => [PushUrgency::Info, 'نظر تازه'],
        E::REVIEW_RESPONDED_CUSTOMER => [PushUrgency::Info, 'پاسخ به نظر شما'],
        E::LOYALTY_POINTS_EARNED_CUSTOMER => [PushUrgency::Info, 'امتیاز باشگاه'],
        E::LOYALTY_REWARD_REDEEMED_CUSTOMER => [PushUrgency::Info, 'پاداش باشگاه'],
        E::COURSE_REMINDER_CUSTOMER => [PushUrgency::Normal, 'یادآوری دوره'],
        self::BOOKING_REMINDER_CUSTOMER => [PushUrgency::Urgent, 'یادآوری نوبت'],
        self::BOOKING_REMINDER_SPECIALIST => [PushUrgency::Urgent, 'یادآوری نوبت'],
        self::BOOKING_CANCELLED_UNPAID_CUSTOMER => [PushUrgency::Normal, 'لغو نوبت پرداخت‌نشده'],
        self::BOOKING_PREPAYMENT_WAIVED_CUSTOMER => [PushUrgency::Normal, 'نوبت شما بدون پیش‌پرداخت شد'],
        self::DISCOUNT_CODE_CUSTOMER => [PushUrgency::Info, 'کد تخفیف'],
        self::COURSE_PHOTO_CONSENT_CUSTOMER => [PushUrgency::Normal, 'رضایت عکس دوره'],
        self::TEST => [PushUrgency::Info, 'پوش آزمایشی ماهرو'],
        self::LIVE_CHECK => [PushUrgency::Urgent, 'آزمون حالت فعال ماهرو'],
    ];

    public static function urgency(string $eventKey): PushUrgency
    {
        return self::MAP[$eventKey][0] ?? PushUrgency::Normal;
    }

    public static function title(string $eventKey): string
    {
        return self::MAP[$eventKey][1] ?? 'ماهرو';
    }

    /** آیا رویداد در تنظیمات اعلان ستون «اپ» دارد؟ (گیرنده مشتری یا متخصص است، نه مدیر) */
    public static function hasAppColumn(string $eventKey): bool
    {
        return isset(self::MAP[$eventKey]) && in_array($eventKey, E::allKeys(), true);
    }
}
