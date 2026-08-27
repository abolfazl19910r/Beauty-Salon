<?php

namespace App\Support\Notifications;

/**
 * رجیستری تمام «رویدادهای» قابل اطلاع‌رسانی پروژه. هر ثابت اینجا دقیقاً همان event_key ای است که
 * توسط متد notificationEventKey() روی کلاس‌های Notification برگردانده می‌شود و در جدول
 * notification_settings به‌عنوان کلید یکتا ذخیره می‌شود. اضافه کردن یک رویداد جدید فقط به یک خط
 * این‌جا و ثبتش در groups() نیاز دارد تا در صفحه‌ی تنظیمات ادمین هم نمایش داده شود.
 */
class NotificationEvents
{
    // رزرو نوبت
    // اطلاع رزرو نوبت جدید به مشتری، قبل از پرداخت، عمداً حذف شد (نه فقط پیش‌فرض‌خاموش) — این پیامک
    // با متن نادرست («ثبت و پرداخت شد» با شماره‌پیگیری خالی، چون هنوز پرداختی رخ نداده) همیشه با
    // پیامک درست بعد از پرداخت واقعی (BOOKING_PAID_PENDING_APPROVAL_CUSTOMER/BOOKING_CONFIRMED_CUSTOMER)
    // تکراری بود؛ به تصمیم صریح کاربر این گزینه اصلاً در پنل تنظیمات نمایش داده نمی‌شود.
    public const BOOKING_CREATED_SPECIALIST = 'booking.created.specialist';

    public const BOOKING_CREATED_ADMIN = 'booking.created.admin';

    public const BOOKING_CONFIRMED_CUSTOMER = 'booking.confirmed.customer';

    public const BOOKING_PAID_PENDING_APPROVAL_CUSTOMER = 'booking.paid_pending_approval.customer';

    public const BOOKING_COMPLETED_REVIEW_REQUEST = 'booking.completed.review_request';

    // پیام «تشکر» جداگانه‌ی زمان تکمیل نوبت عمداً حذف شد — متن پیامک BOOKING_COMPLETED_REVIEW_REQUEST
    // از قبل صراحتاً می‌گوید «خدمت X با موفقیت انجام شد» + لینک نظرسنجی، پس یک پیامک دوم صرفاً تکراری بود.

    public const BOOKING_CANCELLED_CUSTOMER = 'booking.cancelled.customer';

    public const BOOKING_CANCELLED_SPECIALIST = 'booking.cancelled.specialist';

    public const BOOKING_RESCHEDULED_CUSTOMER = 'booking.rescheduled.customer';

    // برداشت وجه متخصص
    public const WITHDRAWAL_REQUESTED_ADMIN = 'withdrawal.requested.admin';

    public const WITHDRAWAL_APPROVED_SPECIALIST = 'withdrawal.approved.specialist';

    public const WITHDRAWAL_REJECTED_SPECIALIST = 'withdrawal.rejected.specialist';

    // مرخصی متخصص
    public const LEAVE_STATUS_SPECIALIST = 'leave.status.specialist';

    // نظرات
    public const REVIEW_NEW_SPECIALIST = 'review.new.specialist';

    public const REVIEW_NEGATIVE_ADMIN = 'review.negative.admin';

    public const REVIEW_RESPONDED_CUSTOMER = 'review.responded.customer';

    // مالی/ادمین
    public const PAYMENT_RECEIVED_ADMIN = 'payment.received.admin';

    public const USER_REGISTERED_ADMIN = 'user.registered.admin';

    public const REPORT_EXPORT_READY_ADMIN = 'report.export_ready.admin';

    // وفاداری
    public const LOYALTY_POINTS_EARNED_CUSTOMER = 'loyalty.points_earned.customer';

    public const LOYALTY_REWARD_REDEEMED_CUSTOMER = 'loyalty.reward_redeemed.customer';

    /**
     * فهرست گروه‌بندی‌شده و برچسب‌دار برای صفحه‌ی تنظیمات ادمین. کلید 'sms'/'telegram' مشخص
     * می‌کند آیا اصلاً برای این رویداد پیامک/محتوای رباتی معنادار وجود دارد (بعضی رویدادها فقط
     * نوتیفیکیشن داخل‌برنامه‌ای دارند)، تا ادمین سوییچ بی‌اثر نبیند.
     */
    public static function groups(): array
    {
        return [
            'رزرو نوبت' => [
                self::BOOKING_CREATED_SPECIALIST => ['label' => 'ثبت نوبت جدید — اطلاع به متخصص', 'sms' => true],
                self::BOOKING_CREATED_ADMIN => ['label' => 'ثبت نوبت جدید — اطلاع به ادمین', 'sms' => false],
                self::BOOKING_CONFIRMED_CUSTOMER => ['label' => 'پرداخت/تایید نوبت (خودکار یا توسط متخصص) — اطلاع به مشتری', 'sms' => true],
                self::BOOKING_PAID_PENDING_APPROVAL_CUSTOMER => ['label' => 'پرداخت موفق، در انتظار تایید متخصص — اطلاع به مشتری', 'sms' => true],
                self::BOOKING_COMPLETED_REVIEW_REQUEST => ['label' => 'انجام‌شدن نوبت — اطلاع به مشتری (شامل لینک نظرسنجی)', 'sms' => true],
                self::BOOKING_CANCELLED_CUSTOMER => ['label' => 'لغو نوبت — اطلاع به مشتری', 'sms' => true],
                self::BOOKING_CANCELLED_SPECIALIST => ['label' => 'لغو نوبت — اطلاع به متخصص', 'sms' => true],
                self::BOOKING_RESCHEDULED_CUSTOMER => ['label' => 'تغییر زمان نوبت — اطلاع به مشتری', 'sms' => true],
            ],
            'برداشت وجه متخصص' => [
                self::WITHDRAWAL_REQUESTED_ADMIN => ['label' => 'ثبت درخواست برداشت — اطلاع به ادمین', 'sms' => false],
                self::WITHDRAWAL_APPROVED_SPECIALIST => ['label' => 'تایید برداشت — اطلاع به متخصص', 'sms' => true],
                self::WITHDRAWAL_REJECTED_SPECIALIST => ['label' => 'رد برداشت — اطلاع به متخصص', 'sms' => true],
            ],
            'مرخصی' => [
                self::LEAVE_STATUS_SPECIALIST => ['label' => 'تایید/رد مرخصی — اطلاع به متخصص', 'sms' => true],
            ],
            'نظرات' => [
                self::REVIEW_NEW_SPECIALIST => ['label' => 'ثبت نظر جدید — اطلاع به متخصص', 'sms' => true],
                self::REVIEW_NEGATIVE_ADMIN => ['label' => 'نظر منفی — اطلاع به ادمین', 'sms' => false],
                self::REVIEW_RESPONDED_CUSTOMER => ['label' => 'پاسخ متخصص به نظر — اطلاع به مشتری', 'sms' => false],
            ],
            'مالی و سیستمی (ادمین)' => [
                self::PAYMENT_RECEIVED_ADMIN => ['label' => 'دریافت پرداخت جدید — اطلاع به ادمین', 'sms' => true],
                self::USER_REGISTERED_ADMIN => ['label' => 'ثبت‌نام کاربر جدید — اطلاع به ادمین', 'sms' => false],
                self::REPORT_EXPORT_READY_ADMIN => ['label' => 'آماده‌شدن خروجی گزارش — اطلاع به ادمین', 'sms' => false],
            ],
            'وفاداری' => [
                self::LOYALTY_POINTS_EARNED_CUSTOMER => ['label' => 'کسب امتیاز وفاداری — اطلاع به مشتری', 'sms' => true],
                self::LOYALTY_REWARD_REDEEMED_CUSTOMER => ['label' => 'استفاده از امتیاز برای پاداش — اطلاع به مشتری', 'sms' => true],
            ],
        ];
    }

    /**
     * فهرست تخت تمام کلیدهای معتبر (برای اعتبارسنجی ورودی فرم تنظیمات).
     */
    public static function allKeys(): array
    {
        $keys = [];
        foreach (self::groups() as $group) {
            $keys = array_merge($keys, array_keys($group));
        }

        return $keys;
    }
}