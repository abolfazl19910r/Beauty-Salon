<?php

namespace App\Console\Commands;

use App\Models\PaymentTransaction;
use App\Payments\Contracts\ReversibleGateway;
use App\Payments\GatewayManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * ⭐ تطبیق تراکنش‌های درگاه (مرحله‌ی ۲ چند درگاه — بانک سامان). هر ۵ دقیقه (bootstrap/app.php).
 *
 * ۱) مشتری از درگاه برنگشت: تراکنش pending بیشتر از یک ساعت → expired. هیچ درگاهی بدون اطلاعاتِ بازگشت
 *    قابل تایید نیست (سپ برای verify رسید RefNum رو می‌خواد که فقط با بازگشت مشتری می‌رسه)؛ سپ پرداختِ تاییدنشده
 *    رو بعد از ۳۰ دقیقه خودش برگشت می‌زنه. expired فقط برای صادق بودن دفتر تراکنش‌هاست و مانع تایید دیرهنگام نیست.
 *
 * ۲) سامان — مشتری برگشت ولی پاسخ VerifyTransaction نرسید (unanswered). اگه سپ در واقع تایید کرده باشه، دیگه
 *    خودش برگشت نمی‌زنه و پول مشتری بدون خدمت می‌مونه. مستند اجازه‌ی Reverse تا ۵۰ دقیقه بعد از تراکنش رو می‌ده؛
 *    ولی تا ۳۰ دقیقه مشتری هنوز می‌تونه با refresh همون صفحه تایید رو تکرار کنه. پس فقط تراکنش‌هایی که آخرین
 *    تلاش تاییدشون (updated_at ≈ زمان بازگشت از بانک) بین ۳۱ و ۴۵ دقیقه پیش بوده برگشت زده می‌شن — اول با
 *    یک update شرطی failed → reversing (تا دو اجرا یا یک تایید هم‌زمان با هم تداخل نکنن؛ GatewayReceipt::claim
 *    تراکنش reversing/reversed رو رد می‌کنه). اگه Reverse ناموفق بود (مثلاً سپ هرگز تایید نکرده بود و تراکنش
 *    رو نمی‌شناسه) دوباره failed می‌شه تا اجرای بعدی داخل همون پنجره دوباره امتحان کنه.
 *
 * ۳) ملت (به‌پرداخت) — همون الگو با پنجره‌ی خودش: پاسخ bpVerifyRequest نرسید، یا verify شد ولی نه settle جواب
 *    داد نه bpReversalRequest همون لحظه. به‌پرداخت تراکنش verify‌نشده رو بعد از ۱۵ دقیقه خودش برگشت می‌زنه و تا
 *    ۱۵ دقیقه مشتری با refresh می‌تونه verify رو تکرار کنه؛ برگشتِ تراکنشِ settle‌نشده تا ۲ ساعت مجازه. پس پنجره
 *    ۱۶ تا ۹۰ دقیقه.
 */
class ReconcilePaymentTransactions extends Command
{
    protected $signature = 'payments:reconcile {--dry-run : فقط نمایش، بدون تغییر}';

    protected $description = 'منقضی کردن تراکنش‌های رهاشده و برگشت وجه پرداخت‌های بانکی (سامان/ملت) که پاسخ تایید آن‌ها نرسید';

    public const EXPIRE_PENDING_AFTER_MINUTES = PaymentTransaction::PENDING_LIFETIME_MINUTES;

    /** بعد از این، مهلت ۳۰ دقیقه‌ای verify مشتری تمام شده. */
    public const SAMAN_REVERSE_AFTER_MINUTES = 31;

    /** قبل از این، با حاشیه‌ی امن از مهلت ۵۰ دقیقه‌ای Reverse مستند. */
    public const SAMAN_REVERSE_BEFORE_MINUTES = 45;

    /** بعد از مهلت ۱۵ دقیقه‌ای verify به‌پرداخت. */
    public const MELLAT_REVERSE_AFTER_MINUTES = 16;

    /** با حاشیه‌ی امن از سقف ۲ ساعته‌ی bpReversalRequest. */
    public const MELLAT_REVERSE_BEFORE_MINUTES = 90;

    /**
     * پارسیان: مهلت دقیق تایید/برگشت در منبع عمومی نیست؛ صفحه‌ی خود بانک می‌گه تراکنش تاییدنشده حدود ۶۰ دقیقه بعد
     * برمی‌گرده و کد ‎-1549 یعنی مهلت برگشت گذشته. ۲۰ دقیقه برای refresh مشتری، و تمام شدن پیش از ۶۰ دقیقه.
     */
    public const PARSIAN_REVERSE_AFTER_MINUTES = 20;

    public const PARSIAN_REVERSE_BEFORE_MINUTES = 50;

    /** @return array<string, array{0: int, 1: int}> driver → [از چند دقیقه بعد, تا چند دقیقه بعد] از آخرین تلاش تایید */
    public static function reverseWindows(): array
    {
        return [
            'saman' => [self::SAMAN_REVERSE_AFTER_MINUTES, self::SAMAN_REVERSE_BEFORE_MINUTES],
            'mellat' => [self::MELLAT_REVERSE_AFTER_MINUTES, self::MELLAT_REVERSE_BEFORE_MINUTES],
            'parsian' => [self::PARSIAN_REVERSE_AFTER_MINUTES, self::PARSIAN_REVERSE_BEFORE_MINUTES],
        ];
    }

    public function handle(GatewayManager $gateways): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $stale = PaymentTransaction::query()
            ->where('status', 'pending')
            ->where('created_at', '<=', now()->subMinutes(self::EXPIRE_PENDING_AFTER_MINUTES));

        $expired = $dryRun ? $stale->count() : $stale->update(['status' => 'expired', 'updated_at' => now()]);
        $this->info(($dryRun ? '[dry-run] ' : '')."تراکنش‌های رهاشده‌ی منقضی‌شده: {$expired}");

        $candidates = PaymentTransaction::query()
            ->where('status', 'failed')
            ->whereNotNull('gateway_receipt')
            ->where(function ($query) {
                foreach (self::reverseWindows() as $driver => [$after, $before]) {
                    $query->orWhere(fn ($q) => $q->where('driver', $driver)
                        ->whereBetween('updated_at', [now()->subMinutes($before), now()->subMinutes($after)]));
                }
            })
            ->get()
            ->filter(fn (PaymentTransaction $tx) => ((array) $tx->verify_response)['unanswered'] ?? false);

        $reversed = 0;

        foreach ($candidates as $tx) {
            if ($dryRun) {
                $this->line("[dry-run] برگشت وجه تراکنش #{$tx->id} (رسید {$tx->gateway_receipt})");

                continue;
            }

            // toBase(): updated_at عمداً دست نمی‌خوره — همون «زمان بازگشت مشتری» است که پنجره‌ی بالا باهاش حساب می‌شه؛
            // اگه عوض می‌شد، یک Reverse ناموفق تراکنش رو از پنجره بیرون می‌انداخت و دیگه امتحان نمی‌شد.
            if (PaymentTransaction::whereKey($tx->id)->where('status', 'failed')->toBase()->update(['status' => 'reversing']) !== 1) {
                continue; // اجرای دیگه یا یک تایید هم‌زمان زودتر رسید
            }

            $driver = $tx->gateway ? $gateways->driver($tx->gateway) : null;
            $ok = $driver instanceof ReversibleGateway && $driver->reverseTransaction($tx);
            $response = (array) $tx->verify_response;
            $response['reverse_attempts'] = (int) ($response['reverse_attempts'] ?? 0) + 1;

            PaymentTransaction::whereKey($tx->id)->toBase()->update([
                'status' => $ok ? 'reversed' : 'failed',
                'verify_response' => json_encode($response + ($ok ? ['reversed_at' => now()->toIso8601String()] : []), JSON_UNESCAPED_UNICODE),
            ]);

            if ($ok) {
                // ⭐ مشتری باید بدونه پولش به کارتش برگشت (پیامک صف‌دار)
                \App\Models\User::find($tx->user_id)?->notify(new \App\Notifications\Payment\PaymentRefundedNotification(
                    'verify_unanswered',
                    intdiv((int) $tx->amount_rial, 10),
                    0,
                    \App\Models\Salon::find($tx->salon_id),
                    $tx->purpose === 'booking' ? (int) $tx->payable_id : null,
                ));
            }

            Log::log($ok ? 'info' : 'warning', $ok ? 'Bank payment reversed after unanswered verify' : 'Bank payment reverse failed', [
                'driver' => $tx->driver, 'transaction_id' => $tx->id, 'salon_id' => $tx->salon_id, 'gateway_missing' => ! $tx->gateway,
            ]);
            $reversed += $ok ? 1 : 0;
        }

        $this->info(($dryRun ? '[dry-run] ' : '')."برگشت وجه درگاه‌های بانکی: {$reversed} از {$candidates->count()}");

        return self::SUCCESS;
    }
}
