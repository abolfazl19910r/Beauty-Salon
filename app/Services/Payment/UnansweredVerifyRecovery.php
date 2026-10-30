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

    public const PURPOSES = ['booking', 'wallet_charge', 'course'];

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
            // ⭐ بسته‌ی 5ز: زرین‌پال ‎-50/‎-52 — شاید پول کسر شده ← مدیر بررسی می‌کند
            if (($result->raw['needs_attention'] ?? false) === true) {
                $this->flag($tx);
            }

            return 'not_paid';
        }

        $this->credit($tx, $result->refId, $result->cardPan, $result->raw + $response, 'automatic');

        return 'credited';
    }

    /** درگاه‌هایی که تراکنش pending رهاشده‌شان پیش از expired یک verify می‌گیرد (بسته‌ی 5ز). */
    public const PENDING_DRIVERS = ['zarinpal'];

    /** پاسخ قطعی «پرداخت نشده» زرین‌پال: ‎-51 ناموفق، ‎-54 اتوریتی نامعتبر، ‎-55 تراکنش یافت نشد */
    public const ZARINPAL_NOT_PAID_CODES = [-51, -54, -55];

    /**
     * ⭐ بسته‌ی 5ز (۲۰۲۶-۱۰-۳۰): تراکنش pending زرین‌پال که مشتری از درگاه برنگشته (بعد از PENDING_LIFETIME_MINUTES). اگر
     * «اعتبارسنجی خودکار» پنل زرین‌پال روشن باشد پول کسر شده؛ پس پیش از expired یک verify با amount_rial دیتابیس:
     * - موفق/101 ← همان credit() بالا (نوبت/دوره: کل مبلغ به کیف پول؛ شارژ: همان شارژ) + پیامک؛ اشتراک/بسته‌ی پیامک (درگاه
     *   پلتفرم) ← needs_attention برای ابرمدیر؛
     * - ‎-51/‎-54/‎-55 ← expired؛ ‎-50/‎-52 ← failed + needs_attention؛
     * - بی‌پاسخ یا کد دیگر ← دوباره pending برای اجرای بعد؛ بعد از RECOVER_BEFORE (۲۴ ساعت) از ساخت ← expired + needs_attention.
     *
     * @return 'credited'|'not_paid'|'unanswered'|'attention'|'skipped'
     */
    public function recoverPending(PaymentTransaction $tx): string
    {
        if (! in_array($tx->driver, self::PENDING_DRIVERS, true)) {
            return 'skipped';
        }

        // toBase(): updated_at دست نمی‌خورد؛ claim اتمی تا callback دیرهنگام مشتری هم‌زمان تأیید نکند («در حال بررسی خودکار»)
        if (PaymentTransaction::whereKey($tx->id)->where('status', 'pending')->toBase()->update(['status' => 'reconciling']) !== 1) {
            return 'skipped';
        }

        $response = (array) $tx->verify_response;
        $response['pending_checks'] = (int) ($response['pending_checks'] ?? 0) + 1;
        $platform = in_array($tx->purpose, \App\Services\SuperAdmin\PlatformOverview::PLATFORM_PURPOSES, true);

        $driver = $platform
            ? \App\Support\PlatformZarinpal::driver()
            : ($tx->gateway ? $this->gateways->driver($tx->gateway) : null);

        $result = null;
        if ($driver) {
            try {
                $result = $driver->verify(new GatewayVerifyRequest((int) $tx->amount_rial, [], $tx->token, $tx->id));
            } catch (\Throwable $e) {
                Log::warning('Abandoned payment verify threw', ['transaction_id' => $tx->id, 'error' => $e->getMessage()]);
            }
        }

        if ($result?->success) {
            if ($platform || ! in_array($tx->purpose, self::PURPOSES, true) || ! $tx->user_id) {
                $this->closePending($tx, 'failed', ['recovery' => 'verified_after_abandon'] + $result->raw + $response, true, $result->refId, $result->cardPan);

                return 'attention';
            }

            $this->credit($tx, $result->refId, $result->cardPan, ['abandoned' => true] + $result->raw + $response, 'automatic');

            return 'credited';
        }

        $code = $result ? \App\Payments\Drivers\ZarinpalDriver::errorCode($result->raw) : null;
        $answered = $result && ($result->raw['unanswered'] ?? false) !== true && ($result->raw['verify_not_sent'] ?? false) !== true;

        if ($answered && ($result->raw['needs_attention'] ?? false) === true) {
            $this->closePending($tx, 'failed', ['recovery' => 'needs_attention', 'recovery_message' => $result->message] + $result->raw + $response, true);

            return 'attention';
        }

        if ($answered && in_array($code, self::ZARINPAL_NOT_PAID_CODES, true)) {
            $this->closePending($tx, 'expired', ['recovery' => 'not_paid', 'recovery_message' => $result->message] + $result->raw + $response, false);

            return 'not_paid';
        }

        if ($tx->created_at && $tx->created_at->lte(now()->subMinutes(\App\Console\Commands\ReconcilePaymentTransactions::RECOVER_BEFORE_MINUTES))) {
            $this->closePending($tx, 'expired', ['recovery' => 'gave_up', 'recovery_message' => $result?->message] + ($result?->raw ?? []) + $response, true);

            return 'attention';
        }

        // اجرای بعد (هر RECOVER_AFTER_MINUTES)؛ updated_at = زمان همین تلاش
        PaymentTransaction::whereKey($tx->id)->toBase()->update([
            'status' => 'pending',
            'verify_response' => json_encode($response + ['last_pending_check' => $code ?? 'unanswered'], JSON_UNESCAPED_UNICODE),
            'updated_at' => now(),
        ]);

        return 'unanswered';
    }

    private function closePending(PaymentTransaction $tx, string $status, array $response, bool $attention, ?string $refId = null, ?string $cardPan = null): void
    {
        PaymentTransaction::whereKey($tx->id)->toBase()->update(array_filter([
            'status' => $status,
            'needs_attention' => $attention,
            'ref_id' => $refId,
            'card_pan' => $cardPan,
            'verify_response' => json_encode($response, JSON_UNESCAPED_UNICODE),
            'updated_at' => now(),
        ], fn ($v) => $v !== null));

        if ($attention) {
            app(\App\Services\Admin\AttentionNotifier::class)->paymentFlagged($tx->id);
            Log::warning('Abandoned payment needs a human', ['transaction_id' => $tx->id, 'driver' => $tx->driver, 'purpose' => $tx->purpose, 'status' => $status]);
        }
    }

    /**
     * ⭐ «پرداخت‌های نیازمند بررسی» (۲۰۲۶-۰۹-۲۶): مدیر در پنل درگاه دید مبلغ از مشتری گرفته شده ولی نوبت/شارژی ثبت نشده
     * → همون کاری که بازیابی خودکار با تایید دیرهنگام می‌کنه: کل مبلغ نوبت به کیف پول مشتری (یا خود شارژ کیف پول) + پیامک.
     * فقط برای تراکنشی که پولش هنوز جایی ثبت نشده (failed / گیرکرده در reconciling یا reversing)؛ ادعای اتمی ردیف
     * جلوی دو بار واریز با دو کلیک یا هم‌زمانی با reconcile رو می‌گیره.
     */
    public function creditManually(PaymentTransaction $tx, User $by, string $note): bool
    {
        if (! in_array($tx->purpose, self::PURPOSES, true) || ! $tx->user_id) {
            return false;
        }

        $claimed = PaymentTransaction::whereKey($tx->id)->whereIn('status', ['failed', 'reconciling', 'reversing'])
            ->toBase()->update(['status' => 'reconciling', 'needs_attention' => false]);
        if ($claimed !== 1) {
            return false;
        }

        $this->credit($tx, $tx->ref_id, $tx->card_pan, (array) $tx->verify_response + [
            'manual_resolution' => ['action' => 'wallet_credit', 'by' => $by->id, 'by_name' => $by->name, 'at' => now()->toIso8601String(), 'note' => $note],
        ], 'manual');

        return true;
    }

    private function flag(PaymentTransaction $tx): void
    {
        PaymentTransaction::whereKey($tx->id)->toBase()->update(['needs_attention' => true]);
        app(\App\Services\Admin\AttentionNotifier::class)->paymentFlagged($tx->id);
    }

    private function release(PaymentTransaction $tx, array $response): void
    {
        PaymentTransaction::whereKey($tx->id)->toBase()->update([
            'status' => 'failed',
            'verify_response' => json_encode($response, JSON_UNESCAPED_UNICODE),
        ]);
    }

    private function credit(PaymentTransaction $tx, ?string $refId, ?string $cardPan, array $response, string $via): void
    {
        $user = User::find($tx->user_id);
        // نوبت و دوره: پول تأییدشده ولی ثبت‌نشده → کل مبلغ به کیف پول؛ شارژ: همان شارژ
        $isBooking = in_array($tx->purpose, ['booking', 'course'], true);
        $bookingId = $tx->purpose === 'booking' ? (int) $tx->payable_id : null;
        // نوبت: کل مبلغی که مشتری داد (با کارمزد) برمی‌گرده. شارژ: همون مبلغ شارژ (مثل callback موفق)، بدون کارمزد درگاه.
        $toman = $isBooking ? intdiv((int) $tx->amount_rial, 10) : intdiv((int) $tx->amount_rial - (int) $tx->fee_rial, 10);

        DB::transaction(function () use ($tx, $refId, $cardPan, $response, $user, $isBooking, $bookingId, $toman, $via) {
            if ($user && $toman > 0) {
                $wallet = $user->getOrCreateWallet();
                if ($isBooking) {
                    $wallet->addRefund($toman, $bookingId, ($bookingId ? 'بازگشت وجه نوبت #'.$bookingId : 'بازگشت وجه پرداخت دوره').($via === 'manual' ? ' — ثبت دستی مدیر پس از بررسی درگاه' : ' — تایید پرداخت با تأخیر از درگاه رسید'),
                        $bookingId ? [] : ['customer_course_id' => (int) $tx->payable_id]);
                } else {
                    $wallet->increment('balance', $toman);
                    $wallet->increment('total_deposited', $toman);
                    $wallet->transactions()->create([
                        'type' => 'deposit',
                        'amount' => $toman,
                        'balance_after' => $wallet->fresh()->balance,
                        'description' => 'شارژ کیف پول ('.($via === 'manual' ? 'ثبت دستی مدیر' : 'تایید با تأخیر').') - کد پیگیری: '.($refId ?? 'نامشخص'),
                        'metadata' => ['payment_method' => 'gateway', 'gateway_ref' => $refId, 'gateway' => $tx->driver, 'payment_transaction_id' => $tx->id],
                    ]);
                }
            }

            PaymentTransaction::whereKey($tx->id)->toBase()->update([
                'status' => $isBooking ? 'refunded' : 'paid',
                'ref_id' => $refId,
                'card_pan' => $cardPan,
                'needs_attention' => false,
                'verify_response' => json_encode(['unanswered' => false, 'recovery' => $isBooking ? 'refunded_to_wallet' : 'wallet_charged', 'recovery_via' => $via, 'recovered_at' => now()->toIso8601String(), 'wallet_toman' => $toman] + $response, JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
            ]);
        });

        $user?->notify(new PaymentRefundedNotification(
            $isBooking ? 'verify_unanswered' : 'charge_recovered',
            0,
            $toman,
            Salon::find($tx->salon_id),
            $bookingId,
        ));

        Log::warning('Unanswered payment credited to the customer wallet', [
            'via' => $via, 'transaction_id' => $tx->id, 'driver' => $tx->driver, 'purpose' => $tx->purpose, 'wallet_toman' => $toman,
            'booking_status' => $bookingId ? Booking::find($bookingId)?->payment_status : null,
        ]);
    }
}
