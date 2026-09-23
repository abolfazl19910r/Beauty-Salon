<?php

namespace Tests\Feature\Payment;

use App\Models\Salon;
use App\Models\Specialist;
use App\Models\User;
use App\Models\WithdrawalRequest;
use App\Services\Payment\ZarinpalPayoutService;
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

        $result = app(ZarinpalPayoutService::class)->payout($withdrawal);

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

        $result = app(ZarinpalPayoutService::class)->payout($withdrawal);

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

    public function test_owner_sets_keeps_and_removes_the_payout_token_which_is_stored_encrypted(): void
    {
        $this->actingAs($this->owner)->put(route('admin.salon-settings.update'), [
            'name' => $this->salon->name, 'zarinpal_payout_api_key' => 'salon-token-0123456789-abcdef',
        ])->assertSessionHasNoErrors();

        $this->assertSame('salon-token-0123456789-abcdef', $this->salon->fresh()->zarinpal_payout_api_key);
        $raw = \Illuminate\Support\Facades\DB::table('salons')->where('id', $this->salon->id)->value('zarinpal_payout_api_key');
        $this->assertNotSame('salon-token-0123456789-abcdef', $raw, 'باید رمزشده ذخیره بشه');
        $this->assertArrayNotHasKey('zarinpal_payout_api_key', $this->salon->fresh()->toArray());

        // فیلد خالی = بدون تغییر
        $this->actingAs($this->owner)->put(route('admin.salon-settings.update'), ['name' => $this->salon->name, 'zarinpal_payout_api_key' => '']);
        $this->assertTrue($this->salon->fresh()->canAutoPayout());

        $this->actingAs($this->owner)->get(route('admin.salon-settings.edit'))->assertDontSee('salon-token-0123456789-abcdef');

        $this->actingAs($this->owner)->put(route('admin.salon-settings.update'), ['name' => $this->salon->name, 'remove_zarinpal_payout_api_key' => '1']);
        $this->assertFalse($this->salon->fresh()->canAutoPayout());
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
