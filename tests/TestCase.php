<?php

namespace Tests;

use App\Services\SMSService;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Mockery;

abstract class TestCase extends BaseTestCase
{
    /**
     * تمام تست‌ها (چه نیاز به دیتابیس داشته باشند چه نه) هیچ‌وقت نباید واقعاً به Kavenegar وصل شوند —
     * چه به این دلیل که شبکه‌ی تست به api.kavenegar.com دسترسی ندارد، و چه چون طبق مستندات پروژه
     * (Rasta_unified_prompt.md) یک تماس synchronous واقعی به Kavenegar می‌تواند ۲۰-۳۰ ثانیه طول بکشد —
     * که کل سوییت تست را غیرقابل‌اجرا می‌کرد. این mock پیش‌فرض همیشه true برمی‌گرداند (بدون هیچ side
     * effect)، دقیقاً معادل رفتار SMSService::send()/sendTemplate() در حالت local بدون
     * KAVENEGAR_SEND_IN_LOCAL. تست‌هایی که می‌خواهند محتوای واقعی پیامک را assert کنند، می‌توانند خودشان
     * $this->mock(SMSService::class, ...) را در متد تست override کنند.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(SMSService::class, Mockery::mock(SMSService::class, function ($mock) {
            $mock->shouldReceive('send')->andReturn(true)->byDefault();
            $mock->shouldReceive('sendTemplate')->andReturn(true)->byDefault();
            $mock->shouldReceive('sendVerificationCode')->andReturn(true)->byDefault();
            $mock->shouldReceive('sendBookingConfirmation')->andReturn(true)->byDefault();
            $mock->shouldReceive('sendBookingReminder')->andReturn(true)->byDefault();
        }));
    }
}
