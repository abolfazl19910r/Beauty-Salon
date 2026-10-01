<?php

namespace App\Services\Specialist;

use App\Events\Withdrawal\Requested\WithdrawalRequested;
use App\Exceptions\IdempotencyKeyReusedException;
use App\Models\Specialist;
use App\Models\SpecialistWallet;
use App\Models\WalletSetting;
use App\Models\WithdrawalRequest;
use App\Repositories\Contracts\SpecialistWalletRepositoryInterface;
use App\Repositories\Contracts\WalletTransactionRepositoryInterface;
use App\Repositories\Contracts\WithdrawalRequestRepositoryInterface;
use App\Support\Idempotency;
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
            'iban_verified_by' => null,
            'iban_verified_at' => null,
        ]);
    }

    public function createWithdrawal(Specialist $specialist, array $data): array
    {
        $wallet = $specialist->getOrCreateWallet();
        $amount = (float) $data['amount'];
        $method = $data['method'];

        // idempotency (۲۰۲۶-۱۰-۰۱): همان فرم دوبار فرستاده شد → همان درخواست اول، بدون کم شدن دوباره‌ی موجودی
        $key = $data['idempotency_key'] ?? null;
        $fingerprint = Idempotency::fingerprint(['amount' => $amount, 'method' => $method]);
        try {
            if ($replayed = $this->replayedWithdrawal($specialist, $key, $fingerprint)) {
                return $replayed;
            }
        } catch (IdempotencyKeyReusedException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }

        $canWithdraw = $wallet->canWithdraw($amount);
        if (! $canWithdraw['success']) {
            return ['success' => false, 'message' => $canWithdraw['message']];
        }

        // دو درخواست هم‌زمان (۲۰۲۶-۱۰-۰۱): چک بالا فقط برای پیام سریع است. درستی با قفل ردیف کیف پول: چک موجودی دوباره روی
        // ردیف قفل‌شده انجام می‌شود، پس درخواست دوم منتظر اولی می‌ماند و موجودی کم‌شده را می‌بیند.
        $result = DB::transaction(function () use ($wallet, $specialist, $amount, $method, $key, $fingerprint) {
            $locked = $this->specialistWalletRepository->lockById($wallet->id);

            // دوباره زیر قفل: تکرار هم‌زمان پشت همین قفل منتظر مانده و حالا کلید commit‌شده‌ی اولی را می‌بیند
            if ($replayed = $this->replayedWithdrawal($specialist, $key, $fingerprint)) {
                return $replayed;
            }

            $canWithdraw = $locked->canWithdraw($amount);
            if (! $canWithdraw['success']) {
                return ['success' => false, 'message' => $canWithdraw['message']];
            }

            $feeCalculation = $locked->calculateWithdrawalFee($amount, $method);

            $withdrawalRequest = $this->withdrawalRequestRepository->create([
                'wallet_id' => $locked->id,
                'specialist_id' => $specialist->id,
                'amount' => $amount,
                'fee' => $feeCalculation['fee'],
                'net_amount' => $feeCalculation['net_amount'],
                'method' => $method,
                'iban' => $locked->iban,
                'account_holder_name' => $locked->account_holder_name,
                'status' => 'pending',
            ]);

            $locked->recordWithdrawal($amount, $withdrawalRequest->id);
            Idempotency::claim(Idempotency::WITHDRAWAL, $specialist->id, $key, $fingerprint, $withdrawalRequest->id);

            return ['success' => true, 'withdrawal_request' => $withdrawalRequest];
        }, attempts: 3);

        if (! $result['success'] || ! empty($result['replayed'])) {
            return $result;
        }

        event(new WithdrawalRequested($result['withdrawal_request']));

        return $result;
    }

    private function replayedWithdrawal(Specialist $specialist, ?string $key, string $fingerprint): ?array
    {
        $id = Idempotency::existing(Idempotency::WITHDRAWAL, $specialist->id, $key, $fingerprint);
        $withdrawal = $id ? $this->withdrawalRequestRepository->find($id) : null;

        return $withdrawal ? ['success' => true, 'withdrawal_request' => $withdrawal, 'replayed' => true] : null;
    }

    /**
     * لغو برداشت و برگشت مبلغ به کیف پول. false اگر درخواست (دیگر) قابل لغو نیست — مثلاً لغو هم‌زمان دیگری یا رد مدیر زودتر
     * انجام شده. ردیف برداشت قفل و وضعیتش داخل تراکنش دوباره چک می‌شود (همان ترتیب قفل رد/تأیید مدیر: برداشت، بعد کیف پول).
     */
    public function cancelWithdrawal(Specialist $specialist, WithdrawalRequest $withdrawalRequest): bool
    {
        return DB::transaction(function () use ($specialist, $withdrawalRequest) {
            $locked = $this->withdrawalRequestRepository->lockById($withdrawalRequest->id);

            if (! $locked || (int) $locked->specialist_id !== (int) $specialist->id || ! $locked->canBeCancelled()) {
                return false;
            }

            $wallet = $this->specialistWalletRepository->lockById($locked->wallet_id);

            $wallet->increment('balance', $locked->amount);
            $wallet->decrement('total_withdrawn', $locked->amount);

            $wallet->transactions()->create([
                'type' => 'refund',
                'amount' => $locked->amount,
                'balance_after' => $wallet->balance,
                'description' => 'لغو درخواست برداشت - کد: '.$locked->reference_code,
                'metadata' => [
                    'withdrawal_request_id' => $locked->id,
                ],
            ]);

            $this->withdrawalRequestRepository->update($locked, ['status' => 'cancelled']);
            $withdrawalRequest->setRawAttributes($locked->getAttributes(), true);

            return true;
        }, attempts: 3);
    }

    public function calculateFee(Specialist $specialist, float $amount, string $method): array
    {
        $wallet = $specialist->getOrCreateWallet();

        return $wallet->calculateWithdrawalFee($amount, $method);
    }
}
