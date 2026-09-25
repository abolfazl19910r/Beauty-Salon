<?php

namespace Tests\Feature\Payment;

use App\Payments\Drivers\ZarinpalPayoutDriver;
use App\Payments\Drivers\ZibalPayoutDriver;
use App\Payments\HttpFailure;
use App\Payments\PayoutRequest;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * ⭐ نتیجه‌ی نامعلوم تسویه (۲۰۲۶-۰۹-۲۶): درخواست واریز فرستاده شد ولی پاسخ نرسید → unknown؛ برداشت برای بررسی دستی در
 * processing می‌مونه و به کیف پول متخصص برنمی‌گرده (وگرنه ممکن بود دو بار پرداخت بشه). «هرگز فرستاده نشد» (cURL 6/7/35/60)
 * ناموفق ساده‌ست. زرین‌پال و زیبال اینجا؛ وندار در VandarPayoutTest.
 */
class UnknownPayoutOutcomeTest extends TestCase
{
    private const TIMEOUT = 'cURL error 28: Operation timed out after 30001 milliseconds with 0 bytes received';

    private const NO_DNS = 'cURL error 6: Could not resolve host: gateway.example';

    private const REFUSED = 'cURL error 7: Failed to connect to gateway.example port 443';

    public function test_the_classifier_separates_never_sent_from_lost_answers(): void
    {
        foreach ([self::NO_DNS, self::REFUSED, 'cURL error 35: SSL connect error', 'cURL error 60: SSL certificate problem'] as $message) {
            $this->assertTrue(HttpFailure::neverSent(new ConnectionException($message)), $message);
        }
        foreach ([self::TIMEOUT, 'cURL error 52: Empty reply from server', 'cURL error 56: Recv failure: Connection reset by peer', 'something else'] as $message) {
            $this->assertFalse(HttpFailure::neverSent(new ConnectionException($message)), $message);
        }
        $this->assertTrue(HttpFailure::neverSent(new \RuntimeException('wrapped', 0, new ConnectionException(self::REFUSED))));
    }

    private function payoutRequest(): PayoutRequest
    {
        return new PayoutRequest(2500000, 'IR060180000000000000020600', 'تسویه حساب متخصص', '9');
    }

    public function test_zarinpal_and_zibal_payouts_never_treat_a_lost_answer_as_failed(): void
    {
        Log::spy();
        $drivers = [
            'zarinpal' => fn () => new ZarinpalPayoutDriver(['merchant_id' => 'm', 'payout_api_key' => 'k'], false),
            'zibal' => fn () => new ZibalPayoutDriver(['payout_access_token' => 't', 'payout_wallet_id' => '1']),
        ];

        foreach ($drivers as $name => $make) {
            foreach ([Http::failedConnection(self::TIMEOUT), Http::response(['message' => 'Bad Gateway'], 502)] as $answer) {
                Http::swap(new \Illuminate\Http\Client\Factory);
                Http::fake(['*' => $answer]);
                $result = $make()->payout($this->payoutRequest());
                $this->assertFalse($result->success, $name);
                $this->assertTrue($result->unknown, "{$name}: پاسخ نرسید = نامعلوم");
                $this->assertStringContainsString('#9', $result->message, 'مدیر بدونه کدوم برداشت رو بررسی کنه');
                Http::assertSentCount(1); // تکرار کور نه
            }

            foreach ([self::NO_DNS, self::REFUSED] as $error) {
                Http::swap(new \Illuminate\Http\Client\Factory);
                Http::fake(['*' => Http::failedConnection($error)]);
                $result = $make()->payout($this->payoutRequest());
                $this->assertFalse($result->success);
                $this->assertFalse($result->unknown, "{$name}: فرستاده نشد = ناموفق ساده");
            }
        }
    }

    public function test_a_definite_payout_rejection_is_still_a_plain_failure(): void
    {
        Http::fake(['api.zibal.ir/*' => Http::response(['result' => 6, 'message' => 'موجودی کیف پول کافی نیست'])]);
        $zibal = (new ZibalPayoutDriver(['payout_access_token' => 't', 'payout_wallet_id' => '1']))->payout($this->payoutRequest());
        $this->assertFalse($zibal->unknown);

        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake(['*' => Http::response(['errors' => ['code' => -11, 'message' => 'IBAN نامعتبر']], 422)]);
        $zarinpal = (new ZarinpalPayoutDriver(['merchant_id' => 'm', 'payout_api_key' => 'k'], false))->payout($this->payoutRequest());
        $this->assertFalse($zarinpal->unknown);
        $this->assertSame('IBAN نامعتبر', $zarinpal->message);
    }
}
