<?php

namespace Tests\Feature\Admin;

use App\Jobs\ProcessWithdrawalJob;
use App\Models\SpecialistWallet;
use App\Models\User;
use App\Models\WithdrawalRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * AdminWithdrawalController itself was only ever exercised indirectly through
 * WalletAdminService(Settlement|Withdrawal)Test — this fills the routes/middleware/
 * Form-Request gap for the withdrawal review flow (approve/reject/auto-payout).
 */
class AdminWithdrawalControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    public function test_index_lists_withdrawals_with_stats(): void
    {
        WithdrawalRequest::factory()->create(['status' => 'pending', 'amount' => 100000]);
        WithdrawalRequest::factory()->create(['status' => 'completed']);

        $response = $this->actingAs($this->admin)->get('/admin/wallet/withdrawals');

        $response->assertOk();
        $response->assertViewHas('pendingCount', 1);
        $response->assertViewHas('pendingAmount', 100000.0);
    }

    public function test_index_filters_by_status(): void
    {
        WithdrawalRequest::factory()->create(['status' => 'pending']);
        WithdrawalRequest::factory()->create(['status' => 'failed']);

        $response = $this->actingAs($this->admin)->get('/admin/wallet/withdrawals?status=failed');

        $this->assertCount(1, $response->viewData('withdrawals'));
    }

    public function test_show_renders_the_withdrawal_with_relations(): void
    {
        $withdrawal = WithdrawalRequest::factory()->create();

        $response = $this->actingAs($this->admin)->get("/admin/wallet/withdrawals/{$withdrawal->id}");

        $response->assertOk();
        $response->assertViewHas('withdrawalRequest');
    }

    public function test_approve_requires_a_payment_reference(): void
    {
        $withdrawal = WithdrawalRequest::factory()->create(['status' => 'pending']);

        $response = $this->actingAs($this->admin)
            ->put("/admin/wallet/withdrawals/{$withdrawal->id}/approve", []);

        $response->assertSessionHasErrors('payment_reference');
        $this->assertSame('pending', $withdrawal->fresh()->status);
    }

    public function test_approve_marks_the_withdrawal_completed(): void
    {
        $withdrawal = WithdrawalRequest::factory()->create(['status' => 'pending']);

        $response = $this->actingAs($this->admin)
            ->put("/admin/wallet/withdrawals/{$withdrawal->id}/approve", [
                'payment_reference' => 'REF-123456',
            ]);

        $response->assertRedirect(route('admin.wallet.withdrawals'));
        $response->assertSessionHas('success');
        $withdrawal->refresh();
        $this->assertSame('completed', $withdrawal->status);
        $this->assertSame('REF-123456', $withdrawal->payment_details['payment_reference']);
    }

    public function test_approve_refuses_an_already_completed_withdrawal(): void
    {
        $withdrawal = WithdrawalRequest::factory()->create(['status' => 'completed']);

        $response = $this->actingAs($this->admin)
            ->put("/admin/wallet/withdrawals/{$withdrawal->id}/approve", [
                'payment_reference' => 'REF-999',
            ]);

        $response->assertSessionHas('error');
    }

    public function test_reject_refunds_the_wallet_and_marks_failed(): void
    {
        $wallet = SpecialistWallet::factory()->create(['balance' => 0]);
        $withdrawal = WithdrawalRequest::factory()->create([
            'wallet_id' => $wallet->id,
            'specialist_id' => $wallet->specialist_id,
            'status' => 'pending',
            'amount' => 50000,
        ]);

        $response = $this->actingAs($this->admin)
            ->put("/admin/wallet/withdrawals/{$withdrawal->id}/reject", [
                'rejection_reason' => 'مدارک ناقص',
            ]);

        $response->assertRedirect(route('admin.wallet.withdrawals'));
        $withdrawal->refresh();
        $this->assertSame('failed', $withdrawal->status);
        $this->assertSame('مدارک ناقص', $withdrawal->rejection_reason);
        $this->assertSame(50000.0, (float) $wallet->fresh()->balance);
    }

    public function test_reject_without_a_reason_uses_a_default_message(): void
    {
        $withdrawal = WithdrawalRequest::factory()->create(['status' => 'pending']);

        $this->actingAs($this->admin)
            ->put("/admin/wallet/withdrawals/{$withdrawal->id}/reject", []);

        $this->assertNotNull($withdrawal->fresh()->rejection_reason);
    }

    public function test_auto_payout_dispatches_the_payout_job_and_sets_processing(): void
    {
        Queue::fake();
        $withdrawal = WithdrawalRequest::factory()->create(['status' => 'pending']);

        $response = $this->actingAs($this->admin)
            ->post("/admin/wallet/withdrawals/{$withdrawal->id}/auto-payout");

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertSame('processing', $withdrawal->fresh()->status);
        Queue::assertPushed(ProcessWithdrawalJob::class);
    }

    public function test_auto_payout_refuses_an_already_processed_withdrawal(): void
    {
        Queue::fake();
        $withdrawal = WithdrawalRequest::factory()->create(['status' => 'completed']);

        $response = $this->actingAs($this->admin)
            ->post("/admin/wallet/withdrawals/{$withdrawal->id}/auto-payout");

        $response->assertSessionHas('error');
        Queue::assertNotPushed(ProcessWithdrawalJob::class);
    }

    public function test_non_admin_is_forbidden(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $withdrawal = WithdrawalRequest::factory()->create();

        $this->actingAs($user)->get('/admin/wallet/withdrawals')->assertForbidden();
        $this->actingAs($user)->get("/admin/wallet/withdrawals/{$withdrawal->id}")->assertForbidden();
    }
}
