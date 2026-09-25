<?php

namespace App\Services\Payment;

use App\Models\Booking;
use App\Models\PaymentTransaction;
use App\Models\Salon;
use App\Models\User;
use App\Notifications\Payment\PaymentRefundedNotification;
use App\Payments\GatewayManager;
use App\Payments\GatewayVerifyRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ⭐ پاسخ تایید درگاه‌های غیرمستقیم (زرین‌پال، زیبال، وندار، آسان پرداخت) نرسید (۲۰۲۶-۰۹-۲۶).
 *
 * در این حالت callback پرداخت رو «ناموفق» ثبت می‌کنه و نوبت لغو می‌شه؛ ولی شاید درگاه تایید کرده و پول مشتری الان در
 * حساب سالنه. این درگاه‌ها در کد ما API برگشت ندارن (برخلاف سامان/ملت/پارسیان که reconcile برگشت می‌زنه)، پس
 * payments:reconcile همون verify خود driver رو دوباره اجرا می‌کنه — دقیقاً کاری که refresh صفحه‌ی مشتری می‌کرد و
 * قراردادش در همه‌ی driverها تست‌شده است (استعلام‌های بدون تایید برای زیبال/وندار معنای وضعیت مستند و قابل‌تأییدی نداشتن):
 * - تایید شد → پول پیش سالنه و نوبت/شارژی ثبت نشده: نوبت → کل مبلغ به کیف پول مشتری؛ شارژ کیف پول → همون شارژ انجام می‌شه.
 *   مشتری پیامک می‌گیره. تراکنش «refunded» (نوبت) یا «paid» (شارژ) و دیگه دوباره تایید نمی‌شه.
 * - رد قطعی (پرداخت‌نشده، منقضی، …) → بسته می‌شه؛ پول تاییدنشده رو خود درگاه به کارت برمی‌گردونه.
 * - باز هم بی‌پاسخ → دفعه‌ی بعد، تا ۲۴ ساعت بعد از آخرین تلاش مشتری؛ بعدش برای بررسی دستی در لاگ.
 *
 * هم‌زمانی: قبل از تایید، تراکنش اتمی failed → reconciling می‌شه؛ callback پرداخت در این حالت دوباره تایید نمی‌کنه
 * (وگرنه ممکن بود هم نوبت ثبت بشه هم پول به کیف پول برگرده).
 */
class UnansweredVerifyRecovery
{
    public const DRIVERS = ['zarinpal', 'zibal', 'vandar', 'asanpardakht'];

    public const PURPOSES = ['booking', 'wallet_charge'];

    public function __construct(private readonly GatewayManager $gateways) {}

    /** @return 'credited'|'not_paid'|'unanswered'|'skipped' */
    public function recover(PaymentTransaction $tx): string
    {
        if (! in_array($tx->driver, self::DRIVERS, true) || ! in_array($tx->purpose, self::PURPOSES, true)) {
            return 'skipped';
        }

        // toBase(): updated_at دست نمی‌خوره تا پنجره‌ی ۲۴ ساعته از آخرین تلاش مشتری حساب بشه
        if (PaymentTransaction::whereKey($tx->id)->where('status', 'failed')->toBase()->update(['status' => 'reconciling']) !== 1) {
            return 'skipped';
        }

        $response = (array) $tx->verify_response;
        $response['recovery_attempts'] = (int) ($response['recovery_attempts'] ?? 0) + 1;

        if (! $tx->gateway) {
            $this->release($tx, $response);
            Log::warning('Unanswered verify recovery: gateway row is gone', ['transaction_id' => $tx->id]);

            return 'unanswered';
        }

        try {
            $result = $this->gateways->verify($tx->gateway, new GatewayVerifyRequest((int) $tx->amount_rial, [], $tx->token, $tx->id));
        } catch (\Throwable $e) {
            $this->release($tx, $response);
            Log::warning('Unanswered verify recovery: verify threw', ['transaction_id' => $tx->id, 'error' => $e->getMessage()]);

            return 'unanswered';
        }

        if (! $result->success) {
            if (($result->raw['unanswered'] ?? false) === true) {
                $this->release($tx, $response);

                return 'unanswered';
            }

            $this->release($tx, ['unanswered' => false, 'recovery' => 'not_paid', 'recovery_message' => $result->message] + $response);

            return 'not_paid';
        }

        $this->credit($tx, $result, $response);

        return 'credited';
    }

    private function release(PaymentTransaction $tx, array $response): void
    {
        PaymentTransaction::whereKey($tx->id)->toBase()->update([
            'status' => 'failed',
            'verify_response' => json_encode($response, JSON_UNESCAPED_UNICODE),
        ]);
    }

    private function credit(PaymentTransaction $tx, \App\Payments\GatewayVerifyResult $result, array $response): void
    {
        $user = User::find($tx->user_id);
        $isBooking = $tx->purpose === 'booking';
        // نوبت: کل مبلغی که مشتری داد (با کارمزد) برمی‌گرده. شارژ: همون مبلغ شارژ (مثل callback موفق)، بدون کارمزد درگاه.
        $toman = $isBooking ? intdiv((int) $tx->amount_rial, 10) : intdiv((int) $tx->amount_rial - (int) $tx->fee_rial, 10);

        DB::transaction(function () use ($tx, $result, $response, $user, $isBooking, $toman) {
            if ($user && $toman > 0) {
                $wallet = $user->getOrCreateWallet();
                if ($isBooking) {
                    $wallet->addRefund($toman, (int) $tx->payable_id, 'بازگشت وجه نوبت #'.$tx->payable_id.' — تایید پرداخت با تأخیر از درگاه رسید');
                } else {
                    $wallet->increment('balance', $toman);
                    $wallet->increment('total_deposited', $toman);
                    $wallet->transactions()->create([
                        'type' => 'deposit',
                        'amount' => $toman,
                        'balance_after' => $wallet->fresh()->balance,
                        'description' => 'شارژ کیف پول (تایید با تأخیر) - کد پیگیری: '.($result->refId ?? 'نامشخص'),
                        'metadata' => ['payment_method' => 'gateway', 'gateway_ref' => $result->refId, 'gateway' => $tx->driver, 'payment_transaction_id' => $tx->id],
                    ]);
                }
            }

            PaymentTransaction::whereKey($tx->id)->toBase()->update([
                'status' => $isBooking ? 'refunded' : 'paid',
                'ref_id' => $result->refId,
                'card_pan' => $result->cardPan,
                'verify_response' => json_encode(['unanswered' => false, 'recovery' => $isBooking ? 'refunded_to_wallet' : 'wallet_charged', 'recovered_at' => now()->toIso8601String(), 'wallet_toman' => $toman] + $result->raw + $response, JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
            ]);
        });

        $user?->notify(new PaymentRefundedNotification(
            $isBooking ? 'verify_unanswered' : 'charge_recovered',
            0,
            $toman,
            Salon::find($tx->salon_id),
            $isBooking ? (int) $tx->payable_id : null,
        ));

        Log::warning('Unanswered verify recovered: the gateway had verified the payment', [
            'transaction_id' => $tx->id, 'driver' => $tx->driver, 'purpose' => $tx->purpose, 'wallet_toman' => $toman,
            'booking_status' => $isBooking ? Booking::find($tx->payable_id)?->payment_status : null,
        ]);
    }
}
