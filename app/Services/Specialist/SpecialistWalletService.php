<?php

namespace App\Services\Specialist;

use App\Events\Withdrawal\Requested\WithdrawalRequested;
use App\Models\Specialist;
use App\Models\SpecialistWallet;
use App\Models\WalletSetting;
use App\Models\WithdrawalRequest;
use App\Traits\HasJalaliDates;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SpecialistWalletService
{
    use HasJalaliDates;

// Modified: Removed the resolveSpecialist() method that was here before.
// App\Traits\ResolvesSpecialist already exists in the project and do the same (via
// The relationship $user->specialist, which itself is defined based on phone match.
// Controllers now use that trait directly.

    public function getWalletOverview(Specialist $specialist): array
    {
        $wallet = $specialist->getOrCreateWallet();
        $settings = WalletSetting::first();

        $recentTransactions = $wallet->transactions()
            ->latest()
            ->limit(10)
            ->get();

        $withdrawalRequests = $wallet->withdrawalRequests()
            ->latest()
            ->paginate(10);

        $currentMonthIncome = $wallet->transactions()
            ->where('type', 'income')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        $currentMonthWithdrawals = $wallet->transactions()
            ->where('type', 'withdrawal')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

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

        $query = $wallet->transactions()->with('booking');

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['date_from'])) {
            $dateFrom = $this->parseJalali($filters['date_from'], context: 'تاریخ از فیلتر تراکنش‌های کیف پول')?->startOfDay();
            if ($dateFrom) {
                $query->where('created_at', '>=', $dateFrom);
            }
        }

        if (! empty($filters['date_to'])) {
            $dateTo = $this->parseJalali($filters['date_to'], context: 'تاریخ تا فیلتر تراکنش‌های کیف پول')?->endOfDay();
            if ($dateTo) {
                $query->where('created_at', '<=', $dateTo);
            }
        }

        return $query->latest()->paginate(20)->withQueryString();
    }

    public function updateIban(SpecialistWallet $wallet, array $data): void
    {
        $wallet->update([
            'iban' => 'IR'.str_replace(' ', '', $data['iban']),
            'account_holder_name' => $data['account_holder_name'],
            'bank_name' => $data['bank_name'],
            'iban_verified' => false,
        ]);
    }

    /**
     * @return array{success: bool, message?: string, withdrawal_request?: WithdrawalRequest}
     */
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

            $withdrawalRequest = WithdrawalRequest::create([
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

            $withdrawalRequest->update(['status' => 'cancelled']);
        });
    }

    public function calculateFee(Specialist $specialist, float $amount, string $method): array
    {
        $wallet = $specialist->getOrCreateWallet();

        return $wallet->calculateWithdrawalFee($amount, $method);
    }

}
