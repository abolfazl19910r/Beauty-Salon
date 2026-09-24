<?php

namespace Tests\Feature\Payment;

use App\Models\SalonPaymentGateway;
use App\Models\User;
use App\Payments\Drivers\SamanDriver;
use App\Payments\GatewayStartRequest;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * ⭐ مرحله‌ی ۲ چند درگاه — حالت «بلوپی» (neo-pg) درگاه سامان: فرم به آدرس هدر X-IPG-Url پاسخ توکن، فقط اگه
 * آدرس مطمئن باشه؛ وگرنه صفحه‌ی کلاسیک.
 */
class SamanBlupayTest extends TestCase
{
    use RefreshDatabase;

    private const TERMINAL = '13012345';

    private SalonPaymentGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();
        $salon = app(CurrentSalon::class)->get();
        $salon->paymentGateways()->delete();
        $this->gateway = $salon->paymentGateways()->create(['driver' => 'saman', 'credentials' => ['terminal_id' => self::TERMINAL], 'priority' => 1]);
    }

    private function startWith(array $credentials, array $headers = []): \App\Payments\GatewayStartResult
    {
        Http::fake(['sep.shaparak.ir/*' => Http::response(['status' => 1, 'token' => 'T-9'], 200, $headers)]);

        return (new SamanDriver($credentials))->start(new GatewayStartRequest(250000, 'https://salon.test/r', 'x', transactionId: 5));
    }

    public function test_blupay_mode_sends_the_form_to_the_ipg_url_from_the_token_response(): void
    {
        $result = $this->startWith(['terminal_id' => self::TERMINAL, 'redirect_mode' => 'blupay'], ['X-IPG-Url' => 'https://neo-pg.sep.ir/transaction/init']);

        $this->assertSame('https://neo-pg.sep.ir/transaction/init', $result->redirectUrl);
        $this->assertSame(['Token' => 'T-9'], $result->formFields);
        $this->assertSame('blupay', $result->raw['mode']);
    }

    public function test_blupay_falls_back_to_the_classic_page_without_a_trusted_ipg_url(): void
    {
        $blupay = ['terminal_id' => self::TERMINAL, 'redirect_mode' => 'blupay'];

        $this->assertSame(SamanDriver::PAYMENT_URL, $this->startWith($blupay)->redirectUrl, 'neo-pg برای ترمینال فعال نیست');
        $this->assertSame(SamanDriver::PAYMENT_URL, $this->startWith($blupay, ['X-IPG-Url' => 'https://evil.example/sep.ir'])->redirectUrl);
        $this->assertSame(SamanDriver::PAYMENT_URL, $this->startWith($blupay, ['X-IPG-Url' => 'http://neo-pg.sep.ir/init'])->redirectUrl);
        $this->assertSame(SamanDriver::PAYMENT_URL, $this->startWith($blupay, ['X-IPG-Url' => 'https://sep.ir.evil.example/init'])->redirectUrl);
        $this->assertSame(
            SamanDriver::PAYMENT_URL,
            $this->startWith(['terminal_id' => self::TERMINAL], ['X-IPG-Url' => 'https://neo-pg.sep.ir/transaction/init'])->redirectUrl,
            'حالت کلاسیک (پیش‌فرض) هدر رو نادیده می‌گیره',
        );
    }

    public function test_owner_chooses_blupay_from_a_select_on_the_gateways_page(): void
    {
        $owner = User::factory()->create(['is_admin' => true]);

        $this->actingAs($owner)->get(route('admin.payment-gateways.index'))
            ->assertOk()
            ->assertSee('name="credentials[redirect_mode]"', false)
            ->assertSee('درگاه + بلوپی');

        $this->actingAs($owner)->put(route('admin.payment-gateways.update', $this->gateway), [
            'credentials' => ['terminal_id' => self::TERMINAL, 'redirect_mode' => 'blupay'], 'is_active' => '1',
        ])->assertSessionHasNoErrors();
        $this->assertSame('blupay', $this->gateway->fresh()->credentials['redirect_mode']);

        $this->actingAs($owner)->put(route('admin.payment-gateways.update', $this->gateway), [
            'credentials' => ['terminal_id' => self::TERMINAL, 'redirect_mode' => 'something'], 'is_active' => '1',
        ])->assertSessionHasErrors('credentials.redirect_mode');
    }
}
