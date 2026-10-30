<?php

namespace Tests\Feature\Payment;

use App\Payments\Drivers\ZarinpalDriver;
use App\Payments\GatewayStartRequest;
use App\Payments\GatewayVerifyRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * ⭐ بسته‌ی 5ز، کار ۴ (۲۰۲۶-۱۰-۳۰): پیام خطاهای زرین‌پال از جدول رسمی v4 (`/paymentGateway/errorList.html`)، نه جدول API قدیمی
 * (‎-9 «IP»، ‎-10 «توکن»، ‎-11 «یافت نشد»، ‎-54 «آرشیو» همه غلط بودند)؛ و کد از هر دو شکل پاسخ خطا خوانده می‌شود.
 */
class ZarinpalErrorCodesTest extends TestCase
{
    private function driver(): ZarinpalDriver
    {
        return new ZarinpalDriver(['merchant_id' => '11111111-2222-3333-4444-555555555555'], sandbox: true);
    }

    public function test_messages_follow_the_official_v4_table(): void
    {
        $expected = [
            -9 => 'خطای اعتبارسنجی',
            -10 => 'آی‌پی یا کد پذیرنده',
            -11 => 'کد پذیرنده فعال نیست',
            -12 => 'تلاش بیش از حد',
            -13 => 'سقف تراکنش',
            -14 => 'دامنه‌ی ثبت‌شده‌ی درگاه',
            -15 => 'تعلیق',
            -16 => 'سطح نقره‌ای',
            -17 => 'سطح آبی',
            -18 => 'ارجاع‌دهنده',
            -19 => 'امکان ایجاد تراکنش',
            -40 => 'expire_in',
            -41 => '۱۰۰ میلیون تومان',
            -50 => 'مبلغ پرداخت‌شده با مبلغ تأیید',
            -51 => 'پرداخت ناموفق',
            -52 => 'خطای غیرمنتظره',
            -53 => 'متعلق به این کد پذیرنده نیست',
            -54 => 'اتوریتی',
            -55 => 'یافت نشد',
            -60 => 'برگشت تراکنش با بانک',
            -61 => 'قبلاً برگشت',
            -62 => 'آی‌پی درگاه تنظیم نشده',
            -63 => '۳۰ دقیقه',
            100 => 'موفق',
            101 => 'قبلاً تأیید شده',
        ];

        foreach ($expected as $code => $fragment) {
            $this->assertStringContainsString($fragment, ZarinpalDriver::errorMessage($code), "کد {$code}");
        }

        $this->assertStringNotContainsString('آرشیو', ZarinpalDriver::errorMessage(-54));
        $this->assertStringNotContainsString('توکن', ZarinpalDriver::errorMessage(-10));
        $this->assertStringContainsString('(کد: -77)', ZarinpalDriver::errorMessage(-77));
    }

    public function test_code_is_read_from_both_documented_error_shapes(): void
    {
        $this->assertSame(-10, ZarinpalDriver::errorCode(['data' => [], 'errors' => ['message' => 'Terminal is not valid', 'code' => -10, 'validations' => []]]));
        $this->assertSame(-54, ZarinpalDriver::errorCode(['message' => 'Invalid authority', 'errors' => ['authority' => ['Invalid authority.', '-54']]]));
        $this->assertSame(100, ZarinpalDriver::errorCode(['data' => ['code' => 100], 'errors' => []]));
        $this->assertSame(-999, ZarinpalDriver::errorCode(['message' => 'nothing']));
    }

    public function test_start_reports_the_field_shaped_error(): void
    {
        Http::fake(['*' => Http::response(['message' => 'The callback url domain…', 'errors' => ['callback_url' => ['The callback URL domain does not match the registered terminal domain.', '-14']]], 422)]);

        $result = $this->driver()->start(new GatewayStartRequest(50000, 'https://rasta.arisheh.ir/payments/return/x', 'تست'));

        $this->assertFalse($result->success);
        $this->assertFalse($result->retryable);
        $this->assertStringContainsString('دامنه‌ی ثبت‌شده‌ی درگاه', $result->message);
    }

    public function test_verify_reports_the_field_shaped_error(): void
    {
        Http::fake(['*' => Http::response(['message' => 'Invalid authority', 'errors' => ['authority' => ['Invalid authority.', '-54']]], 422)]);

        $result = $this->driver()->verify(new GatewayVerifyRequest(50000, ['Authority' => 'S000', 'Status' => 'OK']));

        $this->assertFalse($result->success);
        $this->assertStringContainsString('اتوریتی', $result->message);
    }
}
