<?php

namespace Tests\Feature\Payment;

use App\Models\Invoice;
use App\Models\PaymentTransaction;
use App\Services\Payment\SubscriptionPaymentService;
use App\Support\CurrentSalon;
use App\Support\PlatformZarinpal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * ⭐ آزمون دستی 5ز (۲۰۲۶-۱۰-۳۰، بندهای ۱۲ و ۱۳): در داکر `ZARINPAL_MERCHANT_ID=` خالی بود؛ `env('KEY', 'default')` با کلید خالی همان ''
 * می‌دهد و خرید اشتراک/بسته‌ی پیامک با ‎-9 «The merchant id field is required» رد می‌شد. حالا مرچنت پلتفرم از PlatformZarinpal: مقدار
 * معتبر .env؛ در sandbox بی مقدار یک UUID آزمایشی (sandbox هر UUID را می‌پذیرد)؛ در درگاه واقعی بی مقدار ← پیام روشن، بی درخواست.
 * و پیام سقف مبلغ دیگر «(کد -999)» ندارد.
 */
class PlatformZarinpalMerchantTest extends TestCase
{
    use RefreshDatabase;

    private function invoice(float $toman = 1500000): Invoice
    {
        return Invoice::factory()->create(['salon_id' => app(CurrentSalon::class)->get()->id, 'amount' => $toman]);
    }

    public function test_an_empty_merchant_in_sandbox_uses_a_test_uuid(): void
    {
        config(['services.zarinpal.merchant_id' => '', 'services.zarinpal.sandbox' => true]);
        Http::fake(['*' => Http::response(['data' => ['code' => 100, 'authority' => 'S0001'], 'errors' => []])]);

        $this->assertSame(PlatformZarinpal::SANDBOX_MERCHANT, PlatformZarinpal::merchantId());
        $this->assertTrue(app(SubscriptionPaymentService::class)->createPayment($this->invoice())['success']);
        Http::assertSent(fn ($r) => $r['merchant_id'] === PlatformZarinpal::SANDBOX_MERCHANT);
    }

    public function test_an_empty_merchant_on_the_real_gateway_is_refused_clearly_without_a_request(): void
    {
        config(['services.zarinpal.merchant_id' => '', 'services.zarinpal.sandbox' => false]);
        Http::fake();

        $result = app(SubscriptionPaymentService::class)->createPayment($this->invoice());

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('ZARINPAL_MERCHANT_ID', $result['message']);
        Http::assertNothingSent();
        $this->assertSame('failed', PaymentTransaction::sole()->status);
    }

    public function test_a_valid_configured_merchant_is_used(): void
    {
        config(['services.zarinpal.merchant_id' => 'AAAAAAAA-1111-2222-3333-BBBBBBBBBBBB', 'services.zarinpal.sandbox' => false]);

        $this->assertSame('aaaaaaaa-1111-2222-3333-bbbbbbbbbbbb', PlatformZarinpal::merchantId());
    }

    public function test_the_amount_limit_message_has_no_fake_gateway_code(): void
    {
        config(['services.zarinpal.merchant_id' => '', 'services.zarinpal.sandbox' => true]);
        Http::fake();

        $result = app(SubscriptionPaymentService::class)->createPayment($this->invoice(218_270_000));

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('۱۰۰ میلیون تومان', $result['message']);
        $this->assertStringNotContainsString('-999', $result['message']);
    }
}
