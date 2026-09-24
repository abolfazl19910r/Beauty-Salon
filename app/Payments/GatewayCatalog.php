<?php

namespace App\Payments;

/**
 * ⭐ فهرست درگاه‌های قابل افزودن برای سالن‌ها (مرحله‌ی ۱ چند درگاه، ۲۰۲۶-۰۹-۲۵) — تصمیم ابوالفضل:
 * زرین‌پال، زیبال، آسان پرداخت، وندار. فرم مدیریت درگاه‌ها و اعتبارسنجی هر دو از همین فهرست ساخته می‌شن.
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
            ],
            'note' => 'کد ۳۶ کاراکتری درگاه از پنل زرین‌پال.',
        ],
        'zibal' => [
            'label' => 'زیبال',
            'website' => 'https://zibal.ir/',
            'fields' => [
                'merchant' => ['label' => 'کد مرچنت (merchant)', 'secret' => false, 'rules' => ['string', 'max:64'], 'placeholder' => 'مثلاً 5f3e...'],
            ],
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
    ];

    public static function keys(): array
    {
        return array_keys(self::DRIVERS);
    }

    public static function label(string $driver): string
    {
        return self::DRIVERS[$driver]['label'] ?? $driver;
    }

    /** @return array<string, array{label: string, secret: bool, rules: array, placeholder: string}> */
    public static function fields(string $driver): array
    {
        return self::DRIVERS[$driver]['fields'] ?? [];
    }
}
