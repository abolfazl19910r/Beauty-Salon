<?php

namespace App\Contracts\Verification;

use App\Models\SalonVerification;
use App\Services\Verification\VerificationOutcome;

/**
 * سرویس احراز سالن (بسته‌ی مجوز 5د، مرحله‌ی ب — اسکلت). دستورالعمل سکوهای اینترنتی: احراز هویت تأمین‌کننده (بند ۲) و کنترل پروانه‌ی
 * کسب (بند ۴) «از طریق سامانه‌ی احراز مشتریان تجارت الکترونیکی (امتا)»، طبق برنامه‌ی زمان‌بندی مرکز توسعه تجارت الکترونیکی؛ دسترسی
 * امتا به درخواست پلتفرم داده می‌شود (بند ۷). تا آن موقع فقط ManualSalonIdentityVerifier: ابرمدیر خودش بررسی می‌کند.
 *
 * پیاده‌سازی تازه (مثلاً EmtaSalonIdentityVerifier، یا ShahkarMobileCheck برای تطبیق کد ملی و موبایل مالک — اختیاری) فقط همین را
 * پیاده می‌کند و در config/verification.php ‏(SALON_VERIFIER) انتخاب می‌شود؛ صف ابرمدیر، وضعیت سالن و بسته شدن سایت دست نمی‌خورند.
 * پاسخ «در انتظار» یعنی بررسی انسانی؛ «تأیید» یا «رد» قطعی، سالن را بی دخالت ابرمدیر تأیید یا رد می‌کند.
 */
interface SalonIdentityVerifier
{
    /** نامی که در salon_verifications.provider ثبت می‌شود */
    public function name(): string;

    public function check(SalonVerification $verification): VerificationOutcome;
}
