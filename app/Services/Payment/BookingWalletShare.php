<?php

namespace App\Services\Payment;

use App\Models\Booking;
use App\Models\PaymentTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * سهم کیف پولِ پرداخت ترکیبی یک نوبت (کیف پول + درگاه) — روی خود ردیف تراکنش، نه session (۲۰۲۶-۱۰-۱۱).
 *
 * چرخه: hold (کم شدن از کیف پول هنگام شروع پرداخت) ← یکی از این دو، فقط یک بار:
 *  - settle: پرداخت درگاه موفق شد و این مبلغ بخشی از پیش‌پرداخت نوبت است؛
 *  - release: پرداخت به نتیجه نرسید (شکست، انصراف، مشتری برنگشت و نوبت لغو شد، نوبت با تراکنش دیگری پرداخت شد)
 *    و مبلغ به کیف پول برمی‌گردد.
 * هر گذار با یک update شرطی روی wallet_state='held' انجام می‌شود؛ پس callback تکراری، لغو خودکار و
 * LostSlotRefundService هرگز دو بار پول برنمی‌گردانند.
 */
class BookingWalletShare
{
    public const HELD = 'held';

    public const SETTLED = 'settled';

    public const RELEASED = 'released';

    /**
     * مبلغ را از کیف پول مشتری کم می‌کند (با قفل ردیف کیف پول). بعدش باید attach روی تراکنش صدا زده شود.
     */
    public function hold(User $user, Booking $booking, float $toman, string $description): void
    {
        DB::transaction(function () use ($user, $booking, $toman, $description) {
            $wallet = $user->getOrCreateWallet();
            $wallet = $wallet->newQuery()->lockForUpdate()->findOrFail($wallet->id);
            $wallet->deductPayment($toman, $booking->id, $description);
        });
    }

    public function attach(PaymentTransaction $transaction, float $toman): void
    {
        if ($toman <= 0) {
            return;
        }

        $transaction->forceFill(['wallet_toman' => $toman, 'wallet_state' => self::HELD])->save();
    }

    /** سهم held را «مصرف‌شده» علامت می‌زند؛ مبلغ برمی‌گردد (۰ اگر سهمی نبود یا قبلاً برگشته بود). */
    public function settle(?PaymentTransaction $transaction): float
    {
        return $this->transition($transaction, self::SETTLED);
    }

    /**
     * سهم held را برای برگشت به کیف پول «برمی‌دارد» بدون واریز — برای کسی که خودش واریز را انجام می‌دهد
     * (LostSlotRefundService کل مبلغ را یک‌جا واریز می‌کند).
     */
    public function claim(?PaymentTransaction $transaction): float
    {
        return $this->transition($transaction, self::RELEASED);
    }

    /** سهم held را به کیف پول مشتری برمی‌گرداند. */
    public function release(?PaymentTransaction $transaction, string $reason): float
    {
        $toman = $this->claim($transaction);
        if ($toman <= 0) {
            return 0.0;
        }

        $user = User::find($transaction->user_id);
        if (! $user) {
            Log::error('Wallet share could not be returned: the customer no longer exists', ['transaction_id' => $transaction->id, 'toman' => $toman]);

            return 0.0;
        }

        // پرداخت دوره‌ی درمانی (بسته‌ی ب) هم همین سهم کیف پول را دارد؛ payable آن دوره است نه نوبت
        if ($transaction->purpose === 'course') {
            $user->getOrCreateWallet()->addRefund($toman, null, "بازگشت سهم کیف پول پرداخت دوره — {$reason}", ['customer_course_id' => (int) $transaction->payable_id]);

            return $toman;
        }

        $bookingId = (int) $transaction->payable_id;
        $user->getOrCreateWallet()->addRefund($toman, $bookingId, "بازگشت سهم کیف پول نوبت #{$bookingId} — {$reason}");

        return $toman;
    }

    /** همه‌ی سهم‌های held نوبت (به‌جز تراکنش پرداخت‌شده‌ی آن) را برمی‌گرداند. */
    public function releaseForBooking(Booking $booking, string $reason, ?int $exceptTransactionId = null): float
    {
        $total = 0.0;

        $transactions = PaymentTransaction::query()
            ->where('payable_type', $booking->getMorphClass())
            ->where('payable_id', $booking->id)
            ->where('wallet_state', self::HELD)
            ->when($exceptTransactionId, fn ($q) => $q->whereKeyNot($exceptTransactionId))
            ->get();

        foreach ($transactions as $transaction) {
            $total += $this->release($transaction, $reason);
        }

        return $total;
    }

    private function transition(?PaymentTransaction $transaction, string $to): float
    {
        if (! $transaction || (float) $transaction->wallet_toman <= 0) {
            return 0.0;
        }

        $changed = PaymentTransaction::whereKey($transaction->id)
            ->where('wallet_state', self::HELD)
            ->toBase()
            ->update(['wallet_state' => $to, 'updated_at' => now()]);

        if ($changed !== 1) {
            return 0.0;
        }

        $transaction->wallet_state = $to;

        return (float) $transaction->wallet_toman;
    }
}
