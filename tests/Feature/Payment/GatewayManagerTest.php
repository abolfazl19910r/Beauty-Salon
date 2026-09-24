<?php

namespace Tests\Feature\Payment;

use App\Models\Salon;
use App\Models\SalonPaymentGateway;
use App\Payments\Contracts\PaymentGatewayDriver;
use App\Payments\GatewayManager;
use App\Payments\GatewayStartRequest;
use App\Payments\GatewayStartResult;
use App\Payments\GatewayVerifyRequest;
use App\Payments\GatewayVerifyResult;
use App\Services\PaymentService;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * ⭐ لایه‌ی چند درگاه — مرحله‌ی ۰ (۲۰۲۶-۰۹-۲۵).
 */
class GatewayManagerTest extends TestCase
{
    use RefreshDatabase;

    private Salon $salon;

    protected function setUp(): void
    {
        parent::setUp();

        $this->salon = app(CurrentSalon::class)->get();
    }

    /** یک manager که به‌جای driver واقعی، driverهای ساختگی با نتیجه‌ی از پیش تعیین‌شده برمی‌گردونه. */
    private function managerWith(array $resultsByGatewayId, array &$calls): GatewayManager
    {
        return new class($resultsByGatewayId, $calls) extends GatewayManager
        {
            public function __construct(private array $results, private array &$calls) {}

            public function driver(SalonPaymentGateway $gateway): PaymentGatewayDriver
            {
                $result = $this->results[$gateway->id];
                $calls = &$this->calls;

                return new class($gateway, $result, $calls) implements PaymentGatewayDriver
                {
                    public function __construct(private SalonPaymentGateway $g, private GatewayStartResult $r, private array &$calls) {}

                    public function key(): string
                    {
                        return $this->g->driver;
                    }

                    public function start(GatewayStartRequest $request): GatewayStartResult
                    {
                        $this->calls[] = $this->g->id;

                        return $this->r;
                    }

                    public function verify(GatewayVerifyRequest $request): GatewayVerifyResult
                    {
                        return new GatewayVerifyResult(true);
                    }
                };
            }
        };
    }

    private function gateway(int $priority, bool $active = true): SalonPaymentGateway
    {
        return $this->salon->paymentGateways()->create([
            'driver' => 'zarinpal', 'label' => "g{$priority}", 'credentials' => ['merchant_id' => "m{$priority}"],
            'is_active' => $active, 'priority' => $priority,
        ]);
    }

    private function request(): GatewayStartRequest
    {
        return GatewayStartRequest::fromToman(1000, 'https://example.test/cb', 'test');
    }

    public function test_merchant_field_keeps_the_zarinpal_gateway_row_in_sync(): void
    {
        $this->salon->update(['zarinpal_merchant_id' => 'aaaaaaaa-1111-2222-3333-bbbbbbbbbbbb']);
        $row = $this->salon->paymentGateways()->where('driver', 'zarinpal')->sole();
        $this->assertSame(['merchant_id' => 'aaaaaaaa-1111-2222-3333-bbbbbbbbbbbb'], $row->credentials);
        $this->assertNotSame('aaaaaaaa-1111-2222-3333-bbbbbbbbbbbb', DB::table('salon_payment_gateways')->where('id', $row->id)->value('credentials'), 'credentials باید رمزشده ذخیره بشه');

        $this->salon->update(['zarinpal_merchant_id' => null]);
        $this->assertSame(0, $this->salon->paymentGateways()->count());
        $this->assertFalse($this->salon->fresh()->acceptsOnlinePayments());
    }

    public function test_inactive_gateways_are_ignored_and_order_follows_priority(): void
    {
        $this->salon->paymentGateways()->delete();
        $b = $this->gateway(2);
        $a = $this->gateway(1);
        $this->gateway(0, active: false);

        $this->assertSame([$a->id, $b->id], app(GatewayManager::class)->gatewaysFor($this->salon)->pluck('id')->all());
    }

    public function test_failover_moves_to_the_next_gateway_only_when_the_first_is_unreachable(): void
    {
        $this->salon->paymentGateways()->delete();
        $first = $this->gateway(1);
        $second = $this->gateway(2);
        $calls = [];

        $manager = $this->managerWith([
            $first->id => GatewayStartResult::failed('down', retryable: true),
            $second->id => GatewayStartResult::redirect('https://pay.test/2', 'T2'),
        ], $calls);

        [$result, $used] = $manager->start($this->salon, $this->request());

        $this->assertTrue($result->success);
        $this->assertSame($second->id, $used->id);
        $this->assertSame([$first->id, $second->id], $calls);
    }

    public function test_a_non_retryable_error_is_returned_without_trying_other_gateways(): void
    {
        $this->salon->paymentGateways()->delete();
        $first = $this->gateway(1);
        $second = $this->gateway(2);
        $calls = [];

        $manager = $this->managerWith([
            $first->id => GatewayStartResult::failed('invalid merchant'),
            $second->id => GatewayStartResult::redirect('https://pay.test/2', 'T2'),
        ], $calls);

        [$result] = $manager->start($this->salon, $this->request());

        $this->assertFalse($result->success);
        $this->assertSame('invalid merchant', $result->message);
        $this->assertSame([$first->id], $calls);
    }

    public function test_the_customers_chosen_gateway_is_tried_first(): void
    {
        $this->salon->paymentGateways()->delete();
        $first = $this->gateway(1);
        $second = $this->gateway(2);
        $calls = [];

        $manager = $this->managerWith([
            $first->id => GatewayStartResult::redirect('https://pay.test/1', 'T1'),
            $second->id => GatewayStartResult::redirect('https://pay.test/2', 'T2'),
        ], $calls);

        [, $used] = $manager->start($this->salon, $this->request(), preferredGatewayId: $second->id);

        $this->assertSame($second->id, $used->id);
        $this->assertSame([$second->id], $calls);
    }

    public function test_verification_uses_the_gateway_the_payment_started_with_and_ignores_a_foreign_one(): void
    {
        $this->salon->update(['zarinpal_merchant_id' => 'aaaaaaaa-1111-2222-3333-bbbbbbbbbbbb']);
        $other = Salon::factory()->create(['zarinpal_merchant_id' => 'cccccccc-1111-2222-3333-dddddddddddd']);
        $foreign = $other->paymentGateways()->sole();

        Http::fake([
            '*request.json' => Http::response(['data' => ['code' => 100, 'authority' => 'A1']], 200),
            '*verify.json' => Http::response(['data' => ['code' => 100, 'ref_id' => 'R1']], 200),
        ]);

        $user = \App\Models\User::factory()->create();
        app(PaymentService::class)->createWalletChargePayment($user, 5000);
        // دستکاری session به درگاه یک سالن دیگه نباید پرداخت رو با مرچنت اون سالن تایید کنه
        session(['wallet_charge_gateway' => $foreign->id]);

        app(PaymentService::class)->verifyWalletChargePayment(new \Illuminate\Http\Request(['Authority' => 'A1', 'Status' => 'OK']), 5000);

        Http::assertSent(fn ($r) => str_ends_with($r->url(), 'verify.json') && $r['merchant_id'] === 'aaaaaaaa-1111-2222-3333-bbbbbbbbbbbb');
        Http::assertNotSent(fn ($r) => str_ends_with($r->url(), 'verify.json') && $r['merchant_id'] === 'cccccccc-1111-2222-3333-dddddddddddd');
    }

    public function test_amount_is_converted_from_toman_to_rial_once(): void
    {
        $this->assertSame(123450, GatewayStartRequest::fromToman(12345, 'u', 'd')->amountRial);
    }
}
