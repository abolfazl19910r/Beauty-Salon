<?php

namespace App\Payments;

/**
 * ⭐ فهرست درگاه‌های قابل افزودن برای سالن‌ها (مرحله‌ی ۱ چند درگاه، ۲۰۲۶-۰۹-۲۵) — تصمیم ابوالفضل:
 * زرین‌پال، زیبال، آسان پرداخت، وندار؛ و از مرحله‌ی ۲ درگاه‌های مستقیم بانک سامان (سپ) و بانک ملت (به‌پرداخت). فرم مدیریت درگاه‌ها و اعتبارسنجی هر دو از همین فهرست ساخته می‌شن.
 * فیلدهای secret هیچ‌وقت دوباره در فرم نمایش داده نمی‌شن (خالی = بدون تغییر).
 */
final class GatewayCatalog
{
    public const DRIVERS = [
        'zarinpal' => [
            'label' => 'زرین‌پال',
            'website' => 'https://www.zarinpal.com/',
            'fields' => [
                'merchant_id' => ['label' => 'کد پذیرنده (Merchant ID)', 'secret' => false, 'rules' => ['regex:/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/'], 'placeholder' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx'],
                // ⭐ مرحله‌ی ۳ (۲۰۲۶-۰۹-۲۵): از «اطلاعات سالن» به اینجا منتقل شد
                'payout_api_key' => ['label' => 'توکن Payout (اختیاری — برای تسویه‌ی خودکار کیف پول متخصص‌ها)', 'secret' => true, 'optional' => true, 'rules' => ['string', 'max:2000'], 'placeholder' => 'از بخش «توسعه‌دهندگان» پنل زرین‌پال'],
            ],
            'payout' => true,
            'note' => 'کد ۳۶ کاراکتری درگاه از پنل زرین‌پال. توکن Payout فقط برای واریز خودکار برداشت متخصص‌ها به شبای‌شان لازم است.',
        ],
        'zibal' => [
            'label' => 'زیبال',
            'website' => 'https://zibal.ir/',
            'fields' => [
                'merchant' => ['label' => 'کد مرچنت (merchant)', 'secret' => false, 'rules' => ['string', 'max:64'], 'placeholder' => 'مثلاً 5f3e...'],
                // ⭐ مرحله‌ی ۳ (۲۰۲۶-۰۹-۲۵): تسویه‌ی خودکار متخصص‌ها از کیف پول زیبال سالن
                'payout_access_token' => ['label' => 'توکن دسترسی API (اختیاری — برای تسویه‌ی خودکار متخصص‌ها)', 'secret' => true, 'optional' => true, 'rules' => ['string', 'max:2000'], 'placeholder' => 'پنل زیبال ← توسعه‌دهندگان ← API Tokenها'],
                'payout_wallet_id' => ['label' => 'شناسه‌ی کیف پول تسویه (اختیاری)', 'secret' => false, 'optional' => true, 'rules' => ['digits_between:1,20'], 'placeholder' => 'مثلاً 1010101'],
            ],
            'payout' => true,
            'note' => 'کد مرچنت درگاه از پنل زیبال. برای آزمایش بدون پول واقعی می‌توانید کد «zibal» را وارد کنید.',
        ],
        'asanpardakht' => [
            'label' => 'آسان پرداخت',
            'website' => 'https://asanpardakht.ir/',
            'fields' => [
                'merchant_config_id' => ['label' => 'شناسه‌ی پیکربندی پذیرنده (Merchant Configuration ID)', 'secret' => false, 'rules' => ['digits_between:1,12'], 'placeholder' => '1234'],
                'username' => ['label' => 'نام کاربری وب‌سرویس', 'secret' => false, 'rules' => ['string', 'max:100'], 'placeholder' => ''],
                'password' => ['label' => 'رمز عبور وب‌سرویس', 'secret' => true, 'rules' => ['string', 'max:200'], 'placeholder' => ''],
            ],
            'note' => 'این سه مقدار را آسان پرداخت پس از قرارداد می‌دهد. IP سرور سایت باید در پنل آسان پرداخت ثبت شده باشد.',
        ],
        'vandar' => [
            'label' => 'وندار',
            'website' => 'https://vandar.io/',
            'fields' => [
                'api_key' => ['label' => 'کلید درگاه (API Key)', 'secret' => true, 'rules' => ['string', 'max:200'], 'placeholder' => ''],
            ],
            'note' => 'کلید درگاه از داشبورد وندار. دامنه‌ی سالن باید در پنل وندار برای همین درگاه ثبت شده باشد.',
        ],
        // ⭐ مرحله‌ی ۲ (درگاه مستقیم بانکی): سپ برای توکن/تایید رمز نمی‌خواد — فقط شماره ترمینال، و امنیت با
        // IP سرورِ ثبت‌شده نزد سپ. قرارداد پذیرندگی مستقیم با بانک سامان/شاپرک لازمه.
        'saman' => [
            'label' => 'بانک سامان (سپ)',
            'website' => 'https://www.sep.ir/',
            'fields' => [
                'terminal_id' => ['label' => 'شماره ترمینال (Terminal ID)', 'secret' => false, 'rules' => ['digits_between:1,15'], 'placeholder' => 'مثلاً 13012345'],
                // ⭐ «بلوپی» (neo-pg): فقط اگه سپ برای ترمینال فعالش کرده؛ وگرنه خودکار همون صفحه‌ی کلاسیک
                'redirect_mode' => ['label' => 'صفحه‌ی پرداخت', 'secret' => false, 'optional' => true, 'rules' => ['in:classic,blupay'], 'placeholder' => '',
                    'options' => ['classic' => 'درگاه اینترنتی سپ (پیش‌فرض)', 'blupay' => 'درگاه + بلوپی (فقط اگر سپ بلوپی را برای ترمینال فعال کرده)']],
            ],
            'note' => 'شماره ترمینال را پرداخت الکترونیک سامان پس از قرارداد می‌دهد. IP سرور سایت باید نزد سپ ثبت شده باشد (برای دریافت توکن و تایید پرداخت).',
        ],
        // ⭐ مرحله‌ی ۲: به‌پرداخت ملت (SOAP) — شماره ترمینال + نام کاربری/رمز ترمینال؛ IP سرور و دامنه‌ی سالن ثبت‌شده
        'mellat' => [
            'label' => 'بانک ملت (به‌پرداخت)',
            'website' => 'https://www.behpardakht.com/',
            'fields' => [
                'terminal_id' => ['label' => 'شماره ترمینال (Terminal ID)', 'secret' => false, 'rules' => ['digits_between:1,15'], 'placeholder' => 'مثلاً 5012345'],
                'username' => ['label' => 'نام کاربری ترمینال', 'secret' => false, 'rules' => ['string', 'max:100'], 'placeholder' => ''],
                'password' => ['label' => 'رمز ترمینال', 'secret' => true, 'rules' => ['string', 'max:200'], 'placeholder' => ''],
            ],
            'note' => 'این سه مقدار را به‌پرداخت ملت پس از قرارداد می‌دهد. IP سرور سایت و دامنه/ساب‌دامین سالن باید نزد به‌پرداخت ثبت شده باشند.',
        ],
    ];

    /** درگاه‌هایی که driver تسویه (App\Payments\Contracts\PayoutDriver) دارن. */
    public static function supportsPayout(string $driver): bool
    {
        return (bool) (self::DRIVERS[$driver]['payout'] ?? false);
    }

    public static function keys(): array
    {
        return array_keys(self::DRIVERS);
    }

    public static function label(string $driver): string
    {
        return self::DRIVERS[$driver]['label'] ?? $driver;
    }

    /** @return array<string, array{label: string, secret: bool, rules: array, placeholder: string, optional?: bool, options?: array<string, string>}> */
    public static function fields(string $driver): array
    {
        return self::DRIVERS[$driver]['fields'] ?? [];
    }
}
