<?php

namespace Tests\Feature\Jobs;

use App\Events\Withdrawal\Approved\WithdrawalApproved;
use App\Events\Withdrawal\Rejected\WithdrawalRejected;
use App\Jobs\ProcessWithdrawalJob;
use App\Models\Specialist;
use App\Models\WithdrawalRequest;
use App\Services\Payment\ZarinpalPayoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\TestCase;

class ProcessWithdrawalJobTest extends TestCase
{
    use RefreshDatabase;

    private function makeProcessingWithdrawal(float $amount = 100000): WithdrawalRequest
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
            'status' => 'processing',
        ]);
    }

    public function test_a_successful_payout_marks_the_withdrawal_completed_and_dispatches_approved(): void
    {
        Event::fake([WithdrawalApproved::class]);

        $withdrawal = $this->makeProcessingWithdrawal();

        $this->mock(ZarinpalPayoutService::class, function ($mock) {
            $mock->shouldReceive('payout')->once()->andReturn([
                'success' => true,
                'reference_code' => 'ZP-REAL-123',
                'payout_id' => 'PAYOUT-1',
            ]);
        });

        (new ProcessWithdrawalJob($withdrawal->id))->handle(app(ZarinpalPayoutService::class));

        $withdrawal->refresh();
        $this->assertSame('completed', $withdrawal->status);
        $this->assertSame('ZP-REAL-123', $withdrawal->reference_code);
        $this->assertNotNull($withdrawal->processed_at);

        Event::assertDispatched(WithdrawalApproved::class, fn ($event) => $event->withdrawalRequest->is($withdrawal));
    }

    public function test_a_failed_payout_marks_the_withdrawal_failed_and_dispatches_rejected(): void
    {
        Event::fake([WithdrawalRejected::class]);

        $withdrawal = $this->makeProcessingWithdrawal();

        $this->mock(ZarinpalPayoutService::class, function ($mock) {
            $mock->shouldReceive('payout')->once()->andReturn([
                'success' => false,
                'message' => 'موجودی کافی نیست',
            ]);
        });

        (new ProcessWithdrawalJob($withdrawal->id))->handle(app(ZarinpalPayoutService::class));

        $withdrawal->refresh();
        $this->assertSame('failed', $withdrawal->status);
        $this->assertSame('موجودی کافی نیست', $withdrawal->rejection_reason);

        Event::assertDispatched(WithdrawalRejected::class, function ($event) use ($withdrawal) {
            return $event->withdrawalRequest->is($withdrawal) && $event->reason === 'موجودی کافی نیست';
        });
    }

    public function test_a_withdrawal_no_longer_in_processing_status_is_skipped(): void
    {
        // Simulates the race where a manual admin approve/reject already finalized this
        // request between dispatch and the job actually running.
        // Note: Event::fake() is called AFTER creating the model — faking all events
        // beforehand would also silently swallow WithdrawalRequest::boot()'s own
        // `creating` hook (which auto-generates reference_code), a classic Eloquent-model
        // testing pitfall unrelated to the job logic under test here.
        $withdrawal = $this->makeProcessingWithdrawal();
        $withdrawal->update(['status' => 'completed', 'reference_code' => 'MANUAL-REF']);

        Event::fake();

        $payoutMock = Mockery::mock(ZarinpalPayoutService::class);
        $payoutMock->shouldNotReceive('payout');
        $this->app->instance(ZarinpalPayoutService::class, $payoutMock);

        (new ProcessWithdrawalJob($withdrawal->id))->handle(app(ZarinpalPayoutService::class));

        $withdrawal->refresh();
        $this->assertSame('completed', $withdrawal->status);
        $this->assertSame('MANUAL-REF', $withdrawal->reference_code);

        Event::assertNotDispatched(WithdrawalApproved::class);
        Event::assertNotDispatched(WithdrawalRejected::class);
    }

    public function test_a_missing_withdrawal_record_is_handled_gracefully(): void
    {
        Event::fake();

        $payoutMock = Mockery::mock(ZarinpalPayoutService::class);
        $payoutMock->shouldNotReceive('payout');
        $this->app->instance(ZarinpalPayoutService::class, $payoutMock);

        (new ProcessWithdrawalJob(999999))->handle(app(ZarinpalPayoutService::class));

        Event::assertNotDispatched(WithdrawalApproved::class);
        Event::assertNotDispatched(WithdrawalRejected::class);
    }

    public function test_failed_hook_returns_a_stuck_processing_withdrawal_to_pending(): void
    {
        $withdrawal = $this->makeProcessingWithdrawal();

        $job = new ProcessWithdrawalJob($withdrawal->id);
        $job->failed(new \Exception('worker crashed'));

        $withdrawal->refresh();
        $this->assertSame('pending', $withdrawal->status);
    }

    public function test_failed_hook_does_not_touch_a_withdrawal_no_longer_processing(): void
    {
        $withdrawal = $this->makeProcessingWithdrawal();
        $withdrawal->update(['status' => 'completed']);

        $job = new ProcessWithdrawalJob($withdrawal->id);
        $job->failed(new \Exception('worker crashed'));

        $withdrawal->refresh();
        $this->assertSame('completed', $withdrawal->status);
    }
}
