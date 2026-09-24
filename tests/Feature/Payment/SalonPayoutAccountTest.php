<?php

namespace Tests\Feature\Payment;

use App\Models\Salon;
use App\Models\Specialist;
use App\Models\User;
use App\Models\WithdrawalRequest;
use App\Services\Payment\SalonPayoutService;
use App\Support\CurrentSalon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * ⭐ تسویه‌ی خودکار کیف پول متخصص از حساب زرین‌پالِ خودِ سالن (۲۰۲۶-۰۹-۲۴، تصمیم ابوالفضل)؛
 * تسویه‌ی دستی مدیر سالن همچنان همیشه در دسترسه.
 */
class SalonPayoutAccountTest extends TestCase
{
    use RefreshDatabase;

    private Salon $salon;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->salon = app(CurrentSalon::class)->get();
        $this->owner = User::factory()->create(['is_admin' => true]); // اولین ادمین = owner
    }

    public function test_payout_uses_the_salons_own_merchant_and_token_never_the_platforms(): void
    {
        config(['services.zarinpal.merchant_id' => 'platform-merchant', 'services.zarinpal.payout.api_key' => 'platform-token']);
        $this->salon->update(['zarinpal_merchant_id' => 'aaaaaaaa-1111-2222-3333-bbbbbbbbbbbb', 'zarinpal_payout_api_key' => 'salon-token-0123456789-abcdef']);
        $withdrawal = WithdrawalRequest::factory()->create(['status' => 'processing', 'specialist_id' => Specialist::factory()->create()->id]);

        Http::fake(['*payout.json' => Http::response(['data' => ['code' => 100, 'payout_id' => 'P-1']], 200)]);

        $result = app(SalonPayoutService::class)->payout($withdrawal);

        $this->assertTrue($result['success']);
        Http::assertSent(fn ($request) => $request['merchant_id'] === 'aaaaaaaa-1111-2222-3333-bbbbbbbbbbbb'
            && $request->header('Authorization')[0] === 'Bearer salon-token-0123456789-abcdef');
    }

    public function test_payout_refuses_when_the_salon_has_no_token(): void
    {
        config(['services.zarinpal.payout.api_key' => 'platform-token']);
        $this->salon->update(['zarinpal_payout_api_key' => null]);
        $withdrawal = WithdrawalRequest::factory()->create(['status' => 'processing', 'specialist_id' => Specialist::factory()->create()->id]);
        Http::fake();

        $result = app(SalonPayoutService::class)->payout($withdrawal);

        $this->assertFalse($result['success']);
        Http::assertNothingSent();
    }

    public function test_auto_payout_button_is_blocked_before_queueing_when_not_configured(): void
    {
        Queue::fake();
        $this->salon->update(['zarinpal_payout_api_key' => null]);
        $withdrawal = WithdrawalRequest::factory()->create(['status' => 'pending']);

        $this->actingAs($this->owner)->post("/admin/wallet/withdrawals/{$withdrawal->id}/auto-payout")
            ->assertSessionHas('error');

        Queue::assertNothingPushed();
        $this->assertSame('pending', $withdrawal->fresh()->status);

        $this->actingAs($this->owner)->get("/admin/wallet/withdrawals/{$withdrawal->id}")
            ->assertOk()->assertSee('تسویه‌ی خودکار برای این سالن فعال نیست');
    }

    /**
     * ⭐ مرحله‌ی ۳ (۲۰۲۶-۰۹-۲۵): توکن Payout از «اطلاعات سالن» به ویرایش درگاه زرین‌پال در «درگاه‌های پرداخت» رفت.
     */
    public function test_owner_sets_keeps_and_removes_the_payout_token_on_the_zarinpal_gateway(): void
    {
        $gateway = $this->salon->zarinpalGateway();
        $update = fn (array $extra) => $this->actingAs($this->owner)->put(route('admin.payment-gateways.update', $gateway->id), $extra + [
            'credentials' => ['merchant_id' => $gateway->credentials['merchant_id']], 'is_active' => '1',
        ]);

        $this->actingAs($this->owner)->get(route('admin.payment-gateways.index'))->assertOk()->assertSee('توکن Payout');
        $update(['credentials' => ['merchant_id' => $gateway->credentials['merchant_id'], 'payout_api_key' => 'salon-token-0123456789-abcdef']])->assertSessionHasNoErrors();

        $this->assertSame('salon-token-0123456789-abcdef', $this->salon->fresh()->zarinpal_payout_api_key);
        $this->assertTrue($this->salon->fresh()->canAutoPayout());
        $raw = \Illuminate\Support\Facades\DB::table('salon_payment_gateways')->where('id', $gateway->id)->value('credentials');
        $this->assertStringNotContainsString('salon-token', $raw, 'باید رمزشده ذخیره بشه');
        $this->assertArrayNotHasKey('zarinpal_payout_api_key', $this->salon->fresh()->toArray());

        $update([])->assertSessionHasNoErrors(); // خالی = بدون تغییر
        $this->assertTrue($this->salon->fresh()->canAutoPayout());
        $this->actingAs($this->owner)->get(route('admin.payment-gateways.index'))
            ->assertDontSee('salon-token-0123456789-abcdef')->assertSee('تسویه‌ی خودکار متخصص‌ها:')->assertSee('name="clear[payout_api_key]"', false);

        $update(['clear' => ['payout_api_key' => '1']])->assertSessionHasNoErrors();
        $this->assertFalse($this->salon->fresh()->canAutoPayout());
        $this->assertSame($gateway->credentials['merchant_id'], $this->salon->fresh()->zarinpal_merchant_id, 'کد پذیرنده دست نمی‌خوره');

        $this->actingAs($this->owner)->get(route('admin.salon-settings.edit'))->assertOk()
            ->assertDontSee('name="zarinpal_payout_api_key"', false)->assertSee('درگاه‌های پرداخت');
    }

    public function test_super_admin_edit_does_not_reactivate_a_zarinpal_gateway_the_owner_turned_off(): void
    {
        $gateway = $this->salon->zarinpalGateway();
        $gateway->update(['is_active' => false]);

        $this->salon->fresh()->update(['name' => 'نام جدید', 'zarinpal_merchant_id' => $gateway->credentials['merchant_id']]);
        $this->assertFalse($gateway->fresh()->is_active);

        $this->salon->fresh()->update(['zarinpal_merchant_id' => 'bbbbbbbb-1111-2222-3333-cccccccccccc']);
        $this->assertTrue($gateway->fresh()->is_active, 'کد پذیرنده‌ی جدید = درگاه دوباره فعال (همون رفتار قبلی)');
    }

    public function test_migration_moves_existing_merchant_and_payout_token_into_the_gateway_row_and_drops_the_columns(): void
    {
        $migration = require database_path('migrations/2026_09_25_000100_move_zarinpal_salon_columns_into_payment_gateways.php');
        $migration->down();
        \App\Models\SalonPaymentGateway::query()->delete();

        $withBoth = Salon::factory()->create();
        $onlyToken = Salon::factory()->create();
        \App\Models\SalonPaymentGateway::query()->delete();
        \Illuminate\Support\Facades\DB::table('salons')->where('id', $withBoth->id)->update([
            'zarinpal_merchant_id' => 'aaaaaaaa-1111-2222-3333-bbbbbbbbbbbb',
            'zarinpal_payout_api_key' => \Illuminate\Support\Facades\Crypt::encryptString('old-column-token'),
        ]);
        \Illuminate\Support\Facades\DB::table('salons')->where('id', $onlyToken->id)->update([
            'zarinpal_merchant_id' => null,
            'zarinpal_payout_api_key' => \Illuminate\Support\Facades\Crypt::encryptString('orphan-token'),
        ]);

        $migration->up();

        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('salons', 'zarinpal_merchant_id'));
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('salons', 'zarinpal_payout_api_key'));
        $row = $withBoth->fresh()->zarinpalGateway();
        $this->assertTrue($row->is_active);
        $this->assertSame(['merchant_id' => 'aaaaaaaa-1111-2222-3333-bbbbbbbbbbbb', 'payout_api_key' => 'old-column-token'], $row->credentials);
        $this->assertTrue($withBoth->fresh()->canAutoPayout());
        $this->assertNull($onlyToken->fresh()->zarinpalGateway(), 'توکن بدون کد پذیرنده منتقل نمی‌شه');
    }

    public function test_manual_settlement_still_works_without_any_payout_configuration(): void
    {
        $this->salon->update(['zarinpal_payout_api_key' => null]);
        $withdrawal = WithdrawalRequest::factory()->create(['status' => 'pending']);

        $this->actingAs($this->owner)->put("/admin/wallet/withdrawals/{$withdrawal->id}/approve", [
            'payment_reference' => 'MANUAL-123',
        ])->assertSessionHas('success');

        $this->assertSame('completed', $withdrawal->fresh()->status);
    }
}
