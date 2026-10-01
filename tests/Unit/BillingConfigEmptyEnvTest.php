<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * کلید موجود ولی خالی در .env (مثلاً «SMS_PART_PRICE=») نباید عدد پول یا سهمیه را ۰ کند: پیش از رفع، پلن یک‌ماهه و
 * بسته‌ی پیامک رایگان می‌شدند، سهمیه‌ی پیامک ۰ می‌شد و ثبت‌نام عمومی با سقف ۰ متخصص ناممکن بود.
 */
class BillingConfigEmptyEnvTest extends TestCase
{
    private const KEYS = [
        'SUBSCRIPTION_PRICE_1M', 'SUBSCRIPTION_PRICE_3M', 'SUBSCRIPTION_PRICE_6M', 'SUBSCRIPTION_PRICE_12M',
        'SMS_QUOTA_PER_MONTH', 'SMS_PART_PRICE', 'SMS_PACKS', 'INCLUDED_SPECIALISTS_COUNT',
        'EXTRA_SPECIALIST_PRICE_PER_MONTH', 'MAX_SIGNUP_SPECIALISTS', 'SUBSCRIPTION_TRIAL_DAYS', 'TRIAL_SMS_QUOTA',
    ];

    private array $original = [];

    protected function tearDown(): void
    {
        foreach ($this->original as $key => [$env, $envConst, $server]) {
            $env === false ? putenv($key) : putenv("{$key}={$env}");
            $envConst === null ? $this->forget($_ENV, $key) : $_ENV[$key] = $envConst;
            $server === null ? $this->forget($_SERVER, $key) : $_SERVER[$key] = $server;
        }

        parent::tearDown();
    }

    private function forget(array &$array, string $key): void
    {
        unset($array[$key]);
    }

    private function billingWith(array $values): array
    {
        foreach ($values as $key => $value) {
            $this->original[$key] ??= [getenv($key), $_ENV[$key] ?? null, $_SERVER[$key] ?? null];
            putenv("{$key}={$value}");
            $_ENV[$key] = $_SERVER[$key] = $value;
        }

        return require base_path('config/billing.php');
    }

    public function test_present_but_empty_keys_fall_back_to_the_defaults(): void
    {
        $billing = $this->billingWith(array_fill_keys(self::KEYS, ''));

        $this->assertSame(['1m' => 1500000, '3m' => 4150000, '6m' => 7650000, '12m' => 13850000], $billing['subscription_prices']);
        $this->assertSame(2000, $billing['sms_quota_per_month']);
        $this->assertSame(320, $billing['sms_part_price']);
        $this->assertSame([1000, 2500, 5000], $billing['sms_packs']);
        $this->assertSame(10, $billing['included_specialists']);
        $this->assertSame(250000, $billing['extra_specialist_price_per_month']);
        $this->assertSame(50, $billing['max_signup_specialists']);
        $this->assertSame(14, $billing['trial_days']);
        $this->assertSame(400, $billing['trial_sms_quota']);
    }

    public function test_explicit_values_including_zero_are_kept(): void
    {
        $billing = $this->billingWith(['SUBSCRIPTION_TRIAL_DAYS' => '0', 'SMS_PART_PRICE' => '350', 'SMS_PACKS' => '500, 0,x,2000']);

        $this->assertSame(0, $billing['trial_days'], 'صفر یعنی دوره‌ی آزمایشی خاموش');
        $this->assertSame(350, $billing['sms_part_price']);
        $this->assertSame([500, 2000], $billing['sms_packs']);
    }
}
