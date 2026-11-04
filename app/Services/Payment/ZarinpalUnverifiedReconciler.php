<?php

namespace App\Services\Payment;

use App\Console\Commands\ReconcilePaymentTransactions;
use App\Models\PaymentTransaction;
use App\Models\SalonPaymentGateway;
use App\Payments\Drivers\ZarinpalDriver;
use App\Payments\GatewayManager;
use App\Services\SuperAdmin\PlatformOverview;
use App\Support\PlatformZarinpal;
use App\Support\ZarinpalMerchant;
use Illuminate\Support\Facades\Log;

/**
 * ⭐ بسته‌ی 5ح (۲۰۲۶-۱۱-۰۴): تطبیق ساعتی با `unVerified` زرین‌پال (دستور `payments:zarinpal-unverified`).
 *
 * برای هر مرچنت (درگاه‌های زرین‌پال سالن‌ها، فعال یا غیرفعال — پرداختی که با درگاهِ بعداً خاموش‌شده انجام شده هم پول دارد — و مرچنت
 * پلتفرم) فهرست پرداخت‌های موفقِ verifyنشده خوانده می‌شود و هر اتوریتی با `payment_transactions.token` تطبیق می‌خورد:
 * - تراکنش ما با وضعیت expired/failed (پول هنوز پیش زرین‌پال) ← UnansweredVerifyRecovery::recoverUnverified (۷ روز اخیر: verify و
 *   واریز خودکار؛ قدیمی‌تر یا مبلغ ناهمخوان: فقط «نیازمند بررسی»)؛
 * - pending/reconciling یا failedِ بی‌پاسخی که هنوز در بازه‌ی ۲۴ ساعته‌ی payments:reconcile است ← همان‌جا رسیدگی می‌شود (این‌جا رد)؛
 * - paid/refunded/… ← کاری نیست؛
 * - اتوریتی ناشناس (یا متعلق به سالن/پلتفرم دیگر) ← فقط لاگ (تصمیم ابوالفضل؛ بدون اعلان).
 */
class ZarinpalUnverifiedReconciler
{
    public function __construct(
        private readonly GatewayManager $gateways,
        private readonly UnansweredVerifyRecovery $recovery,
    ) {}

    /**
     * @param  callable(string): void|null  $line  خروجی dry-run
     * @return array{merchants: int, failed_calls: int, listed: int, unknown: int, ignored: int, outcomes: array<string, int>}
     */
    public function run(bool $dryRun = false, ?callable $line = null): array
    {
        $stats = ['merchants' => 0, 'failed_calls' => 0, 'listed' => 0, 'unknown' => 0, 'ignored' => 0, 'outcomes' => []];

        foreach ($this->merchants() as $merchant) {
            $stats['merchants']++;
            $list = $merchant['driver']->unverified();

            if (! $list['ok']) {
                $stats['failed_calls']++;
                Log::warning('Zarinpal unVerified call failed', [
                    'code' => $list['code'], 'error' => $list['error'] ?? null, 'salon_ids' => $merchant['salon_ids'], 'platform' => $merchant['platform'],
                ]);

                continue;
            }

            foreach ($list['authorities'] as $item) {
                $stats['listed']++;
                $tx = PaymentTransaction::query()->where('driver', 'zarinpal')->where('token', $item['authority'])->first();

                if (! $tx || ! $this->belongsTo($tx, $merchant)) {
                    $stats['unknown']++;
                    Log::info('Zarinpal unVerified lists an authority we do not own', [
                        'authority' => $item['authority'], 'amount_rial' => $item['amount'], 'date' => $item['date'],
                        'salon_ids' => $merchant['salon_ids'], 'platform' => $merchant['platform'], 'transaction_id' => $tx?->id,
                    ]);

                    continue;
                }

                if (! in_array($tx->status, UnansweredVerifyRecovery::UNVERIFIED_CLOSED_STATUSES, true) || $this->handledByReconcile($tx)) {
                    $stats['ignored']++;

                    continue;
                }

                if ($dryRun) {
                    $line && $line("[dry-run] پرداخت تأییدنشده در زرین‌پال: تراکنش #{$tx->id} ({$tx->status}، {$tx->purpose}، {$item['amount']} ریال)");

                    continue;
                }

                $outcome = $this->recovery->recoverUnverified($tx, $item['amount']);
                $stats['outcomes'][$outcome] = ($stats['outcomes'][$outcome] ?? 0) + 1;
            }
        }

        return $stats;
    }

    /**
     * مرچنت‌های یکتا: [driver, salon_ids, platform]. مرچنت نامعتبر (جای‌نگهدار) کنار می‌رود.
     *
     * @return list<array{driver: ZarinpalDriver, salon_ids: list<int>, platform: bool}>
     */
    private function merchants(): array
    {
        $groups = [];

        foreach (SalonPaymentGateway::query()->where('driver', 'zarinpal')->orderBy('id')->get() as $gateway) {
            $key = ZarinpalMerchant::validOrNull((string) (((array) $gateway->credentials)['merchant_id'] ?? ''));
            if ($key === null) {
                continue;
            }

            $groups[$key] ??= ['driver' => $this->gateways->driver($gateway), 'salon_ids' => [], 'platform' => false];
            $groups[$key]['salon_ids'][] = (int) $gateway->salon_id;
        }

        if ($platform = PlatformZarinpal::merchantId()) {
            $groups[$platform] ??= ['driver' => PlatformZarinpal::driver(), 'salon_ids' => [], 'platform' => false];
            $groups[$platform]['platform'] = true;
        }

        return array_values(array_map(function (array $g) {
            $g['salon_ids'] = array_values(array_unique($g['salon_ids']));

            return $g;
        }, $groups));
    }

    /** پرداخت سالن فقط با مرچنت همان سالن، و اشتراک/بسته‌ی پیامک فقط با مرچنت پلتفرم. */
    private function belongsTo(PaymentTransaction $tx, array $merchant): bool
    {
        if (in_array($tx->purpose, PlatformOverview::PLATFORM_PURPOSES, true)) {
            return $merchant['platform'];
        }

        return in_array((int) $tx->salon_id, $merchant['salon_ids'], true);
    }

    /** failedِ «بی‌پاسخ» تا ۲۴ ساعت از آخرین تلاش، کار recoverIndirect در payments:reconcile است. */
    private function handledByReconcile(PaymentTransaction $tx): bool
    {
        return $tx->status === 'failed'
            && (((array) $tx->verify_response)['unanswered'] ?? false)
            && $tx->updated_at
            && $tx->updated_at->gte(now()->subMinutes(ReconcilePaymentTransactions::RECOVER_BEFORE_MINUTES));
    }
}
