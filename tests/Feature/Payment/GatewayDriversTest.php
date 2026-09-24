<?php

namespace Tests\Feature\Payment;

use App\Models\SalonPaymentGateway;
use App\Payments\Drivers\AsanPardakhtDriver;
use App\Payments\Drivers\VandarDriver;
use App\Payments\Drivers\ZibalDriver;
use App\Payments\GatewayStartRequest;
use App\Payments\GatewayVerifyRequest;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * ⭐ مرحله‌ی ۱ چند درگاه (۲۰۲۶-۰۹-۲۵) — driverهای زیبال، وندار و آسان پرداخت با پاسخ‌های مستندات رسمی.
 * همه‌ی driverها: مبلغ به ریال، verify با توکن ذخیره‌شده (نه callback)، و ردِ مبلغ ناهمخوان.
 */
class GatewayDriversTest extends TestCase
{
    private function start(int $rial = 250000): GatewayStartRequest
    {
        return new GatewayStartRequest($rial, 'https://salon.test/payments/return/abc', 'پیش پرداخت', '09121234567', null, '7', 42);
    }

    // ── زیبال ──

    public function test_zibal_start_sends_rial_and_redirects_to_the_start_page(): void
    {
        Http::fake(['gateway.zibal.ir/v1/request' => Http::response(['trackId' => 15966442233311, 'result' => 100, 'message' => 'success'])]);

        $result = (new ZibalDriver(['merchant' => 'zibal']))->start($this->start());

        $this->assertTrue($result->success);
        $this->assertSame('https://gateway.zibal.ir/start/15966442233311', $result->redirectUrl);
        $this->assertSame('15966442233311', $result->token);
        Http::assertSent(fn (Request $r) => $r['merchant'] === 'zibal' && $r['amount'] === 250000
            && $r['callbackUrl'] === 'https://salon.test/payments/return/abc' && $r['orderId'] === '42');
    }

    public function test_zibal_logical_error_is_not_retryable_but_a_5xx_is(): void
    {
        Http::fakeSequence('gateway.zibal.ir/*')
            ->push(['result' => 104, 'message' => 'invalid merchant'])
            ->push('', 503);
        $driver = new ZibalDriver(['merchant' => 'bad']);

        $first = $driver->start($this->start());
        $this->assertFalse($first->success);
        $this->assertFalse($first->retryable);
        $this->assertStringContainsString('نامعتبر', $first->message);

        $this->assertTrue($driver->start($this->start())->retryable);
    }

    public function test_zibal_verify_uses_the_stored_track_id_and_accepts_already_verified(): void
    {
        Http::fake(['gateway.zibal.ir/v1/verify' => Http::sequence()
            ->push(['result' => 100, 'status' => 1, 'amount' => 250000, 'refNumber' => 9988, 'cardNumber' => '62741****44'])
            ->push(['result' => 201, 'status' => 1, 'amount' => 250000])]);
        $driver = new ZibalDriver(['merchant' => 'zibal']);
        $request = new GatewayVerifyRequest(250000, ['trackId' => '666', 'success' => '1', 'status' => '2'], '15966442233311', 42);

        $ok = $driver->verify($request);
        $this->assertTrue($ok->success);
        $this->assertSame('9988', $ok->refId);
        $this->assertSame('62741****44', $ok->cardPan);
        Http::assertSent(fn (Request $r) => $r['trackId'] === 15966442233311);

        $this->assertTrue($driver->verify($request)->success, 'کد ۲۰۱ یعنی قبلاً تایید شده — موفق');
    }

    public function test_zibal_rejects_an_amount_mismatch_and_does_not_verify_a_failed_callback(): void
    {
        Http::fake(['gateway.zibal.ir/v1/verify' => Http::response(['result' => 100, 'status' => 1, 'amount' => 1000])]);
        $driver = new ZibalDriver(['merchant' => 'zibal']);

        $this->assertFalse($driver->verify(new GatewayVerifyRequest(250000, ['success' => '1'], '1', 1))->success);

        Http::fake();
        $cancelled = $driver->verify(new GatewayVerifyRequest(250000, ['success' => '0', 'status' => '3'], '1', 1));
        $this->assertFalse($cancelled->success);
        $this->assertTrue($cancelled->cancelledByUser);
    }

    // ── وندار ──

    public function test_vandar_start_uses_api_v4_and_redirects_to_the_token_page(): void
    {
        Http::fake(['ipg.vandar.io/api/v4/send' => Http::response(['status' => 1, 'token' => 'VT-1'])]);

        $result = (new VandarDriver(['api_key' => 'key-123']))->start($this->start());

        $this->assertTrue($result->success);
        $this->assertSame('https://ipg.vandar.io/v4/VT-1', $result->redirectUrl);
        Http::assertSent(fn (Request $r) => $r['api_key'] === 'key-123' && $r['amount'] === 250000
            && $r['factorNumber'] === '42' && $r['mobile_number'] === '09121234567');
    }

    public function test_vandar_error_messages_come_from_the_errors_array(): void
    {
        Http::fake(['ipg.vandar.io/*' => Http::response(['status' => 0, 'errors' => ['api_key معتبر نیست']], 422)]);

        $result = (new VandarDriver(['api_key' => 'x']))->start($this->start());

        $this->assertFalse($result->success);
        $this->assertFalse($result->retryable);
        $this->assertSame('api_key معتبر نیست', $result->message);
    }

    public function test_vandar_verify_checks_payment_status_and_amount(): void
    {
        Http::fake(['ipg.vandar.io/api/v4/verify' => Http::response(['status' => 1, 'amount' => '250000.00', 'transId' => 159178352177, 'cardNumber' => '603799******7999'])]);
        $driver = new VandarDriver(['api_key' => 'key-123']);

        $ok = $driver->verify(new GatewayVerifyRequest(250000, ['token' => 'forged', 'payment_status' => 'OK'], 'VT-1', 42));
        $this->assertTrue($ok->success);
        $this->assertSame('159178352177', $ok->refId);
        Http::assertSent(fn (Request $r) => $r['token'] === 'VT-1');

        $this->assertFalse($driver->verify(new GatewayVerifyRequest(999, ['payment_status' => 'OK'], 'VT-1', 42))->success);

        $failed = $driver->verify(new GatewayVerifyRequest(250000, ['payment_status' => 'FAILED'], 'VT-1', 42));
        $this->assertFalse($failed->success);
        $this->assertTrue($failed->cancelledByUser);
    }

    // ── آسان پرداخت ──

    private function asan(): AsanPardakhtDriver
    {
        return new AsanPardakhtDriver(['merchant_config_id' => '1234', 'username' => 'u', 'password' => 'p']);
    }

    public function test_asanpardakht_start_returns_a_post_form_with_the_ref_id(): void
    {
        Http::fake([
            'ipgrest.asanpardakht.ir/v1/Time' => Http::response('"20260925 101500"'),
            'ipgrest.asanpardakht.ir/v1/Token' => Http::response('"REF-ABC"'),
        ]);

        $result = $this->asan()->start($this->start());

        $this->assertTrue($result->success);
        $this->assertSame('POST', $result->method);
        $this->assertSame('https://asan.shaparak.ir', $result->redirectUrl);
        $this->assertSame(['RefId' => 'REF-ABC', 'mobileap' => '09121234567'], $result->formFields);
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/Token') && $r->header('usr') === ['u'] && $r->header('pwd') === ['p']
            && $r['merchantConfigurationId'] === 1234 && $r['localInvoiceId'] === 42 && $r['amountInRials'] === 250000
            && $r['localDate'] === '20260925 101500' && $r['serviceTypeId'] === 1);
    }

    public function test_asanpardakht_status_codes_map_to_messages_and_only_real_5xx_fail_over(): void
    {
        Http::fake([
            'ipgrest.asanpardakht.ir/v1/Time' => Http::response('"20260925 101500"'),
            'ipgrest.asanpardakht.ir/v1/Token' => Http::sequence()->push('', 474)->push('', 572)->push('', 503),
        ]);

        $ip = $this->asan()->start($this->start());
        $this->assertStringContainsString('IP', $ip->message);
        $this->assertFalse($ip->retryable);
        $this->assertFalse($this->asan()->start($this->start())->retryable, 'کد ۵۷x خطای منطقی است نه قطعی');
        $this->assertTrue($this->asan()->start($this->start())->retryable);
    }

    public function test_asanpardakht_verify_reads_tran_result_then_verifies_and_settles(): void
    {
        Http::fake([
            'ipgrest.asanpardakht.ir/v1/TranResult*' => Http::response(['payGateTranID' => 777, 'rrn' => 'RRN-1', 'refID' => 'R1', 'cardNumber' => '6037****1234', 'amount' => 250000]),
            'ipgrest.asanpardakht.ir/v1/Verify' => Http::response('', 200),
            'ipgrest.asanpardakht.ir/v1/Settlement' => Http::response('', 200),
        ]);

        $result = $this->asan()->verify(new GatewayVerifyRequest(250000, [], 'REF-ABC', 42));

        $this->assertTrue($result->success);
        $this->assertSame('RRN-1', $result->refId);
        Http::assertSent(fn (Request $r) => str_contains($r->url(), 'TranResult') && str_contains($r->url(), 'localInvoiceId=42'));
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/Verify') && $r['payGateTranId'] === 777);
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/Settlement') && $r['payGateTranId'] === 777);
    }

    public function test_asanpardakht_no_record_is_a_cancellation_and_a_failed_verify_is_not_settled(): void
    {
        Http::fake(['ipgrest.asanpardakht.ir/v1/TranResult*' => Http::response('', 472)]);
        $cancelled = $this->asan()->verify(new GatewayVerifyRequest(250000, [], 'REF', 42));
        $this->assertFalse($cancelled->success);
        $this->assertTrue($cancelled->cancelledByUser);

        Http::fake([
            'ipgrest.asanpardakht.ir/v1/TranResult*' => Http::response(['payGateTranID' => 777, 'amount' => 250000]),
            'ipgrest.asanpardakht.ir/v1/Verify' => Http::response('', 573),
            'ipgrest.asanpardakht.ir/v1/Settlement' => Http::response('', 200),
        ]);
        $this->assertFalse($this->asan()->verify(new GatewayVerifyRequest(250000, [], 'REF', 42))->success);
        Http::assertNotSent(fn (Request $r) => str_ends_with($r->url(), '/Settlement'));
    }

    // ── کارمزد ──

    public function test_fee_is_percent_plus_fixed_rounded_up_to_a_whole_toman(): void
    {
        $gateway = new SalonPaymentGateway(['fee_percent' => 1.5, 'fee_fixed_toman' => 500]);

        $this->assertSame(20000, $gateway->feeRialFor(1000000)); // ۱.۵٪ از ۱۰۰٬۰۰۰ تومان = ۱۵۰۰ + ۵۰۰
        $this->assertSame(2005, $gateway->feeTomanFor(100333)); // ۱۵۰۴.۹۹۵ → ۱۵۰۵ + ۵۰۰
        $this->assertSame(0, (new SalonPaymentGateway(['fee_percent' => 0, 'fee_fixed_toman' => 0]))->feeRialFor(1000000));
        $this->assertSame(0, $gateway->feeRialFor(0));
    }
}
