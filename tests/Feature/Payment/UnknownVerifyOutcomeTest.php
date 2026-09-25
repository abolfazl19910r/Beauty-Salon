<?php

namespace Tests\Feature\Payment;

use App\Payments\Drivers\AsanPardakhtDriver;
use App\Payments\Drivers\VandarDriver;
use App\Payments\Drivers\ZarinpalDriver;
use App\Payments\Drivers\ZibalDriver;
use App\Payments\GatewayVerifyRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * ⭐ پاسخ تایید درگاه‌های غیرمستقیم نرسید (۲۰۲۶-۰۹-۲۶): زرین‌پال، زیبال، وندار و آسان پرداخت حالا unanswered ثبت می‌کنن
 * (نه «ناموفق» ساده) تا payments:reconcile دوباره بپرسه — UnansweredVerifyRecoveryTest. «هرگز فرستاده نشد» ناموفق ساده‌ست.
 * درگاه‌های مستقیم (سامان، ملت، پارسیان) از قبل unanswered دارن.
 */
class UnknownVerifyOutcomeTest extends TestCase
{
    private const TIMEOUT = 'cURL error 28: Operation timed out after 30001 milliseconds with 0 bytes received';

    private const REFUSED = 'cURL error 7: Failed to connect to gateway.example port 443';

    /** @return array<string, array{0: callable, 1: string, 2: array}> driver، الگوی URL، callback موفق */
    private function indirect(): array
    {
        return [
            'zarinpal' => [fn () => new ZarinpalDriver(['merchant_id' => 'm'], false), '*zarinpal.com/*', ['Authority' => 'A1', 'Status' => 'OK']],
            'zibal' => [fn () => new ZibalDriver(['merchant' => 'zibal']), 'gateway.zibal.ir/*', ['trackId' => '77', 'success' => '1', 'status' => '2']],
            'vandar' => [fn () => new VandarDriver(['api_key' => 'k']), 'ipg.vandar.io/*', ['token' => 'VT', 'payment_status' => 'OK']],
            'asanpardakht' => [fn () => new AsanPardakhtDriver(['merchant_config_id' => '1', 'username' => 'u', 'password' => 'p']), 'ipgrest.asanpardakht.ir/*', []],
        ];
    }

    private function verify(callable $make, array $callback)
    {
        return $make()->verify(new GatewayVerifyRequest(250000, $callback, 'TOKEN', 42));
    }

    public function test_every_indirect_gateway_marks_a_lost_verify_answer_as_unanswered(): void
    {
        foreach ($this->indirect() as $name => [$make, $pattern, $callback]) {
            foreach ([Http::failedConnection(self::TIMEOUT), Http::response('', 503)] as $answer) {
                Http::swap(new \Illuminate\Http\Client\Factory);
                Http::fake([$pattern => $answer]);
                $result = $this->verify($make, $callback);
                $this->assertFalse($result->success, $name);
                $this->assertTrue($result->raw['unanswered'] ?? false, "{$name}: پاسخ تایید نرسید");
                $this->assertStringContainsString('پیامک', $result->message);
            }

            Http::swap(new \Illuminate\Http\Client\Factory);
            Http::fake([$pattern => Http::failedConnection(self::REFUSED)]);
            $this->assertArrayNotHasKey('unanswered', $this->verify($make, $callback)->raw, "{$name}: فرستاده نشد = چیزی تایید نشده");
        }
    }

    public function test_asan_pardakht_business_codes_in_the_5xx_range_are_definite_but_571_572_are_unknown(): void
    {
        $asan = $this->indirect()['asanpardakht'][0];

        Http::fake(['ipgrest.asanpardakht.ir/v1/TranResult*' => Http::response(['payGateTranID' => 7, 'amount' => 250000]), 'ipgrest.asanpardakht.ir/v1/Verify' => Http::response('', 573)]);
        $this->assertArrayNotHasKey('unanswered', $this->verify($asan, [])->raw, '۵۷۳ = امکان تایید نیست (قطعی)');

        foreach ([571, 572] as $code) {
            Http::swap(new \Illuminate\Http\Client\Factory);
            Http::fake(['ipgrest.asanpardakht.ir/v1/TranResult*' => Http::response('', $code)]);
            $this->assertTrue($this->verify($asan, [])->raw['unanswered'] ?? false, "TranResult {$code}");
        }

        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake(['ipgrest.asanpardakht.ir/v1/TranResult*' => Http::response(['payGateTranID' => 7, 'amount' => 250000]), 'ipgrest.asanpardakht.ir/v1/Verify' => Http::response('', 572)]);
        $this->assertTrue($this->verify($asan, [])->raw['unanswered'] ?? false, 'Verify ۵۷۲ = وضعیت نامشخص');
    }

    public function test_asan_pardakht_verified_but_not_settled_is_a_success_flagged_for_the_owner(): void
    {
        Log::spy();
        $asan = $this->indirect()['asanpardakht'][0];

        foreach ([Http::response('', 503), Http::failedConnection(self::TIMEOUT)] as $settlement) {
            Http::swap(new \Illuminate\Http\Client\Factory);
            Http::fake([
                'ipgrest.asanpardakht.ir/v1/TranResult*' => Http::response(['payGateTranID' => 7, 'rrn' => 'RRN-7', 'amount' => 250000]),
                'ipgrest.asanpardakht.ir/v1/Verify' => Http::response('', 200),
                'ipgrest.asanpardakht.ir/v1/Settlement' => $settlement,
            ]);

            $result = $this->verify($asan, []);

            $this->assertTrue($result->success, 'Verify موفق = پول مشتری گرفته شده؛ قبلاً «ناموفق» و نوبت لغو می‌شد');
            $this->assertSame('RRN-7', $result->refId);
            $this->assertTrue($result->raw['settlement_failed']);
        }
        Log::shouldHaveReceived('error')->withArgs(fn ($m) => str_contains($m, 'Settlement'))->twice();
    }
}
