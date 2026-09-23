<?php

namespace App\Services\Specialist;

use App\Events\Withdrawal\Requested\WithdrawalRequested;
use App\Models\Specialist;
use App\Models\SpecialistWallet;
use App\Models\WalletSetting;
use App\Models\WithdrawalRequest;
use App\Repositories\Contracts\SpecialistWalletRepositoryInterface;
use App\Repositories\Contracts\WalletTransactionRepositoryInterface;
use App\Repositories\Contracts\WithdrawalRequestRepositoryInterface;
use App\Traits\HasJalaliDates;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SpecialistWalletService
{
    use HasJalaliDates;

    public function __construct(
        protected readonly SpecialistWalletRepositoryInterface $specialistWalletRepository,
        protected readonly WithdrawalRequestRepositoryInterface $withdrawalRequestRepository,
        protected readonly WalletTransactionRepositoryInterface $walletTransactionRepository,
    ) {}

    public function getWalletOverview(Specialist $specialist): array
    {
        $wallet = $specialist->getOrCreateWallet();
        $settings = WalletSetting::get();

        $recentTransactions = $this->walletTransactionRepository->getRecentForWallet($wallet->id, 10);

        $withdrawalRequests = $this->withdrawalRequestRepository->paginateForWallet($wallet->id, 10);

        $currentMonthIncome = $this->walletTransactionRepository->sumForWalletByTypeAndMonth(
            $wallet->id, 'income', now()->month, now()->year
        );

        $currentMonthWithdrawals = $this->walletTransactionRepository->sumForWalletByTypeAndMonth(
            $wallet->id, 'withdrawal', now()->month, now()->year
        );

        return compact(
            'wallet',
            'settings',
            'recentTransactions',
            'withdrawalRequests',
            'currentMonthIncome',
            'currentMonthWithdrawals'
        );
    }

    public function getTransactions(Specialist $specialist, array $filters): LengthAwarePaginator
    {
        $wallet = $specialist->getOrCreateWallet();

        $normalizedFilters = [
            'type' => $filters['type'] ?? null,
        ];

        if (! empty($filters['date_from'])) {
            $dateFrom = $this->parseJalali($filters['date_from'], context: 'تاریخ از فیلتر تراکنش‌های کیف پول')?->startOfDay();
            if ($dateFrom) {
                $normalizedFilters['date_from'] = $dateFrom;
            }
        }

        if (! empty($filters['date_to'])) {
            $dateTo = $this->parseJalali($filters['date_to'], context: 'تاریخ تا فیلتر تراکنش‌های کیف پول')?->endOfDay();
            if ($dateTo) {
                $normalizedFilters['date_to'] = $dateTo;
            }
        }

        return $this->walletTransactionRepository->paginateForWalletWithFilters($wallet->id, $normalizedFilters, 20);
    }

    public function updateIban(SpecialistWallet $wallet, array $data): void
    {
        $this->specialistWalletRepository->update($wallet, [
            'iban' => 'IR'.str_replace(' ', '', $data['iban']),
            'account_holder_name' => $data['account_holder_name'],
            'bank_name' => $data['bank_name'],
            'iban_verified' => false,
        ]);
    }

    public function createWithdrawal(Specialist $specialist, array $data): array
    {
        $wallet = $specialist->getOrCreateWallet();
        $amount = (float) $data['amount'];
        $method = $data['method'];

        $canWithdraw = $wallet->canWithdraw($amount);
        if (! $canWithdraw['success']) {
            return ['success' => false, 'message' => $canWithdraw['message']];
        }

        $withdrawalRequest = DB::transaction(function () use ($wallet, $specialist, $amount, $method) {
            $feeCalculation = $wallet->calculateWithdrawalFee($amount, $method);

            $withdrawalRequest = $this->withdrawalRequestRepository->create([
                'wallet_id' => $wallet->id,
                'specialist_id' => $specialist->id,
                'amount' => $amount,
                'fee' => $feeCalculation['fee'],
                'net_amount' => $feeCalculation['net_amount'],
                'method' => $method,
                'iban' => $wallet->iban,
                'account_holder_name' => $wallet->account_holder_name,
                'status' => 'pending',
            ]);

            $wallet->recordWithdrawal($amount, $withdrawalRequest->id);

            return $withdrawalRequest;
        });

        event(new WithdrawalRequested($withdrawalRequest));

        return ['success' => true, 'withdrawal_request' => $withdrawalRequest];
    }

    public function cancelWithdrawal(Specialist $specialist, WithdrawalRequest $withdrawalRequest): void
    {
        DB::transaction(function () use ($specialist, $withdrawalRequest) {
            $wallet = $specialist->wallet;

            $wallet->increment('balance', $withdrawalRequest->amount);
            $wallet->decrement('total_withdrawn', $withdrawalRequest->amount);

            $wallet->transactions()->create([
                'type' => 'refund',
                'amount' => $withdrawalRequest->amount,
                'balance_after' => $wallet->balance,
                'description' => 'لغو درخواست برداشت - کد: '.$withdrawalRequest->reference_code,
                'metadata' => [
                    'withdrawal_request_id' => $withdrawalRequest->id,
                ],
            ]);

            $this->withdrawalRequestRepository->update($withdrawalRequest, ['status' => 'cancelled']);
        });
    }

    public function calculateFee(Specialist $specialist, float $amount, string $method): array
    {
        $wallet = $specialist->getOrCreateWallet();

        return $wallet->calculateWithdrawalFee($amount, $method);
    }
}
