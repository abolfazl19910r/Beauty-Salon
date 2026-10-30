<?php

namespace Tests\Feature\Payment;

use App\Models\Salon;
use App\Models\User;
use App\Payments\GatewayCatalog;
use App\Payments\GatewayManager;
use App\Payments\PayoutManager;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ⭐ بسته‌ی 5ز، کار ۰ (۲۰۲۶-۱۰-۳۰): «فقط زرین‌پال دیده شود». درگاه‌های دیگر کد و تست دارند ولی از فهرست‌های قابل انتخاب،
 * شروع پرداخت و تسویه کنار می‌روند؛ با `PAYMENT_ENABLED_DRIVERS` دوباره روشن می‌شوند. ردیف‌های موجود سالن‌ها پاک نمی‌شوند.
 */
class EnabledGatewayDriversTest extends TestCase
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
    }

    private function row(string $driver, array $credentials, int $priority): void
    {
        $this->salon->paymentGateways()->create(['driver' => $driver, 'credentials' => $credentials, 'is_active' => true, 'priority' => $priority]);
    }

    public function test_only_zarinpal_is_enabled_by_default(): void
    {
        $this->assertSame(['zarinpal'], GatewayCatalog::enabledKeys());
        $this->assertTrue(GatewayCatalog::isEnabled('zarinpal'));
        $this->assertFalse(GatewayCatalog::isEnabled('zibal'));
        $this->assertSame(['zarinpal'], array_keys(GatewayCatalog::enabled()));
    }

    public function test_an_existing_hidden_gateway_row_is_kept_but_never_used_for_payment(): void
    {
        $this->row('zibal', ['merchant' => 'zibal'], 1);

        $this->assertFalse($this->salon->fresh()->acceptsOnlinePayments());
        $this->assertTrue(app(GatewayManager::class)->gatewaysFor($this->salon)->isEmpty());
        $this->assertSame(1, $this->salon->paymentGateways()->count(), 'ردیف پاک نمی‌شود');

        $this->row('zarinpal', ['merchant_id' => '11111111-2222-3333-4444-555555555555'], 2);
        $this->assertSame(['zarinpal'], app(GatewayManager::class)->gatewaysFor($this->salon)->pluck('driver')->all());
    }

    public function test_a_hidden_gateway_is_not_used_for_automatic_payout(): void
    {
        $this->row('zibal', ['merchant' => 'zibal', 'payout_access_token' => 'tok', 'payout_wallet_id' => '123'], 1);

        $this->assertNull(app(PayoutManager::class)->gatewayFor($this->salon));
        $this->assertFalse($this->salon->fresh()->canAutoPayout());
    }

    public function test_owner_page_lists_and_accepts_only_enabled_gateways(): void
    {
        $this->row('zibal', ['merchant' => 'zibal'], 1);

        $this->actingAs($this->owner)->get(route('admin.payment-gateways.index'))
            ->assertOk()
            ->assertSee('زرین‌پال')
            ->assertDontSee('زیبال')->assertDontSee('وندار')->assertDontSee('آسان پرداخت')->assertDontSee('بانک ملت');

        $this->actingAs($this->owner)->post(route('admin.payment-gateways.store'), [
            'driver' => 'vandar', 'credentials' => ['api_key' => 'k'], 'is_active' => '1',
        ])->assertSessionHasErrors('driver');

        $zibal = $this->salon->paymentGateways()->where('driver', 'zibal')->first();
        $this->actingAs($this->owner)->post(route('admin.payment-gateways.test', $zibal->id))->assertNotFound();
    }

    public function test_salon_settings_shows_no_hidden_gateway_names(): void
    {
        $this->actingAs($this->owner)->get(route('admin.salon-settings.edit'))
            ->assertOk()
            ->assertDontSee('زیبال')->assertDontSee('آسان پرداخت');
    }

    public function test_the_switch_is_reversible_through_config(): void
    {
        config(['payments.enabled_drivers' => ['zarinpal', 'zibal']]);
        $this->row('zibal', ['merchant' => 'zibal'], 1);

        $this->assertSame(['zarinpal', 'zibal'], GatewayCatalog::enabledKeys());
        $this->assertSame(['zibal'], app(GatewayManager::class)->gatewaysFor($this->salon)->pluck('driver')->all());
        $this->actingAs($this->owner)->get(route('admin.payment-gateways.index'))->assertSee('زیبال');
    }
}
