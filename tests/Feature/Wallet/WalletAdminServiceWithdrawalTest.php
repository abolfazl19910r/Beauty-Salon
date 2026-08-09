<?php

namespace Tests\Feature\Wallet;

use App\Models\Specialist;
use App\Models\User;
use App\Models\WithdrawalRequest;
use App\Services\Admin\Wallet\WalletAdminService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletAdminServiceWithdrawalTest extends TestCase
{
    use RefreshDatabase;

    private WalletAdminService $service;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(WalletAdminService::class);
        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($this->admin);
    }

    private function makeWithdrawalRequest(float $amount = 100000): WithdrawalRequest
    {
        $specialist = Specialist::factory()->create();
        $wallet = $specialist->getOrCreateWallet();
        $wallet->update(['balance' => 500000, 'iban' => 'IR820540102680020817909002']);

        return WithdrawalRequest::create([
            'wallet_id' => $wallet->id,
            'specialist_id' => $specialist->id,
            'amount' => $amount,
            'fee' => 0,
            'net_amount' => $amount,
            'method' => 'iban',
            'iban' => $wallet->iban,
            'account_holder_name' => 'کاربر تست',
            'status' => 'pending',
        ]);
    }

    public function test_approving_a_pending_withdrawal_marks_it_completed(): void
    {
        $withdrawal = $this->makeWithdrawalRequest();

        $this->service->approveWithdrawal($withdrawal, ['payment_reference' => 'REF-123']);

        $this->assertSame('completed', $withdrawal->fresh()->status);
        $this->assertSame('REF-123', $withdrawal->fresh()->payment_details['payment_reference']);
    }

    public function test_approving_an_already_completed_withdrawal_is_a_no_op(): void
    {
        // Idempotency guard: a double-click/duplicate request must never re-process an already
        // finalized withdrawal (this is the exact bug class behind the documented duplicate
        // notification/SMS incident).
        $withdrawal = $this->makeWithdrawalRequest();
        $this->service->approveWithdrawal($withdrawal, ['payment_reference' => 'REF-FIRST']);

        $this->service->approveWithdrawal($withdrawal->fresh(), ['payment_reference' => 'REF-SECOND']);

        $this->assertSame('REF-FIRST', $withdrawal->fresh()->payment_details['payment_reference']);
    }

    public function test_rejecting_a_pending_withdrawal_refunds_the_wallet_and_marks_it_failed(): void
    {
        $withdrawal = $this->makeWithdrawalRequest(100000);
        $walletBalanceBefore = (float) $withdrawal->wallet->balance;

        $this->service->rejectWithdrawal($withdrawal, 'موجودی نامعتبر');

        $this->assertSame('failed', $withdrawal->fresh()->status);
        $this->assertSame($walletBalanceBefore + 100000, (float) $withdrawal->wallet->fresh()->balance);
    }

    public function test_rejecting_without_a_reason_uses_a_default_message(): void
    {
        $withdrawal = $this->makeWithdrawalRequest();

        $this->service->rejectWithdrawal($withdrawal, null);

        $this->assertNotNull($withdrawal->fresh()->rejection_reason);
    }

    public function test_rejecting_an_already_completed_withdrawal_does_not_double_refund(): void
    {
        $withdrawal = $this->makeWithdrawalRequest(100000);
        $this->service->approveWithdrawal($withdrawal, ['payment_reference' => 'REF-1']);
        $balanceAfterApproval = (float) $withdrawal->wallet->fresh()->balance;

        // Attempting to reject an already-completed withdrawal must be a no-op (guarded status
        // check), not silently refund money for a withdrawal that was already paid out.
        $this->service->rejectWithdrawal($withdrawal->fresh(), 'تلاش دیرهنگام');

        $this->assertSame('completed', $withdrawal->fresh()->status);
        $this->assertSame($balanceAfterApproval, (float) $withdrawal->wallet->fresh()->balance);
    }
}
