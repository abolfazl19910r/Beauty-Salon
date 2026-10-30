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
 * ⭐ تسویه‌ی خودکار کیف پول متخصص فقط از حساب درگاه خودِ سالن (۲۰۲۶-۰۹-۲۴، تصمیم ابوالفضل)؛ تسویه‌ی دستی مدیر سالن همیشه
 * در دسترسه. از بسته‌ی 5ز زرین‌پال تسویه‌ی خودکار ندارد (ZarinpalPayoutDisabledTest).
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

    public function test_payout_refuses_when_the_salon_has_no_token(): void
    {
        $withdrawal = WithdrawalRequest::factory()->create(['status' => 'processing', 'specialist_id' => Specialist::factory()->create()->id]);
        Http::fake();

        $result = app(SalonPayoutService::class)->payout($withdrawal);

        $this->assertFalse($result['success']);
        Http::assertNothingSent();
    }

    public function test_auto_payout_button_is_blocked_before_queueing_when_not_configured(): void
    {
        Queue::fake();
        $withdrawal = WithdrawalRequest::factory()->create(['status' => 'pending']);

        $this->actingAs($this->owner)->post("/admin/wallet/withdrawals/{$withdrawal->id}/auto-payout")
            ->assertSessionHas('error');

        Queue::assertNothingPushed();
        $this->assertSame('pending', $withdrawal->fresh()->status);

        $this->actingAs($this->owner)->get("/admin/wallet/withdrawals/{$withdrawal->id}")
            ->assertOk()->assertSee(\App\Payments\GatewayCatalog::NO_PAYOUT_NOTE);
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

    public function test_zarinpal_credentials_live_only_on_the_gateway_row_not_on_salons(): void
    {
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('salons', 'zarinpal_merchant_id'));
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('salons', 'zarinpal_payout_api_key'));
        $this->assertArrayNotHasKey('zarinpal_payout_api_key', $this->salon->fresh()->toArray());
    }

    public function test_manual_settlement_still_works_without_any_payout_configuration(): void
    {
        $withdrawal = WithdrawalRequest::factory()->create(['status' => 'pending']);

        $this->actingAs($this->owner)->put("/admin/wallet/withdrawals/{$withdrawal->id}/approve", [
            'payment_reference' => 'MANUAL-123',
        ])->assertSessionHas('success');

        $this->assertSame('completed', $withdrawal->fresh()->status);
    }
}
