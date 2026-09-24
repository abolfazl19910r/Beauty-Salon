<?php

namespace Tests\Feature\Admin;

use App\Models\Salon;
use App\Models\SalonPaymentGateway;
use App\Models\User;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * ⭐ صفحه‌ی «درگاه‌های پرداخت» مالک سالن (مرحله‌ی ۱ چند درگاه، ۲۰۲۶-۰۹-۲۵).
 */
class AdminPaymentGatewayTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Salon $salon;

    protected function setUp(): void
    {
        parent::setUp();
        $this->salon = app(CurrentSalon::class)->get();
        $this->owner = User::factory()->create(['is_admin' => true]); // اولین ادمین = owner
        $this->salon->paymentGateways()->delete();
    }

    private function add(string $driver, array $credentials, array $extra = []): void
    {
        $this->actingAs($this->owner)->post(route('admin.payment-gateways.store'), [
            'driver' => $driver, 'credentials' => $credentials, 'is_active' => '1',
        ] + $extra)->assertSessionHasNoErrors();
    }

    public function test_owner_sees_the_page_with_all_four_gateway_types_and_the_sidebar_link(): void
    {
        $this->actingAs($this->owner)->get(route('admin.payment-gateways.index'))
            ->assertOk()
            ->assertSee('پرداخت آنلاین سالن غیرفعال است')
            ->assertSee('زرین‌پال')->assertSee('زیبال')->assertSee('آسان پرداخت')->assertSee('وندار');

        $this->actingAs($this->owner)->get(route('admin.dashboard'))
            ->assertSee(route('admin.payment-gateways.index'), false);
    }

    public function test_staff_admin_cannot_manage_gateways(): void
    {
        $staff = User::factory()->create(['is_admin' => true]);
        $this->salon->admins()->syncWithoutDetaching([$staff->id => ['role' => 'staff']]);

        $this->actingAs($staff)->get(route('admin.payment-gateways.index'))->assertForbidden();
        $this->actingAs($staff)->post(route('admin.payment-gateways.store'), ['driver' => 'zibal', 'credentials' => ['merchant' => 'zibal']])->assertForbidden();
        $this->assertSame(0, $this->salon->paymentGateways()->count());
    }

    public function test_owner_adds_gateways_with_fees_in_order_and_secrets_are_encrypted_and_never_shown(): void
    {
        $this->add('zibal', ['merchant' => 'zibal'], ['fee_percent' => '۱٫۵', 'fee_fixed_toman' => '۵۰۰']);
        $this->add('vandar', ['api_key' => 'vandar-secret-key-123']);
        $this->add('asanpardakht', ['merchant_config_id' => '1234', 'username' => 'asan-user', 'password' => 'asan-pass-999']);

        $gateways = $this->salon->paymentGateways()->orderBy('priority')->get();
        $this->assertSame(['zibal', 'vandar', 'asanpardakht'], $gateways->pluck('driver')->all());
        $this->assertSame([1, 2, 3], $gateways->pluck('priority')->all());
        $this->assertSame(1.5, (float) $gateways[0]->fee_percent);
        $this->assertSame(500, $gateways[0]->fee_fixed_toman);
        $this->assertSame('vandar-secret-key-123', $gateways[1]->credentials['api_key']);
        $this->assertStringNotContainsString('vandar-secret', DB::table('salon_payment_gateways')->where('id', $gateways[1]->id)->value('credentials'));

        $this->actingAs($this->owner)->get(route('admin.payment-gateways.index'))
            ->assertDontSee('vandar-secret-key-123')->assertDontSee('asan-pass-999')
            ->assertSee('asan-user')->assertSee('1.5٪ + 500 تومان');
    }

    public function test_validation_rejects_duplicates_bad_credentials_and_excessive_fees(): void
    {
        $this->add('zibal', ['merchant' => 'zibal']);

        $post = fn (array $data) => $this->actingAs($this->owner)->post(route('admin.payment-gateways.store'), $data);

        $post(['driver' => 'zibal', 'credentials' => ['merchant' => 'x']])->assertSessionHasErrors('driver');
        $post(['driver' => 'idpay', 'credentials' => []])->assertSessionHasErrors('driver');
        $post(['driver' => 'zarinpal', 'credentials' => ['merchant_id' => 'not-a-uuid']])->assertSessionHasErrors('credentials.merchant_id');
        $post(['driver' => 'asanpardakht', 'credentials' => ['merchant_config_id' => '12', 'username' => 'u']])->assertSessionHasErrors('credentials.password');
        $post(['driver' => 'vandar', 'credentials' => ['api_key' => 'k'], 'fee_percent' => '25'])->assertSessionHasErrors('fee_percent');
        $this->assertSame(1, $this->salon->paymentGateways()->count());
    }

    public function test_update_keeps_a_secret_when_left_blank_and_can_deactivate(): void
    {
        $this->add('vandar', ['api_key' => 'original-key']);
        $gateway = $this->salon->paymentGateways()->sole();

        $this->actingAs($this->owner)->put(route('admin.payment-gateways.update', $gateway->id), [
            'label' => 'پرداخت سریع', 'credentials' => ['api_key' => ''], 'fee_fixed_toman' => '1000',
        ])->assertSessionHasNoErrors();

        $gateway->refresh();
        $this->assertSame('original-key', $gateway->credentials['api_key']);
        $this->assertFalse($gateway->is_active);
        $this->assertSame('پرداخت سریع', $gateway->displayName());
        $this->assertFalse($this->salon->fresh()->acceptsOnlinePayments());

        $this->actingAs($this->owner)->put(route('admin.payment-gateways.update', $gateway->id), [
            'credentials' => ['api_key' => 'new-key'], 'is_active' => '1',
        ]);
        $this->assertSame('new-key', $gateway->fresh()->credentials['api_key']);
        $this->assertTrue($this->salon->fresh()->acceptsOnlinePayments());
    }

    public function test_order_can_be_changed_and_deleting_resequences(): void
    {
        $this->add('zibal', ['merchant' => 'zibal']);
        $this->add('vandar', ['api_key' => 'k']);
        $this->add('asanpardakht', ['merchant_config_id' => '1', 'username' => 'u', 'password' => 'p']);
        $asan = $this->salon->paymentGateways()->where('driver', 'asanpardakht')->sole();

        $this->actingAs($this->owner)->post(route('admin.payment-gateways.move', $asan->id), ['direction' => 'up']);
        $this->actingAs($this->owner)->post(route('admin.payment-gateways.move', $asan->id), ['direction' => 'up']);
        $this->actingAs($this->owner)->post(route('admin.payment-gateways.move', $asan->id), ['direction' => 'up']); // اولی‌ست؛ بی‌اثر
        $this->assertSame(['asanpardakht', 'zibal', 'vandar'], $this->salon->paymentGateways()->orderBy('priority')->pluck('driver')->all());

        $this->actingAs($this->owner)->delete(route('admin.payment-gateways.destroy', $asan->id));
        $this->assertSame([1, 2], $this->salon->paymentGateways()->orderBy('priority')->pluck('priority')->all());
    }

    public function test_another_salons_gateway_is_not_reachable(): void
    {
        $foreign = Salon::factory()->create()->paymentGateways()->create(['driver' => 'zibal', 'credentials' => ['merchant' => 'zibal'], 'priority' => 1]);

        $this->actingAs($this->owner)->put(route('admin.payment-gateways.update', $foreign->id), ['credentials' => ['merchant' => 'hijack']])->assertNotFound();
        $this->actingAs($this->owner)->delete(route('admin.payment-gateways.destroy', $foreign->id))->assertNotFound();
        $this->actingAs($this->owner)->post(route('admin.payment-gateways.test', $foreign->id))->assertNotFound();
        $this->assertSame('zibal', SalonPaymentGateway::find($foreign->id)->credentials['merchant']);
    }

    public function test_connection_test_reports_success_and_the_gateways_own_error(): void
    {
        $this->add('zibal', ['merchant' => 'zibal']);
        $gateway = $this->salon->paymentGateways()->sole();

        Http::fake(['gateway.zibal.ir/v1/request' => Http::sequence()
            ->push(['result' => 100, 'trackId' => 1])
            ->push(['result' => 103, 'message' => 'merchant inactive'])]);

        $this->actingAs($this->owner)->post(route('admin.payment-gateways.test', $gateway->id))->assertSessionHas('success');
        $this->actingAs($this->owner)->post(route('admin.payment-gateways.test', $gateway->id))
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'غیرفعال'));
    }
}
