<?php

namespace App\Services\Payment;

use App\Models\Booking;
use App\Models\PaymentTransaction;
use App\Payments\Contracts\ReversibleGateway;
use App\Payments\GatewayManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * ⭐ درگاه پول رو گرفت ولی نوبت دیگه ثبت‌شدنی نیست (ساعتش در این فاصله به نفر دیگه‌ای رسیده — index یکتای
 * bookings.active_slot_key). تصمیم ابوالفضل (۲۰۲۶-۰۹-۲۶): پول همیشه برمی‌گرده —
 * - سامان: Reverse مستقیم به کارت (تا ۵۰ دقیقه بعد از تراکنش مجازه)؛ اگه Reverse نشد، کیف پول.
 * - ملت: پرداخت موفق همیشه settle شده و به‌پرداخت تراکنش settle‌شده رو برنمی‌گردونه → کیف پول
 *   (MellatDriver::reverseTransaction بدون تماس با بانک false می‌ده).
 * - پارسیان: تراکنش تاییدشده هم برگشت‌پذیره → کارت (ReversalRequest)؛ اگر نشد، کیف پول.
 * - بقیه‌ی درگاه‌ها: کیف پول مشتری (همون سالن).
 * - بخش کیف پولیِ پرداخت ترکیبی (wallet + درگاه) همیشه به کیف پول.
 *
 * idempotent: تراکنش اول با یک update شرطی paid → reversing قفل می‌شه؛ callback تکراری (refresh) هیچ‌وقت دو بار
 * پول برنمی‌گردونه. مسیر قدیمی بدون tx (callbackهای session-محور قبل از دفتر تراکنش‌ها) با وجود تراکنش بازگشتِ
 * همین نوبت در کیف پول محافظت می‌شه.
 */
class LostSlotRefundService
{
    public const WALLET_NOTE = 'ساعت انتخابی هنگام پرداخت دیگر آزاد نبود';

    public const CANCELLED_NOTE = 'نوبت پیش از تکمیل پرداخت لغو شده بود';

    public function __construct(
        private readonly GatewayManager $gateways,
        private readonly BookingWalletShare $walletShare,
    ) {}

    /**
     * @param  array{wallet_amount?: float|int}|null  $partial  سهم کیف پول وقتی تراکنشی در دفتر نیست (مسیر قدیمی)؛
     *                                                          با تراکنش، سهم کیف پول از خود ردیف خوانده می‌شود
     * @param  float|int  $gatewayTomanFallback  مبلغ درگاه وقتی تراکنشی در دفتر نیست (مسیر قدیمی)
     * @param  string  $reason  slot_taken (ساعت رزرو شد) یا booking_cancelled (نوبت پیش از رسیدن پرداخت لغو شده بود)
     * @return array{handled: bool, card_toman: int, wallet_toman: float, reason: string}
     */
    public function refund(Booking $booking, ?PaymentTransaction $transaction, ?array $partial, float|int $gatewayTomanFallback, string $reason = 'slot_taken'): array
    {
        $note = $reason === 'booking_cancelled' ? self::CANCELLED_NOTE : self::WALLET_NOTE;
        $walletPart = (float) ($partial['wallet_amount'] ?? 0);

        if ($transaction) {
            // قفل: فقط یک درخواست از paid به reversing می‌رسه
            if (PaymentTransaction::whereKey($transaction->id)->where('status', 'paid')->toBase()->update(['status' => 'reversing']) !== 1) {
                return ['handled' => false, 'card_toman' => 0, 'wallet_toman' => 0.0, 'reason' => $reason];
            }
            $gatewayToman = intdiv((int) $transaction->amount_rial, 10); // کل مبلغی که مشتری داد، با کارمزد
            // سهم کیف پولِ همین تلاش (اگر هنوز برنگشته)؛ یک‌جا با مبلغ درگاه واریز می‌شود
            $walletPart = $this->walletShare->claim($transaction);
        } else {
            if ($booking->user->getOrCreateWallet()->transactions()->where('booking_id', $booking->id)->where('type', 'refund')
                ->where('description', 'like', '%'.$note.'%')->exists()) {
                return ['handled' => false, 'card_toman' => 0, 'wallet_toman' => 0.0, 'reason' => $reason];
            }
            $gatewayToman = (float) $gatewayTomanFallback;
        }

        $toCard = $transaction ? $this->reverseToCard($transaction) : false;
        $walletToman = $walletPart + ($toCard ? 0 : $gatewayToman);

        DB::transaction(function () use ($booking, $transaction, $toCard, $walletToman, $gatewayToman, $reason, $note) {
            if ($walletToman > 0) {
                $booking->user->getOrCreateWallet()->addRefund(
                    $walletToman,
                    $booking->id,
                    'بازگشت وجه نوبت #'.$booking->id.' — '.$note,
                );
            }

            if ($transaction) {
                $response = (array) $transaction->fresh()->verify_response;
                $response['refund'] = [
                    'reason' => $reason, 'at' => now()->toIso8601String(),
                    'card_toman' => $toCard ? $gatewayToman : 0, 'wallet_toman' => $walletToman,
                ];
                PaymentTransaction::whereKey($transaction->id)->toBase()->update([
                    'status' => $toCard ? 'reversed' : 'refunded',
                    'verify_response' => json_encode($response, JSON_UNESCAPED_UNICODE),
                    'updated_at' => now(),
                ]);
            }

            $booking->forceFill([
                'status' => 'cancelled',
                'cancelled_by' => $booking->cancelled_by ?? 'system',
                'cancelled_at' => $booking->cancelled_at ?? now(),
                'cancellation_reason' => $reason === 'booking_cancelled' && $booking->cancellation_reason
                    ? $booking->cancellation_reason.'؛ پرداخت دیرهنگام برگشت داده شد'
                    : $note.'؛ مبلغ پرداختی برگشت داده شد',
            ])->save();
        });

        // ⭐ مشتری حتماً باید بدونه پولش کجا رفت (پیامک صف‌دار، بدون مصرف سهمیه‌ی سالن)
        $booking->user?->notify(new \App\Notifications\Payment\PaymentRefundedNotification(
            $reason,
            $toCard ? (int) $gatewayToman : 0,
            (int) round($walletToman),
            \App\Models\Salon::find($booking->salon_id),
            $booking->id,
        ));

        Log::warning('Booking payment refunded: slot was taken while the customer was at the bank', [
            'booking_id' => $booking->id, 'transaction_id' => $transaction?->id,
            'card_toman' => $toCard ? $gatewayToman : 0, 'wallet_toman' => $walletToman, 'wallet_part' => $walletPart,
        ]);

        return ['handled' => true, 'card_toman' => $toCard ? (int) $gatewayToman : 0, 'wallet_toman' => $walletToman, 'reason' => $reason];
    }

    private function reverseToCard(PaymentTransaction $transaction): bool
    {
        if (! $transaction->gateway || ! $transaction->gateway_receipt) {
            return false;
        }

        try {
            $driver = $this->gateways->driver($transaction->gateway);

            return $driver instanceof ReversibleGateway && $driver->reverseTransaction($transaction->fresh());
        } catch (Throwable) {
            return false;
        }
    }

    /** پیام صفحه‌ی نتیجه برای مشتری. */
    public static function message(array $refund): string
    {
        $parts = [];
        if ($refund['card_toman'] > 0) {
            $parts[] = number_format($refund['card_toman']).' تومان به کارت بانکی شما برگشت داده شد (طبق روال بانک معمولاً تا ۷۲ ساعت)';
        }
        if ($refund['wallet_toman'] > 0) {
            $parts[] = number_format($refund['wallet_toman']).' تومان به کیف پول شما در همین سالن برگشت داده شد';
        }

        $lead = ($refund['reason'] ?? 'slot_taken') === 'booking_cancelled'
            ? 'این نوبت پیش از تکمیل پرداخت لغو شده بود و دوباره فعال نمی‌شود. '
            : 'متأسفانه ساعتی که انتخاب کرده بودید در مدتی که در صفحه‌ی بانک بودید رزرو شد و نوبت ثبت نشد. ';

        return $lead.implode(' و ', $parts).'.';
    }
}
