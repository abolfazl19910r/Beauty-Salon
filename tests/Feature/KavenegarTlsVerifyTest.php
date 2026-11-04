<?php

namespace Tests\Feature;

use App\Services\SMSService;
use Kavenegar\KavenegarApi;
use ReflectionClass;
use Tests\TestCase;

/**
 * ⭐ ۲۰۲۶-۱۱-۰۴: SDK ‏kavenegar/php گواهی TLS را به‌صورت پیش‌فرض بررسی نمی‌کند (CURLOPT_SSL_VERIFYPEER=false)؛ کلید API در آدرس و کد ورود
 * می‌رفتند بی اطمینان از اینکه طرف مقابل کاوه‌نگار است. SMSService حالا بررسی را روشن می‌کند (KAVENEGAR_SSL_VERIFY، پیش‌فرض روشن).
 * TestCase سرویس را mock می‌کند؛ این‌جا نمونه‌ی واقعی ساخته می‌شود.
 */
class KavenegarTlsVerifyTest extends TestCase
{
    private function sdk(): KavenegarApi
    {
        $service = new SMSService;

        return (new ReflectionClass($service))->getProperty('api')->getValue($service);
    }

    public function test_the_sdk_default_does_not_verify_so_we_must(): void
    {
        $this->assertFalse((new KavenegarApi('x'))->getSslVerify(), 'اگر SDK پیش‌فرضش را درست کرد، این تست را بردارید');
    }

    public function test_tls_verification_is_on_by_default(): void
    {
        $this->assertTrue(config('services.kavenegar.ssl_verify'));
        $this->assertTrue($this->sdk()->getSslVerify());
    }

    public function test_it_can_be_switched_off_explicitly_for_troubleshooting(): void
    {
        config(['services.kavenegar.ssl_verify' => false]);

        $this->assertFalse($this->sdk()->getSslVerify());
    }
}
