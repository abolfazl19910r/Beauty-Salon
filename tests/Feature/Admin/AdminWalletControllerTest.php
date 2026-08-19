<?php

namespace Tests\Feature\Admin;

use App\Models\Specialist;
use App\Models\SpecialistWallet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * AdminWalletController itself was only ever exercised indirectly through
 * WalletAdminService(Settlement|Withdrawal)Test — those cover the service layer, not the
 * routes/middleware/Form-Request layer. This fills that gap for the wallet-list/detail/
 * verify-iban/adjust/manual-settlement endpoints.
 */
class AdminWalletControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    public function test_index_lists_wallets_with_totals(): void
    {
        SpecialistWallet::factory()->create(['balance' => 10000, 'total_earned' => 20000]);
        SpecialistWallet::factory()->create(['balance' => 5000, 'total_earned' => 8000]);

        $response = $this->actingAs($this->admin)->get('/admin/wallet');

        $response->assertOk();
        $response->assertViewHas('totalBalance', 15000.0);
        $response->assertViewHas('totalEarned', 28000.0);
    }

    public function test_index_search_filters_by_specialist_name(): void
    {
        $specialist = Specialist::factory()->create(['name' => 'سارا محمدی']);
        SpecialistWallet::factory()->create(['specialist_id' => $specialist->id]);
        SpecialistWallet::factory()->create();

        $response = $this->actingAs($this->admin)->get('/admin/wallet?search='.urlencode('سارا'));

        $wallets = $response->viewData('wallets');
        $this->assertCount(1, $wallets);
    }

    public function test_show_renders_wallet_detail_with_transactions(): void
    {
        $wallet = SpecialistWallet::factory()->create();

        $response = $this->actingAs($this->admin)->get("/admin/wallet/{$wallet->id}");

        $response->assertOk();
        $response->assertViewHas('wallet');
        $response->assertViewHas('recentTransactions');
    }

    public function test_verify_iban_marks_wallet_as_verified(): void
    {
        $wallet = SpecialistWallet::factory()->create(['iban_verified' => false]);

        $response = $this->actingAs($this->admin)->post("/admin/wallet/{$wallet->id}/verify-iban");

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertTrue($wallet->fresh()->iban_verified);
    }

    public function test_adjust_increases_balance_and_records_a_transaction(): void
    {
        $wallet = SpecialistWallet::factory()->create(['balance' => 10000]);

        $response = $this->actingAs($this->admin)->post("/admin/wallet/{$wallet->id}/adjust", [
            'amount' => 5000,
            'description' => 'اصلاح دستی',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertSame(15000.0, (float) $wallet->fresh()->balance);
        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $wallet->id,
            'type' => 'adjustment',
            'amount' => 5000,
        ]);
    }

    public function test_adjust_can_decrease_balance_with_a_negative_amount(): void
    {
        $wallet = SpecialistWallet::factory()->create(['balance' => 10000]);

        $this->actingAs($this->admin)->post("/admin/wallet/{$wallet->id}/adjust", [
            'amount' => -4000,
            'description' => 'کسر دستی',
        ]);

        $this->assertSame(6000.0, (float) $wallet->fresh()->balance);
    }

    public function test_adjust_requires_a_description(): void
    {
        $wallet = SpecialistWallet::factory()->create();

        $response = $this->actingAs($this->admin)->post("/admin/wallet/{$wallet->id}/adjust", [
            'amount' => 1000,
        ]);

        $response->assertSessionHasErrors('description');
    }

    public function test_settle_pending_settles_due_transactions_across_all_wallets(): void
    {
        $wallet = SpecialistWallet::factory()->create(['pending_amount' => 20000, 'balance' => 0]);
        $wallet->transactions()->create([
            'type' => 'income',
            'amount' => 20000,
            'balance_after' => 0,
            'description' => 'test',
            'metadata' => ['status' => 'pending', 'settlement_date' => now()->subDay()->toDateString()],
        ]);

        $response = $this->actingAs($this->admin)->post('/admin/wallet/settle-pending');

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertSame(20000.0, (float) $wallet->fresh()->balance);
        $this->assertSame(0.0, (float) $wallet->fresh()->pending_amount);
    }

    public function test_settle_pending_for_a_single_wallet_only_touches_that_wallet(): void
    {
        $walletA = SpecialistWallet::factory()->create(['pending_amount' => 10000, 'balance' => 0]);
        $walletA->transactions()->create([
            'type' => 'income',
            'amount' => 10000,
            'balance_after' => 0,
            'description' => 'a',
            'metadata' => ['status' => 'pending', 'settlement_date' => now()->subDay()->toDateString()],
        ]);
        $walletB = SpecialistWallet::factory()->create(['pending_amount' => 10000, 'balance' => 0]);
        $walletB->transactions()->create([
            'type' => 'income',
            'amount' => 10000,
            'balance_after' => 0,
            'description' => 'b',
            'metadata' => ['status' => 'pending', 'settlement_date' => now()->subDay()->toDateString()],
        ]);

        $this->actingAs($this->admin)->post("/admin/wallet/{$walletA->id}/settle-pending");

        $this->assertSame(10000.0, (float) $walletA->fresh()->balance);
        $this->assertSame(0.0, (float) $walletB->fresh()->balance);
    }

    public function test_non_admin_is_forbidden(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->get('/admin/wallet')->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/wallet')->assertRedirect(route('login'));
    }

    // ── AdminWalletSettingsController ───────────────────────────────────────

    public function test_settings_index_renders_current_settings(): void
    {
        \App\Models\WalletSetting::get();

        $response = $this->actingAs($this->admin)->get('/admin/wallet/settings');

        $response->assertOk();
        $response->assertViewHas('settings');
    }

    public function test_settings_update_persists_new_values(): void
    {
        \App\Models\WalletSetting::get();

        $response = $this->actingAs($this->admin)->put('/admin/wallet/settings', [
            'withdrawal_fee_percentage' => 3,
            'minimum_withdrawal_amount' => 50000,
            'maximum_withdrawal_amount' => 10000000,
            'instant_withdrawal_enabled' => 0,
            'instant_withdrawal_fee' => 5000,
            'cancellation_before_hours' => 24,
            'customer_cancellation_fee_percentage' => 20,
            'specialist_cancellation_penalty_percentage' => 10,
            'specialist_cancellation_before_hours' => 24,
            'specialist_repeat_cancellation_threshold' => 0,
            'specialist_repeat_cancellation_window_days' => 30,
            'specialist_repeat_cancellation_extra_percentage' => 0,
            'settlement_delay_days' => 3,
            'admin_commission_percentage' => 15,
            'prepayment_percentage' => 40,
            'minimum_prepayment_amount' => 60000,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertSame(40.0, (float) \App\Models\WalletSetting::get()->fresh()->prepayment_percentage);
        $this->assertSame(15.0, (float) \App\Models\WalletSetting::get()->fresh()->admin_commission_percentage);
    }

    public function test_settings_update_rejects_a_percentage_above_100(): void
    {
        \App\Models\WalletSetting::get();

        $response = $this->actingAs($this->admin)->put('/admin/wallet/settings', [
            'withdrawal_fee_percentage' => 150,
            'minimum_withdrawal_amount' => 50000,
            'maximum_withdrawal_amount' => 10000000,
            'cancellation_before_hours' => 24,
            'customer_cancellation_fee_percentage' => 20,
            'specialist_cancellation_penalty_percentage' => 10,
            'specialist_cancellation_before_hours' => 24,
            'specialist_repeat_cancellation_threshold' => 0,
            'specialist_repeat_cancellation_window_days' => 30,
            'specialist_repeat_cancellation_extra_percentage' => 0,
            'settlement_delay_days' => 3,
            'admin_commission_percentage' => 15,
            'prepayment_percentage' => 40,
            'minimum_prepayment_amount' => 60000,
        ]);

        $response->assertSessionHasErrors('withdrawal_fee_percentage');
    }
}
