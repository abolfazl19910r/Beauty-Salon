<?php

namespace App\Support\Notifications;

/**
 * Registry of all project notifiable "events". Each constant here is exactly the same event_key as
 * returned by the notificationEventKey() method on the Notification classes and stored as a unique key in the
 * notification_settings table. Adding a new event requires just one line
 * ​​here and registering it in groups() so that it is also displayed in the admin settings page.
 */
class NotificationEvents
{
    // Book an appointment
    public const BOOKING_CREATED_CUSTOMER = 'booking.created.customer';

    public const BOOKING_CREATED_SPECIALIST = 'booking.created.specialist';

    public const BOOKING_CREATED_ADMIN = 'booking.created.admin';

    public const BOOKING_CONFIRMED_CUSTOMER = 'booking.confirmed.customer';

    public const BOOKING_PAID_PENDING_APPROVAL_CUSTOMER = 'booking.paid_pending_approval.customer';

    public const BOOKING_COMPLETED_REVIEW_REQUEST = 'booking.completed.review_request';

    public const BOOKING_COMPLETED_CUSTOMER = 'booking.completed.customer';

    public const BOOKING_CANCELLED_CUSTOMER = 'booking.cancelled.customer';

    public const BOOKING_CANCELLED_SPECIALIST = 'booking.cancelled.specialist';

    public const BOOKING_RESCHEDULED_CUSTOMER = 'booking.rescheduled.customer';

    // Expert withdrawal
    public const WITHDRAWAL_REQUESTED_ADMIN = 'withdrawal.requested.admin';

    public const WITHDRAWAL_APPROVED_SPECIALIST = 'withdrawal.approved.specialist';

    public const WITHDRAWAL_REJECTED_SPECIALIST = 'withdrawal.rejected.specialist';

    // Specialist leave
    public const LEAVE_STATUS_SPECIALIST = 'leave.status.specialist';

    // Comments
    public const REVIEW_NEW_SPECIALIST = 'review.new.specialist';

    public const REVIEW_NEGATIVE_ADMIN = 'review.negative.admin';

    public const REVIEW_RESPONDED_CUSTOMER = 'review.responded.customer';

    // Finance/Admin
    public const PAYMENT_RECEIVED_ADMIN = 'payment.received.admin';

    public const USER_REGISTERED_ADMIN = 'user.registered.admin';

    public const REPORT_EXPORT_READY_ADMIN = 'report.export_ready.admin';

    // Loyalty
    public const LOYALTY_POINTS_EARNED_CUSTOMER = 'loyalty.points_earned.customer';

    public const LOYALTY_REWARD_REDEEMED_CUSTOMER = 'loyalty.reward_redeemed.customer';

    /**
     * A grouped and labeled list for the admin settings page. The 'sms'/'telegram' key specifies
     * whether there is any meaningful SMS/bot content for this event (some events only
     * have in-app notifications), so that the admin doesn't see the switch as ineffective.
     */
    public static function groups(): array
    {
        return [
            'رزرو نوبت' => [
                self::BOOKING_CREATED_CUSTOMER => ['label' => 'ثبت نوبت جدید — اطلاع به مشتری', 'sms' => true],
                self::BOOKING_CREATED_SPECIALIST => ['label' => 'ثبت نوبت جدید — اطلاع به متخصص', 'sms' => true],
                self::BOOKING_CREATED_ADMIN => ['label' => 'ثبت نوبت جدید — اطلاع به ادمین', 'sms' => false],
                self::BOOKING_CONFIRMED_CUSTOMER => ['label' => 'پرداخت/تایید نوبت (خودکار یا توسط متخصص) — اطلاع به مشتری', 'sms' => true],
                self::BOOKING_PAID_PENDING_APPROVAL_CUSTOMER => ['label' => 'پرداخت موفق، در انتظار تایید متخصص — اطلاع به مشتری', 'sms' => true],
                self::BOOKING_COMPLETED_REVIEW_REQUEST => ['label' => 'انجام‌شدن نوبت — لینک نظرسنجی به مشتری', 'sms' => true],
                self::BOOKING_COMPLETED_CUSTOMER => ['label' => 'انجام‌شدن نوبت — پیام تشکر به مشتری (علاوه بر لینک نظرسنجی)', 'sms' => true],
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
     * Flat list of all valid keys (for validating input to the settings form).
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
