<?php

namespace App\Support\Features;

/**
 * امکاناتی که سوپرادمین برای هر پلن روشن/خاموش می‌کند (بسته‌ی الف، تصمیم ۲۰۲۶-۱۰-۱۴: ۱۲ کلید). هر کلید یک نقطه‌ی کنترل در کد
 * دارد (پایین هر case). چیزهایی که هرگز پشت امکان نمی‌روند: کد تأیید/ورود/رمز، هشدارهای پلتفرم (سهمیه، «نیاز به بررسی»)،
 * پیامک برگشت پول، خوشامد سالن.
 *
 * پیامک‌های سالن سه دسته‌ی جدا دارند تا هر پلن دقیق تنظیم شود: یادآوری پیش از نوبت (مشتری و متخصص)، پیامک‌های مشتری، پیامک‌های کادر.
 */
enum Feature: string
{
    /** یادآوری ~۱ ساعت پیش از نوبت به مشتری و متخصص — SendBookingReminderJob */
    case SmsReminders = 'sms_reminders';

    /** ثبت، تأیید، لغو، جابه‌جایی، نظرسنجی، امتیاز و کد تخفیف به مشتری */
    case SmsCustomer = 'sms_customer';

    /** نوبت تازه/لغو/جابه‌جایی به متخصص، پرداخت و نظر به مدیر، برداشت و مرخصی به متخصص */
    case SmsStaff = 'sms_staff';

    /** ویرایش متن پیامک‌ها؛ خاموش = همیشه متن پیش‌فرض (متن ذخیره‌شده پاک نمی‌شود) */
    case SmsTemplates = 'sms_templates';

    /** پیگیری خودکار مشتریان غایب — customers:winback */
    case Winback = 'winback';

    /** ارسال دستی به مشتری‌های قدیمی (بسته‌ی ج، هنوز پیاده نشده) */
    case WinbackManual = 'winback_manual';

    /** باشگاه مشتریان: امتیاز تازه و مدیریت پاداش‌ها (امتیاز گرفته‌شده‌ی مشتری قابل استفاده می‌ماند) */
    case Loyalty = 'loyalty';

    /** دوره‌های درمانی چندجلسه‌ای (بسته‌ی ب): پنل مدیر، فروش در وب/اپ، یادآوری جلسه‌ی بعد. دوره‌های فعال موجود با خاموش شدن کار می‌کنند */
    case TreatmentCourses = 'treatment_courses';

    /** پرداخت آنلاین مشتری از درگاه سالن (پیش‌پرداخت نوبت، شارژ کیف پول)؛ خاموش = مثل سالن بدون درگاه */
    case OnlinePayment = 'online_payment';

    /** تسویه‌ی خودکار برداشت متخصص از حساب درگاه سالن؛ خاموش = فقط تسویه‌ی دستی */
    case SpecialistPayouts = 'specialist_payouts';

    /** لوگوی اختصاصی سالن؛ خاموش = لوگوی پیش‌فرض (فایل آپلودشده پاک نمی‌شود) */
    case CustomLogo = 'custom_logo';

    /** ساخت خروجی Excel/PDF گزارش‌ها (خروجی‌های قبلی قابل دانلود می‌مانند) */
    case ReportsExport = 'reports_export';

    public function label(): string
    {
        return match ($this) {
            self::SmsReminders => 'یادآوری پیامکی پیش از نوبت',
            self::SmsCustomer => 'پیامک‌های نوبت به مشتری',
            self::SmsStaff => 'پیامک‌های کادر سالن',
            self::SmsTemplates => 'ویرایش متن پیامک‌ها',
            self::Winback => 'پیگیری خودکار مشتریان غایب',
            self::WinbackManual => 'ارسال دستی به مشتری‌های قدیمی',
            self::Loyalty => 'باشگاه مشتریان (امتیاز و پاداش)',
            self::TreatmentCourses => 'دوره‌های درمانی چندجلسه‌ای',
            self::OnlinePayment => 'پرداخت آنلاین مشتری',
            self::SpecialistPayouts => 'تسویه‌ی خودکار متخصص',
            self::CustomLogo => 'لوگوی اختصاصی سالن',
            self::ReportsExport => 'خروجی Excel/PDF گزارش‌ها',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::SmsReminders => 'حدود یک ساعت پیش از هر نوبت به مشتری و متخصص.',
            self::SmsCustomer => 'ثبت، تأیید، لغو، جابه‌جایی و نظرسنجی نوبت، امتیاز باشگاه و کد تخفیف.',
            self::SmsStaff => 'نوبت تازه، لغو و جابه‌جایی به متخصص؛ پرداخت و نظر به مدیر؛ برداشت و مرخصی.',
            self::SmsTemplates => 'خاموش: پیامک‌ها همیشه با متن پیش‌فرض می‌روند.',
            self::Winback => 'پیامک روزانه به مشتری‌هایی که مدتی نیامده‌اند، با کد تخفیف اختیاری.',
            self::WinbackManual => 'کمپین دستی برای مشتری‌های خیلی قدیمی.',
            self::Loyalty => 'امتیاز تازه برای نوبت و نظر، و مدیریت پاداش‌ها.',
            self::TreatmentCourses => 'فروش بسته‌ی چندجلسه‌ای و یادآوری جلسه‌ی بعد.',
            self::OnlinePayment => 'پیش‌پرداخت آنلاین نوبت و شارژ کیف پول از درگاه خود سالن.',
            self::SpecialistPayouts => 'واریز خودکار برداشت متخصص از حساب درگاه سالن.',
            self::CustomLogo => 'لوگوی سالن در صفحه‌ی عمومی، پنل و اپ.',
            self::ReportsExport => 'ساخت فایل Excel و PDF از گزارش‌ها.',
        };
    }

    public function group(): string
    {
        return match ($this) {
            self::SmsReminders, self::SmsCustomer, self::SmsStaff, self::SmsTemplates => 'پیامک',
            self::Winback, self::WinbackManual, self::Loyalty => 'بازاریابی',
            self::TreatmentCourses => 'درمان',
            self::OnlinePayment, self::SpecialistPayouts => 'مالی',
            self::CustomLogo, self::ReportsExport => 'سایر',
        };
    }

    /** تیکش ذخیره می‌شود ولی خود امکان هنوز در کد نیست (بسته‌های ب و ج) */
    public function isComingSoon(): bool
    {
        return $this === self::WinbackManual;
    }

    /** @return array<string, list<self>> گروه ← امکانات، به ترتیب تعریف */
    public static function grouped(): array
    {
        $groups = [];
        foreach (self::cases() as $feature) {
            $groups[$feature->group()][] = $feature;
        }

        return $groups;
    }

    /** @return list<string> */
    public static function keys(): array
    {
        return array_map(fn (self $f) => $f->value, self::cases());
    }
}
