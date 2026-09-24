<?php

namespace App\Services\Admin\Wallet;

use App\Events\Withdrawal\Approved\WithdrawalApproved;
use App\Events\Withdrawal\Rejected\WithdrawalRejected;
use App\Jobs\ProcessWithdrawalJob;
use App\Models\SpecialistWallet;
use App\Models\WalletSetting;
use App\Models\WithdrawalRequest;
use App\Repositories\Contracts\SpecialistWalletRepositoryInterface;
use App\Repositories\Contracts\WalletSettingRepositoryInterface;
use App\Repositories\Contracts\WalletTransactionRepositoryInterface;
use App\Repositories\Contracts\WithdrawalRequestRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WalletAdminService
{
    public function __construct(
        protected readonly SpecialistWalletRepositoryInterface $specialistWalletRepository,
        protected readonly WithdrawalRequestRepositoryInterface $withdrawalRequestRepository,
        protected readonly WalletTransactionRepositoryInterface $walletTransactionRepository,
        protected readonly WalletSettingRepositoryInterface $walletSettingRepository,
    ) {}

    public function getWalletsList(array $filters): LengthAwarePaginator
    {
        return $this->specialistWalletRepository->paginateWithFilters($filters, 20);
    }

    public function getWalletTotals(): array
    {
        return $this->specialistWalletRepository->getTotals();
    }

    public function getWalletDetail(SpecialistWallet $wallet): array
    {
        $wallet->load('specialist', 'transactions', 'withdrawalRequests');

        $recentTransactions = $this->walletTransactionRepository->paginateForWalletWithFilters($wallet->id, [], 20);

        return [
            'wallet' => $wallet,
            'recentTransactions' => $recentTransactions,
        ];
    }

    public function getWithdrawalsList(array $filters): LengthAwarePaginator
    {
        return $this->withdrawalRequestRepository->paginateWithFilters($filters, 20);
    }

    public function getWithdrawalStats(): array
    {
        return $this->withdrawalRequestRepository->getStats();
    }

    public function verifyIban(SpecialistWallet $wallet): void
    {
        $this->specialistWalletRepository->update($wallet, ['iban_verified' => true]);
    }

    public function adjustWallet(SpecialistWallet $wallet, float $amount, string $description): void
    {
        DB::transaction(function () use ($wallet, $amount, $description) {
            if ($amount > 0) {
                $wallet->increment('balance', $amount);
            } else {
                $wallet->decrement('balance', abs($amount));
            }

            $wallet->transactions()->create([
                'type' => 'adjustment',
                'amount' => $amount,
                'balance_after' => $wallet->balance,
                'description' => 'تعدیل دستی توسط ادمین: '.$description,
                'metadata' => [
                    'admin_id' => auth()->id(),
                    'admin_name' => auth()->user()->name,
                ],
            ]);
        });
    }

    public function approveWithdrawal(WithdrawalRequest $withdrawalRequest, array $data): void
    {
        $shouldFireEvent = false;

        DB::transaction(function () use ($withdrawalRequest, $data, &$shouldFireEvent) {
            $locked = $this->withdrawalRequestRepository->lockById($withdrawalRequest->id);

            if (! $locked || ! in_array($locked->status, ['pending', 'processing'])) {
                return;
            }

            $locked->markAsCompleted([
                'payment_reference' => $data['payment_reference'],
                'approved_by' => auth()->user()->name,
                'approved_at' => now()->toDateTimeString(),
            ]);

            $this->withdrawalRequestRepository->update($locked, [
                'admin_note' => $data['admin_note'] ?? null,
            ]);

            $withdrawalRequest->setRawAttributes($locked->getAttributes());
            $shouldFireEvent = true;
        });

        if ($shouldFireEvent) {
            event(new WithdrawalApproved($withdrawalRequest));
        }
    }

    public function rejectWithdrawal(WithdrawalRequest $withdrawalRequest, ?string $reason): void
    {
        $reason = $reason ?: 'بدون ذکر دلیل توسط ادمین';

        $shouldFireEvent = false;

        DB::transaction(function () use ($withdrawalRequest, $reason, &$shouldFireEvent) {
            $locked = $this->withdrawalRequestRepository->lockById($withdrawalRequest->id);

            if (! $locked || ! in_array($locked->status, ['pending', 'processing'])) {
                return;
            }

            $wallet = $this->specialistWalletRepository->lockById($locked->wallet_id);

            $wallet->increment('balance', $locked->amount);
            $wallet->decrement('total_withdrawn', $locked->amount);

            $wallet->transactions()->create([
                'type' => 'refund',
                'amount' => $locked->amount,
                'balance_after' => $wallet->balance,
                'description' => 'رد درخواست برداشت - کد: '.$locked->reference_code,
                'metadata' => [
                    'withdrawal_request_id' => $locked->id,
                    'rejection_reason' => $reason,
                ],
            ]);

            $locked->markAsFailed($reason);

            $withdrawalRequest->setRawAttributes($locked->getAttributes());
            $shouldFireEvent = true;
        });

        if ($shouldFireEvent) {
            event(new WithdrawalRejected($withdrawalRequest, $reason));
        }
    }

    public function autoPayout(WithdrawalRequest $withdrawalRequest): array
    {
        // ⭐ ۲۰۲۶-۰۹-۲۴: تسویه‌ی خودکار فقط از حساب زرین‌پال خود سالن؛ اگه سالن پیکربندی نشده، قبل از
        // processing کردن درخواست رد می‌شه (وگرنه درخواست بی‌دلیل به صف می‌رفت و بعد fail می‌شد).
        $salon = app(\App\Support\CurrentSalon::class)->get();
        if (! $salon || ! $salon->canAutoPayout()) {
            return [
                'success' => false,
                'message' => 'تسویه‌ی خودکار برای این سالن فعال نیست. اطلاعات تسویه‌ی زرین‌پال یا زیبال سالن را در «درگاه‌های پرداخت» وارد کنید، یا درخواست را دستی تسویه کنید.',
            ];
        }

        $dispatched = false;

        DB::transaction(function () use ($withdrawalRequest, &$dispatched) {
            $locked = $this->withdrawalRequestRepository->lockById($withdrawalRequest->id);

            if (! $locked || ! in_array($locked->status, ['pending', 'processing'])) {
                return;
            }

            if ($locked->status === 'processing') {
                return;
            }

            $this->withdrawalRequestRepository->update($locked, ['status' => 'processing']);
            $dispatched = true;
        });

        if (! $dispatched) {
            return [
                'success' => false,
                'message' => 'این درخواست در حال حاضر قابل ارسال به صف پردازش نیست (یا قبلاً ارسال شده).',
            ];
        }

        ProcessWithdrawalJob::dispatch($withdrawalRequest->id);

        return [
            'success' => true,
            'dispatched' => true,
        ];
    }

    public function updateSettings(array $data): void
    {
        $settings = WalletSetting::get();
        $this->walletSettingRepository->update($settings, $data);
    }

    public function settlePendingIncomes(
        ?SpecialistWallet $wallet = null,
        bool $ignoreDelay = false,
        string $source = 'schedule'
    ): array {
        $transactions = $this->walletTransactionRepository->getPendingIncomeForSettlement($wallet?->id);

        $settledCount = 0;
        $failedCount = 0;
        $settledAmount = 0.0;

        foreach ($transactions as $transaction) {
            $settlementDate = $transaction->metadata['settlement_date'] ?? null;

            if (! $ignoreDelay && (! $settlementDate || ! Carbon::parse($settlementDate)->isPast())) {
                continue;
            }

            $booking = $transaction->booking;
            if ($booking && $booking->booking_time && $booking->booking_time->isFuture()) {
                continue;
            }

            try {
                DB::transaction(function () use ($transaction, $source) {
                    $transactionWallet = $transaction->wallet;
                    $amount = (float) $transaction->amount;

                    $transactionWallet->settlePendingAmount($amount);

                    $metadata = $transaction->metadata;
                    $metadata['status'] = 'settled';
                    $metadata['settled_at'] = now()->toDateTimeString();
                    $metadata['settled_by'] = $source;
                    $this->walletTransactionRepository->update($transaction, ['metadata' => $metadata]);
                    $this->walletTransactionRepository->update($transaction, ['balance_after' => $transactionWallet->balance]);
                });

                $settledCount++;
                $settledAmount += (float) $transaction->amount;
            } catch (\Throwable $e) {
                $failedCount++;

                Log::error('خطا در تسویه‌ی تراکنش کیف‌پول', [
                    'transaction_id' => $transaction->id,
                    'source' => $source,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'settledCount' => $settledCount,
            'failedCount' => $failedCount,
            'settledAmount' => $settledAmount,
        ];
    }
}
