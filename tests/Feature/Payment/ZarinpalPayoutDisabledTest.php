<?php

namespace Tests\Feature\Payment;

use App\Jobs\ProcessWithdrawalJob;
use App\Models\Salon;
use App\Models\Specialist;
use App\Models\User;
use App\Models\WithdrawalRequest;
use App\Payments\GatewayCatalog;
use App\Payments\PayoutManager;
use App\Services\Payment\SalonPayoutService;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * ⭐ بسته‌ی 5ز، کار ۱ (۲۰۲۶-۱۰-۳۰): تسویه‌ی خودکار زرین‌پال خاموش. `ZarinpalPayoutDriver` به `/pg/v4/payout.json` با `payout_api_key`
 * می‌زد که در هیچ صفحه‌ی مستندات رسمی نیست؛ برداشت متخصص در سالن زرین‌پالی = تسویه‌ی دستی مدیر (مسیر موجود) با توضیح در پنل.
 */
class ZarinpalPayoutDisabledTest extends TestCase
{
    use RefreshDatabase;

    private Salon $salon;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->salon = app(CurrentSalon::class)->get();
        $this->owner = User::factory()->create(['is_admin' => true]);
        $this->salon->paymentGateways()->delete();
        // ردیفی که پیش از این بسته توکن Payout گرفته بود
        $this->salon->paymentGateways()->create([
            'driver' => 'zarinpal', 'is_active' => true, 'priority' => 1,
            'credentials' => ['merchant_id' => 'aaaaaaaa-1111-2222-3333-bbbbbbbbbbbb', 'payout_api_key' => 'old-salon-token-0123456789'],
        ]);
    }

    public function test_zarinpal_has_no_payout_and_no_payout_token_field(): void
    {
        $this->assertFalse(GatewayCatalog::supportsPayout('zarinpal'));
        $this->assertArrayNotHasKey('payout_api_key', GatewayCatalog::fields('zarinpal'));
        $this->assertSame('', GatewayCatalog::payoutLabels());
    }

    public function test_an_old_payout_token_is_never_used(): void
    {
        Http::fake();
        $withdrawal = WithdrawalRequest::factory()->create(['status' => 'processing', 'specialist_id' => Specialist::factory()->create()->id]);

        $this->assertNull(app(PayoutManager::class)->gatewayFor($this->salon));
        $this->assertFalse($this->salon->fresh()->canAutoPayout());
        $result = app(SalonPayoutService::class)->payout($withdrawal);

        $this->assertFalse($result['success']);
        Http::assertNothingSent();
    }

    public function test_the_job_never_calls_zarinpal_payout(): void
    {
        Http::fake();
        $withdrawal = WithdrawalRequest::factory()->create(['status' => 'processing', 'specialist_id' => Specialist::factory()->create()->id]);

        (new ProcessWithdrawalJob($withdrawal->id))->handle(app(SalonPayoutService::class));

        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'payout'));
    }

    public function test_withdrawal_page_explains_manual_settlement_and_auto_payout_is_refused(): void
    {
        Queue::fake();
        $withdrawal = WithdrawalRequest::factory()->create(['status' => 'pending']);

        $this->actingAs($this->owner)->get("/admin/wallet/withdrawals/{$withdrawal->id}")
            ->assertOk()
            ->assertSee('زرین‌پال تسویه‌ی خودکار به شبا ندارد')
            ->assertDontSee('تسویه‌ی آنلاین خودکار (زرین‌پال)');

        $this->actingAs($this->owner)->post("/admin/wallet/withdrawals/{$withdrawal->id}/auto-payout")->assertSessionHas('error');
        Queue::assertNothingPushed();

        $this->actingAs($this->owner)->put("/admin/wallet/withdrawals/{$withdrawal->id}/approve", ['payment_reference' => 'MANUAL-1'])
            ->assertSessionHas('success');
        $this->assertSame('completed', $withdrawal->fresh()->status);
    }

    public function test_gateways_page_has_no_payout_token_field_and_says_why(): void
    {
        $this->actingAs($this->owner)->get(route('admin.payment-gateways.index'))
            ->assertOk()
            ->assertDontSee('توکن Payout')
            ->assertDontSee('name="credentials[payout_api_key]"', false)
            ->assertSee('زرین‌پال تسویه‌ی خودکار به شبا ندارد');

        $this->actingAs($this->owner)->get(route('admin.salon-settings.edit'))
            ->assertOk()->assertSee('زرین‌پال تسویه‌ی خودکار به شبا ندارد');
    }

    public function test_saving_the_zarinpal_gateway_drops_the_old_payout_token(): void
    {
        $gateway = $this->salon->zarinpalGateway();

        $this->actingAs($this->owner)->put(route('admin.payment-gateways.update', $gateway->id), [
            'credentials' => ['merchant_id' => 'aaaaaaaa-1111-2222-3333-bbbbbbbbbbbb', 'payout_api_key' => 'new-token'],
            'is_active' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertArrayNotHasKey('payout_api_key', (array) $gateway->fresh()->credentials);
    }
}
