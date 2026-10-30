<?php

namespace App\Services\Payment;

use App\Jobs\RecheckHeldBookingPaymentJob;
use App\Models\Booking;
use App\Models\PaymentTransaction;
use App\Payments\GatewayManager;
use App\Payments\GatewayVerifyRequest;
use App\Repositories\Contracts\BookingRepositoryInterface;
use Illuminate\Support\Facades\Log;

/**
 * ⭐ نوبتی که پرداختش با قطعی لحظه‌ای اینترنت تأیید نشد، لغو نمی‌شود (تصمیم ۴، ۲۰۲۶-۱۰-۲۲).
 *
 * پیش‌پرداخت نوبت با درگاه‌های غیرمستقیم (زرین‌پال، زیبال، وندار، آسان پرداخت): وقتی درخواست تأیید (verify) از سرور ما خارج
 * نشد (DNS/اتصال/TLS) یا رفت و پاسخش نیامد (timeout، ۵xx)، پیش‌تر نوبت فوراً لغو می‌شد؛ پول را یا خود درگاه به کارت برمی‌گرداند،
 * یا reconcile بعداً تأیید می‌کرد و به کیف پول مشتری می‌ریخت — در هر دو حال مشتری پول داده بود و نوبت نداشت.
 *
 * حالا:
 * - hold(): تراکنش «verifying» می‌شود و نوبت در «در انتظار پرداخت» با ساعتش می‌ماند (لغو خودکار پرداخت‌نشده‌ها و پرداخت دوباره‌ی
 *   همان نوبت بسته‌اند). یک بار یک دقیقه بعد (RecheckHeldBookingPaymentJob) و بعد هر اجرای payments:reconcile (۵ دقیقه) دوباره:
 * - check(): همان verify درگاه —
 *   - تأیید شد ← نوبت مثل بازگشت موفق ثبت می‌شود (BookingPaymentFinalizer::completeVerified؛ پیامک‌های معمول نوبت)؛
 *   - رد قطعی ← نوبت لغو، سهم کیف پول برمی‌گردد (پیامک لغو معمول)؛
 *   - باز هم بی‌پاسخ ← تا HOLD_MINUTES (= عمر تراکنش در جریان، ۶۰ دقیقه) بعد از برگشت مشتری صبر؛ بعد نوبت لغو و تراکنش به همان
 *     مسیر قبلی «بی‌پاسخ» (UnansweredVerifyRecovery تا ۲۴ ساعت، تأیید دیرهنگام ← کیف پول).
 * هم‌زمانی: هر بررسی تراکنش را اتمی verifying → verify_check می‌کند؛ refresh صفحه‌ی بانک در این مدت فقط «در حال بررسی» می‌گوید.
 * درگاه‌های مستقیم بانکی (سامان، ملت، پارسیان) و پرداخت دوره/شارژ کیف پول بی‌تغییرند.
 */
class HeldBookingPayment
{
    public const HELD = 'verifying';

    public const CHECKING = 'verify_check';

    /** وضعیت‌هایی که نوبت را نگه می‌دارند */
    public const STATUSES = [self::HELD, self::CHECKING];

    public const HOLD_MINUTES = PaymentTransaction::PENDING_LIFETIME_MINUTES;

    public const FIRST_CHECK_SECONDS = 60;

    /** بررسی‌ای که بیش از این وسط کار ماند (worker متوقف شد) دوباره پرسیده می‌شود */
    public const STUCK_CHECK_MINUTES = 10;

    public const MESSAGE = 'پرداخت شما در حال بررسی با درگاه است و نوبت برایتان نگه داشته شده؛ نتیجه را چند دقیقه‌ی دیگر با پیامک می‌فرستیم. لطفاً دوباره پرداخت نکنید.';

    public function __construct(
        private readonly GatewayManager $gateways,
        private readonly BookingRepositoryInterface $bookings,
        private readonly BookingWalletShare $walletShare,
    ) {}

    /** این نتیجه‌ی verify سزاوار نگه داشتن نوبت است؟ */
    public static function qualifies(?PaymentTransaction $tx, array $raw): bool
    {
        return $tx !== null
            && $tx->purpose === 'booking'
            && in_array($tx->driver, UnansweredVerifyRecovery::DRIVERS, true)
            && (($raw['unanswered'] ?? false) === true || ($raw['verify_not_sent'] ?? false) === true);
    }

    public static function isHeld(?PaymentTransaction $tx): bool
    {
        return $tx !== null && in_array($tx->status, self::STATUSES, true);
    }

    public function hold(PaymentTransaction $tx): void
    {
        $response = (array) $tx->verify_response;
        $tx->forceFill([
            'status' => self::HELD,
            'verify_response' => ['held' => true, 'hold_until' => now()->addMinutes(self::HOLD_MINUTES)->toIso8601String(), 'hold_checks' => 0] + $response,
        ])->save();

        Log::warning('Booking payment held: gateway verify did not complete', [
            'transaction_id' => $tx->id, 'booking_id' => $tx->payable_id, 'driver' => $tx->driver,
            'not_sent' => (bool) ($response['verify_not_sent'] ?? false),
        ]);

        // صف sync (لوکال بی worker) تأخیر ندارد و همین درخواست را دو برابر معطل می‌کرد — آن‌جا فقط payments:reconcile
        if (config('queue.default') !== 'sync') {
            dispatch(new RecheckHeldBookingPaymentJob($tx->id))->delay(now()->addSeconds(self::FIRST_CHECK_SECONDS))->afterCommit();
        }
    }

    /** @return 'paid'|'refunded'|'not_paid'|'held'|'expired'|'skipped' */
    public function check(PaymentTransaction $tx): string
    {
        $claimed = PaymentTransaction::whereKey($tx->id)
            ->where(fn ($q) => $q->where('status', self::HELD)
                ->orWhere(fn ($s) => $s->where('status', self::CHECKING)->where('updated_at', '<=', now()->subMinutes(self::STUCK_CHECK_MINUTES))))
            ->update(['status' => self::CHECKING, 'updated_at' => now()]);
        if ($claimed !== 1) {
            return 'skipped';
        }

        $tx->refresh();

        // در صف/کنسول CurrentSalon نیست (حالت سخت‌گیر BelongsToSalon): کار با سالن خود تراکنش
        return app(\App\Support\CurrentSalon::class)->withSalon((int) $tx->salon_id, fn () => $this->runCheck($tx));
    }

    /** @return 'paid'|'refunded'|'not_paid'|'held'|'expired' */
    private function runCheck(PaymentTransaction $tx): string
    {
        $response = (array) $tx->verify_response;
        $response['hold_checks'] = (int) ($response['hold_checks'] ?? 0) + 1;
        $booking = Booking::find($tx->payable_id);

        if (! $booking || ! $tx->gateway) {
            return $this->giveUp($tx, $booking, $response, 'نوبت یا درگاه این پرداخت دیگر نیست');
        }

        try {
            $result = $this->gateways->verify($tx->gateway, new GatewayVerifyRequest((int) $tx->amount_rial, [], $tx->token, $tx->id));
        } catch (\Throwable $e) {
            Log::warning('Held booking payment: verify threw', ['transaction_id' => $tx->id, 'error' => $e->getMessage()]);
            $result = null;
        }

        if ($result !== null && $result->success) {
            $tx->forceFill([
                'status' => 'paid',
                'needs_attention' => ($result->raw['settlement_failed'] ?? false) === true,
                'ref_id' => $result->refId,
                'card_pan' => $result->cardPan,
                'verify_response' => ['held' => true, 'hold_result' => 'paid'] + $result->raw + $response,
                'verified_at' => now(),
            ])->save();

            $outcome = app(BookingPaymentFinalizer::class)->completeVerified($booking->refresh(), [
                'ref_id' => $result->refId,
                'reference' => $result->token ?? $tx->token,
                'card_pan' => $result->cardPan,
                'gateway' => $tx->driver,
                'gateway_fee' => intdiv((int) $tx->fee_rial, 10),
            ], $tx);

            Log::info('Held booking payment confirmed by the gateway', ['transaction_id' => $tx->id, 'booking_id' => $booking->id, 'state' => $outcome['state']]);

            return $outcome['state'] === 'paid' ? 'paid' : 'refunded';
        }

        $stillUnknown = $result === null
            || ($result->raw['unanswered'] ?? false) === true
            || ($result->raw['verify_not_sent'] ?? false) === true;

        if (! $stillUnknown) {
            $tx->forceFill([
                'status' => 'failed',
                // ⭐ بسته‌ی 5ز: زرین‌پال ‎-50/‎-52 — شاید پول کسر شده ← «پرداخت‌های نیازمند بررسی»
                'needs_attention' => ($result->raw['needs_attention'] ?? false) === true,
                'verify_response' => ['held' => true, 'hold_result' => 'not_paid', 'unanswered' => false, 'hold_message' => $result->message] + $result->raw + $response,
            ])->save();
            if ($tx->needs_attention) {
                app(\App\Services\Admin\AttentionNotifier::class)->paymentFlagged($tx->id);
            }
            $this->cancel($booking, 'پرداخت از درگاه تأیید نشد');

            return 'not_paid';
        }

        $holdUntil = isset($response['hold_until']) ? \Illuminate\Support\Carbon::parse($response['hold_until']) : now();
        if (now()->lt($holdUntil)) {
            $tx->forceFill(['status' => self::HELD, 'verify_response' => $response])->save();

            return 'held';
        }

        return $this->giveUp($tx, $booking, $response, 'درگاه تا پایان مهلت پاسخ نداد');
    }

    /**
     * مهلت تمام شد: نوبت لغو و تراکنش به مسیر قبلی «پاسخ تأیید نرسید» (UnansweredVerifyRecovery تا ۲۴ ساعت؛ اگر درگاه بعداً
     * تأیید کرده بود، مبلغ به کیف پول مشتری).
     */
    private function giveUp(PaymentTransaction $tx, ?Booking $booking, array $response, string $why): string
    {
        $tx->forceFill([
            'status' => 'failed',
            'verify_response' => ['held' => true, 'hold_result' => 'expired', 'unanswered' => true, 'hold_message' => $why] + $response,
        ])->save();

        if ($booking) {
            $this->cancel($booking, 'تأیید پرداخت از درگاه نرسید');
        }

        Log::warning('Held booking payment released after the hold window', ['transaction_id' => $tx->id, 'reason' => $why]);

        return 'expired';
    }

    private function cancel(Booking $booking, string $reason): void
    {
        $booking->refresh();
        if ($booking->payment_status !== 'paid' && $booking->status !== 'cancelled') {
            $this->bookings->update($booking, ['status' => 'cancelled', 'cancellation_reason' => $reason, 'cancelled_by' => 'system', 'cancelled_at' => now()]);
        }
        $this->walletShare->releaseForBooking($booking, $reason);
    }
}
