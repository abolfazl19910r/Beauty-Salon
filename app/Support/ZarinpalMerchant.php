<?php

namespace App\Support;

/**
 * ⭐ کد پذیرنده‌ی (Merchant ID) زرین‌پالِ هر سالن (۲۰۲۶-۰۹-۲۴).
 *
 * پرداخت‌های مشتری‌های یک سالن (پیش‌پرداخت نوبت، پرداخت باقی‌مانده، شارژ کیف پول) مستقیم به درگاه
 * زرین‌پالِ خود همون سالن می‌رن. تصمیم ابوالفضل: سالنی که کد پذیرنده نداره **هیچ پرداخت آنلاینی
 * نداره** — قبلاً PaymentService بی‌صدا به مرچنت سراسری پلتفرم (ZARINPAL_MERCHANT_ID) برمی‌گشت،
 * یعنی پول مشتری‌های سالن به حساب پلتفرم واریز می‌شد. مرچنت سراسری فقط برای خرید اشتراک خودِ
 * سالن‌ها (SubscriptionPaymentService) استفاده می‌شه.
 */
class ZarinpalMerchant
{
    /** فرمت کد پذیرنده‌ی زرین‌پال: UUID ۳۶ کاراکتری. */
    public const PATTERN = '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/';

    public const RULES = ['nullable', 'string', 'regex:'.self::PATTERN];

    public const MESSAGES = [
        'zarinpal_merchant_id.regex' => 'کد پذیرنده‌ی زرین‌پال باید ۳۶ کاراکتر به شکل xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx باشد.',
    ];

    /** پیام مشترک برای مشتری وقتی سالن هنوز درگاه نداره. */
    public const CUSTOMER_MESSAGE = 'پرداخت آنلاین این سالن هنوز فعال نشده است. برای رزرو، لطفاً با سالن تماس بگیرید.';

    public static function normalize(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : strtolower($value);
    }

    /**
     * مثل normalize، ولی مقدار نامعتبر (مثلاً جای‌نگهدار «your-merchant-id» در ‎.env.example) را null
     * می‌کند. برای جاهایی که مقدار از ‎.env کپی می‌شود و از فرم اعتبارسنجی‌شده نمی‌آید (seeder):
     * مرچنت نامعتبر یعنی هر پرداخت با خطای درگاه رد می‌شود؛ null یعنی پیام روشن «پرداخت آنلاین فعال نیست».
     */
    public static function validOrNull(?string $value): ?string
    {
        $value = self::normalize($value);

        return $value !== null && preg_match(self::PATTERN, $value) ? $value : null;
    }
}
