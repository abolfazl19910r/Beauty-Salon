<?php

namespace App\Contracts\Sms;

/**
 * job فرستادن کد پیامکی (صف otp). Queues::dispatchOtp پیش از صف کردن، شماره‌ی گیرنده را به OtpSendGuard می‌دهد
 * (بررسی امنیتی ۲۰۲۶-۱۱-۰۴، H4 — «پمپاژ پیامک»).
 */
interface OtpJob
{
    /** شماره‌ی موبایل گیرنده‌ی کد؛ null اگر کاربر دیگر نیست (job خودش هم چیزی نمی‌فرستد). */
    public function otpRecipient(): ?string;
}
