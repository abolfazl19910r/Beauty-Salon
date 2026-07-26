<?php

namespace App\Jobs;

use App\Events\Withdrawal\Approved\WithdrawalApproved;
use App\Events\Withdrawal\Rejected\WithdrawalRejected;
use App\Models\WithdrawalRequest;
use App\Services\Payment\ZarinpalPayoutService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Async processing of online settlement (auto-payout) of a specialized withdrawal request.
 *
 * Previously this logic (WalletAdminService::autoPayout) was completely mocked and synchronous
 * (see "critical warning" at the top of Rasta_unified_prompt.md). Because the actual call to
 * the ZarrinPal Payout gateway is an external HTTP request (exactly the same class of issue that caused a 30+ second login timeout during the
 * Telescope standalone fix phase — see
 * "30+ second login slowness"), this call has been intentionally queued so that the admin's click on
 * the "Confirm Payment" button returns immediately, rather than waiting for a response from ZarrinPal.
 *
 * The 'processing' state was originally foreseen in the model/Blade of this project (blue color,
 * separate branch of @elseif in withdrawal-show.blade.php) but nowhere in the codebase did it actually set this
 * state — this Job actually uses this state for the first time:
 * Before dispatch, the state changes to 'processing'; after this Job runs
 * it goes to 'completed' (with a real tracking code) or 'failed' (with a real error message).
 */
class ProcessWithdrawalJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    // The call to Payout is case sensitive; automatic retry should not cause double deposits.
    // That's why the large backoff + row lock inside handle() prevents double processing.
    public int $backoff = 30;

    public int $timeout = 45;

    public function __construct(
        protected int $withdrawalRequestId,
    ) {
    }

    public function handle(ZarinpalPayoutService $payoutService): void
    {
        DB::transaction(function () use ($payoutService) {
            $withdrawalRequest = WithdrawalRequest::whereKey($this->withdrawalRequestId)
                ->lockForUpdate()
                ->first();

            if (!$withdrawalRequest) {
                Log::warning('ProcessWithdrawalJob: درخواست برداشت یافت نشد', [
                    'withdrawal_request_id' => $this->withdrawalRequestId,
                ]);
                return;
            }

            // If between dispatch and Job execution, the request has been finalized from another path (manual approval/rejection),
            // Do nothing here — prevents double processing or overriding of the previous result.
            if ($withdrawalRequest->status !== 'processing') {
                Log::info('ProcessWithdrawalJob: درخواست دیگر processing نیست، از پردازش صرف‌نظر شد', [
                    'withdrawal_request_id' => $withdrawalRequest->id,
                    'current_status' => $withdrawalRequest->status,
                ]);
                return;
            }

            $result = $payoutService->payout($withdrawalRequest);

            if ($result['success']) {
                $withdrawalRequest->update([
                    'reference_code' => $result['reference_code'],
                    'status' => 'completed',
                    'processed_at' => now(),
                    'payment_details' => [
                        'payment_method' => 'zarinpal_auto',
                        'payment_reference' => $result['reference_code'],
                        'payout_id' => $result['payout_id'] ?? null,
                    ],
                ]);

                event(new WithdrawalApproved($withdrawalRequest));

                return;
            }

            $withdrawalRequest->update([
                'status' => 'failed',
                'processed_at' => now(),
                'rejection_reason' => $result['message'] ?? 'خطای نامشخص در تسویه‌ی آنلاین',
            ]);

            Log::error('ProcessWithdrawalJob: تسویه‌ی آنلاین ناموفق بود', [
                'withdrawal_request_id' => $withdrawalRequest->id,
                'message' => $result['message'] ?? null,
            ]);

            /**
             * R-Observers: Previously this branch set the request to 'failed' and logged the error,
             * but never told the specialist — unlike the manual reject path in
             * WalletAdminService::rejectWithdrawal(), which always dispatches WithdrawalRejected.
             * The specialist would only discover the auto-payout had failed by refreshing the wallet
             * page themselves. Dispatching the same event here reuses the existing notification/SMS
             * listener (SendWithdrawalRejectedNotification) without any new class.
             */
            event(new WithdrawalRejected(
                $withdrawalRequest,
                $result['message'] ?? 'خطای نامشخص در تسویه‌ی آنلاین'
            ));
        });
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessWithdrawalJob: خود Job با خطا متوقف شد', [
            'withdrawal_request_id' => $this->withdrawalRequestId,
            'error' => $exception->getMessage(),
        ]);

        // If the Job itself (not just the Zarrinpal response) crashes, don't leave the request pending in 'processing' —
        // Return it to pending so the admin can take action again (manually or auto-payout again).
        $withdrawalRequest = WithdrawalRequest::find($this->withdrawalRequestId);
        if ($withdrawalRequest && $withdrawalRequest->status === 'processing') {
            $withdrawalRequest->update(['status' => 'pending']);
        }
    }
}
