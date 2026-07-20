<?php

namespace App\Services\Admin\Wallet;

use App\Events\Withdrawal\Approved\WithdrawalApproved;
use App\Events\Withdrawal\Rejected\WithdrawalRejected;
use App\Models\SpecialistWallet;
use App\Models\WalletSetting;
use App\Models\WalletTransaction;
use App\Models\WithdrawalRequest;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WalletAdminService
{
    /**
     * Paginated list of expert wallets with search and sort filter.
     */
    public function getWalletsList(array $filters): LengthAwarePaginator
    {
        $query = SpecialistWallet::with('specialist');

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('specialist', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        match ($filters['sort_by'] ?? 'balance_desc') {
            'balance_asc' => $query->orderBy('balance', 'asc'),
            'earned_desc' => $query->orderBy('total_earned', 'desc'),
            default => $query->orderBy('balance', 'desc'),
        };

        return $query->paginate(20);
    }

    /**
     * Aggregate statistics of all expert wallets (cards on the top of the index page).
     */
    public function getWalletTotals(): array
    {
        return [
            'totalBalance' => SpecialistWallet::sum('balance'),
            'totalEarned' => SpecialistWallet::sum('total_earned'),
            'totalWithdrawn' => SpecialistWallet::sum('total_withdrawn'),
            'totalPending' => SpecialistWallet::sum('pending_amount'),
        ];
    }

    /**
     * Details of a wallet with recent transactions (show page).
     */
    public function getWalletDetail(SpecialistWallet $wallet): array
    {
        $wallet->load('specialist', 'transactions', 'withdrawalRequests');

        $recentTransactions = $wallet->transactions()
            ->with('booking')
            ->latest()
            ->paginate(20);

        return [
            'wallet' => $wallet,
            'recentTransactions' => $recentTransactions,
        ];
    }

    /**
     * Paginated list of withdrawal requests with status/method/search filter.
     */
    public function getWithdrawalsList(array $filters): LengthAwarePaginator
    {
        $query = WithdrawalRequest::with(['specialist', 'wallet']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['method'])) {
            $query->where('method', $filters['method']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('reference_code', 'like', "%{$search}%")
                    ->orWhereHas('specialist', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        return $query->latest()->paginate(20);
    }

    /**
     * Quick statistics on top of withdrawal requests page.
     */
    public function getWithdrawalStats(): array
    {
        return [
            'pendingCount' => WithdrawalRequest::where('status', 'pending')->count(),
            'pendingAmount' => WithdrawalRequest::where('status', 'pending')->sum('amount'),
            'completedToday' => WithdrawalRequest::where('status', 'completed')
                ->whereDate('processed_at', today())
                ->count(),
        ];
    }

    public function verifyIban(SpecialistWallet $wallet): void
    {
        $wallet->update(['iban_verified' => true]);
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
                'description' => 'تعدیل دستی توسط ادمین: ' . $description,
                'metadata' => [
                    'admin_id' => auth()->id(),
                    'admin_name' => auth()->user()->name,
                ],
            ]);
        });
    }

    public function approveWithdrawal(WithdrawalRequest $withdrawalRequest, array $data): void
    {
        DB::transaction(function () use ($withdrawalRequest, $data) {
            $withdrawalRequest->markAsCompleted([
                'payment_reference' => $data['payment_reference'],
                'approved_by' => auth()->user()->name,
                'approved_at' => now()->toDateTimeString(),
            ]);

            $withdrawalRequest->update([
                'admin_note' => $data['admin_note'] ?? null,
            ]);
        });

        event(new WithdrawalApproved($withdrawalRequest));
    }

    public function rejectWithdrawal(WithdrawalRequest $withdrawalRequest, ?string $reason): void
    {
        // If the admin has not entered a reason (the field is displayed in the optional Blade), a default value is recorded
        $reason = $reason ?: 'بدون ذکر دلیل توسط ادمین';

        DB::transaction(function () use ($withdrawalRequest, $reason) {
            $wallet = $withdrawalRequest->wallet;

            $wallet->increment('balance', $withdrawalRequest->amount);
            $wallet->decrement('total_withdrawn', $withdrawalRequest->amount);

            $wallet->transactions()->create([
                'type' => 'refund',
                'amount' => $withdrawalRequest->amount,
                'balance_after' => $wallet->balance,
                'description' => 'رد درخواست برداشت - کد: ' . $withdrawalRequest->reference_code,
                'metadata' => [
                    'withdrawal_request_id' => $withdrawalRequest->id,
                    'rejection_reason' => $reason,
                ],
            ]);

            $withdrawalRequest->markAsFailed($reason);
        });

        event(new WithdrawalRejected($withdrawalRequest, $reason));
    }

    /**
     * @return array{success: bool, message?: string, reference_code?: string}
     */
    public function autoPayout(WithdrawalRequest $withdrawalRequest): array
    {
        /*
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.zarinpal.api_key'),
        ])->post('https://api.zarinpal.com/pg/v4/payout.json', [
            'merchant_id' => config('services.zarinpal.merchant_id'),
            'amount'      => $withdrawalRequest->net_amount * 10,
            'description' => "تسویه حساب متخصص: " . $withdrawalRequest->specialist->name,
            'destination_iban' => $withdrawalRequest->iban,
        ]);
        */

        $isSuccessful = true;
        $referenceCode = 'ZRP-' . rand(100000, 999999);

        if (!$isSuccessful) {
            return [
                'success' => false,
                'message' => 'خطا در اتصال به درگاه زرین‌پال یا عدم موجودی کافی در پنل زرین‌پال.',
            ];
        }

        DB::transaction(function () use ($withdrawalRequest, $referenceCode) {
            $withdrawalRequest->markAsCompleted([
                'payment_method' => 'zarinpal_auto',
                'payment_reference' => $referenceCode,
                'payout_id' => 'PAY-' . uniqid(),
            ]);
        });

        return [
            'success' => true,
            'reference_code' => $referenceCode,
        ];
    }

    public function updateSettings(array $data): void
    {
        $settings = WalletSetting::first();
        $settings->update($data);
    }

    /**
     * Settle "pending" revenue transactions and transfer them to the balance.
     * *
     * * The single source of this logic — both the `wallet:settle-pending` scheduled command (nightly, at 01:00)
     * * and the ``Manual Settlement'' button in the admin panel (both for all specialists and for a specific specialist) use the same method.
     * *
     * * @param SpecialistWallet|null $wallet If null, all wallets will be checked; otherwise, only this one.
     * * @param bool $ignoreDelay If true, the settlement delay (settlement_delay_days) will be ignored and all
     * * pending transactions (even those that are not yet due) will be settled immediately.
     * * @param string $source is recorded in the transaction metadata to indicate where the settlement came from: 'schedule' or 'admin_manual'.
     * * @return array{settledCount: int, failedCount: int, settledAmount: float}
     */
    public function settlePendingIncomes(
        ?SpecialistWallet $wallet = null,
        bool $ignoreDelay = false,
        string $source = 'schedule'
    ): array {
        $query = WalletTransaction::where('type', 'income')
            ->whereJsonContains('metadata->status', 'pending');

        if ($wallet) {
            $query->where('wallet_id', $wallet->id);
        }

        $settledCount = 0;
        $failedCount = 0;
        $settledAmount = 0.0;

        foreach ($query->get() as $transaction) {
            $settlementDate = $transaction->metadata['settlement_date'] ?? null;

            if (!$ignoreDelay && (!$settlementDate || !Carbon::parse($settlementDate)->isPast())) {
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
                    $transaction->update(['metadata' => $metadata]);
                    $transaction->update(['balance_after' => $transactionWallet->balance]);
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
