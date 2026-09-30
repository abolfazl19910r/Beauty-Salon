<?php

namespace Tests\Feature\Wallet;

use App\Models\SpecialistWallet;
use App\Models\User;
use App\Models\WithdrawalRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * تصمیم ۲۰۲۶-۰۹-۳۰: تسویه‌ی دستی برای شبای تأییدنشده مجاز است، ولی با هشدار و با ثبت در سابقه‌ی درخواست.
 */
class ManualSettlementUnverifiedIbanTest extends TestCase
{
    use RefreshDatabase;

    private const IBAN = 'IR820540102680020817909002';

    private function withdrawal(bool $verified): WithdrawalRequest
    {
        $wallet = SpecialistWallet::factory()->create(['iban' => self::IBAN, 'iban_verified' => $verified]);

        return WithdrawalRequest::factory()->create([
            'wallet_id' => $wallet->id,
            'specialist_id' => $wallet->specialist_id,
            'iban' => self::IBAN,
            'status' => 'pending',
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_the_page_warns_before_a_manual_settlement_to_an_unverified_iban(): void
    {
        $withdrawal = $this->withdrawal(verified: false);

        $this->actingAs($this->admin())->get("/admin/wallet/withdrawals/{$withdrawal->id}")
            ->assertOk()
            ->assertSee('هشدار: شبای این درخواست تأیید نشده است')
            ->assertSee(route('admin.wallet.withdrawals.approve', $withdrawal), false);
    }

    public function test_no_warning_for_a_verified_iban(): void
    {
        $withdrawal = $this->withdrawal(verified: true);

        $this->actingAs($this->admin())->get("/admin/wallet/withdrawals/{$withdrawal->id}")
            ->assertOk()
            ->assertDontSee('هشدار: شبای این درخواست تأیید نشده است');
    }

    public function test_a_manual_settlement_to_an_unverified_iban_is_allowed_and_recorded(): void
    {
        $withdrawal = $this->withdrawal(verified: false);

        $this->actingAs($this->admin())->put("/admin/wallet/withdrawals/{$withdrawal->id}/approve", [
            'payment_reference' => '123456789012',
        ])->assertSessionHas('success');

        $fresh = $withdrawal->fresh();
        $this->assertSame('completed', $fresh->status);
        $this->assertFalse($fresh->payment_details['iban_verified']);
        $this->assertNotEmpty($fresh->payment_details['iban_warning']);
    }

    public function test_a_manual_settlement_to_a_verified_iban_records_it_as_verified(): void
    {
        $withdrawal = $this->withdrawal(verified: true);

        $this->actingAs($this->admin())->put("/admin/wallet/withdrawals/{$withdrawal->id}/approve", [
            'payment_reference' => '123456789012',
        ]);

        $fresh = $withdrawal->fresh();
        $this->assertTrue($fresh->payment_details['iban_verified']);
        $this->assertArrayNotHasKey('iban_warning', $fresh->payment_details);
    }
}
