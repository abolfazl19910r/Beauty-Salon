<?php

namespace App\Services\Payment;

use App\Models\PaymentTransaction;
use App\Models\User;
use App\Models\UserWallet;
use Illuminate\Support\Facades\DB;

/**
 * واریز شارژ کیف پول مشتری بعد از تأیید درگاه — صفحه‌ی وب و اپ «ماهرو» (۲۰۲۶-۱۰-۱۱).
 *
 * فقط یک بار برای هر تراکنش: ردیف تراکنش با یک update شرطی (wallet_state: null → credited) «برداشته» می‌شود. قبلاً
 * دو callback هم‌زمان (رفرش، دو تب) هر دو پیش از ثبت «paid» از verify رد می‌شدند و درگاه‌هایی که تأیید تکراری را
 * موفق برمی‌گردانند کیف پول را دو بار شارژ می‌کردند.
 */
class WalletChargeService
{
    public const CREDITED = 'credited';

    /**
     * @return float|null موجودی تازه؛ null اگر این تراکنش قبلاً واریز شده بود
     */
    public function credit(User $user, float $amount, array $verifyResult, ?PaymentTransaction $transaction): ?float
    {
        return DB::transaction(function () use ($user, $amount, $verifyResult, $transaction) {
            if ($transaction && PaymentTransaction::whereKey($transaction->id)->whereNull('wallet_state')
                ->toBase()->update(['wallet_state' => self::CREDITED, 'wallet_toman' => $amount, 'updated_at' => now()]) !== 1) {
                return null;
            }

            $wallet = $user->getOrCreateWallet();
            $wallet = UserWallet::query()->lockForUpdate()->findOrFail($wallet->id);
            $wallet->increment('balance', $amount);
            $wallet->increment('total_deposited', $amount);
            $wallet->transactions()->create([
                'type' => 'deposit',
                'amount' => $amount,
                'balance_after' => $wallet->balance,
                'description' => 'شارژ کیف پول - کد پیگیری: '.($verifyResult['ref_id'] ?? 'نامشخص'),
                'metadata' => [
                    'payment_method' => 'gateway',
                    'gateway_ref' => $verifyResult['ref_id'] ?? null,
                    'card_pan' => $verifyResult['card_pan'] ?? null,
                    'gateway' => $verifyResult['gateway'] ?? null,
                    'gateway_fee' => $verifyResult['gateway_fee'] ?? 0,
                ],
            ]);

            return (float) $wallet->balance;
        });
    }
}
