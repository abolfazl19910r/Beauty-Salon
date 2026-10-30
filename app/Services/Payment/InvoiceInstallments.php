<?php

namespace App\Services\Payment;

use App\Models\Invoice;
use App\Models\PaymentTransaction;
use App\Models\Salon;
use App\Payments\GatewayAmountLimits;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ⭐ پرداخت قسطی فاکتور اشتراک/ارتقا (تصمیم ابوالفضل ۲۰۲۶-۱۰-۳۱، راه «ب»). زرین‌پال هر پرداخت را حداکثر ۱۰۰ میلیون تومان می‌پذیرد (‎-41).
 *
 * - prepare(): هنگام ساخت فاکتور، اعتبار پلتفرم سالن (PlatformCredit) تا سقف مبلغ رزرو و از مبلغ درگاه کم می‌شود؛ باقی‌مانده به
 *   ceil(باقی / سقف) قسط مساوی تقسیم می‌شود (قسط آخر = باقی‌مانده).
 * - هر قسط یک ردیف payment_transactions (payable = همین فاکتور، installment = شماره) و در زرین‌پال order_id = INV-{id}.
 * - recordPaid(): جمع پرداخت‌شده زیاد می‌شود؛ با آخرین قسط InvoiceService::markPaidFromGateway (اشتراک فقط یک بار فعال).
 *   اولین قسط مهلت OPEN_DAYS روزه می‌گذارد.
 * - fail(): قسط اول ناموفق ← فاکتور failed و اعتبار رزروشده برمی‌گردد؛ قسط بعدی ناموفق ← فاکتور باز می‌ماند.
 * - expire(): بعد از مهلت، فاکتور نیمه‌پرداخت failed و (قسط‌های پرداخت‌شده + اعتبار رزروشده) اعتبار پلتفرم سالن می‌شود؛ فاکتوری که
 *   هیچ قسطش نرسید (با یا بی اعتبار رزروشده) بعد از یک روز «ناموفق» می‌شود.
 * - قسطی که بعد از بسته شدن فاکتور برسد (reconcile) ← اعتبار پلتفرم.
 */
class InvoiceInstallments
{
    public const OPEN_DAYS = 7;

    /** فاکتور بی قسط پرداخت‌شده (بی پاسخ درگاه) بعد از این مدت «ناموفق» و اعتبار رزروشده‌اش آزاد می‌شود */
    public const ABANDONED_RESERVATION_HOURS = 24;

    public function __construct(
        private readonly PlatformCredit $credit,
        private readonly InvoiceService $invoices,
    ) {}

    public function prepare(Invoice $invoice): Invoice
    {
        $salon = Salon::withoutGlobalScopes()->findOrFail($invoice->salon_id);
        $credit = $this->credit->reserve($salon, (int) $invoice->amount, $invoice->id);
        $payable = max(0, (int) $invoice->amount - $credit);

        $invoice->forceFill([
            'credit_applied' => $credit,
            'installments' => max(1, (int) ceil($payable / GatewayAmountLimits::MAX_TOMAN)),
        ])->save();

        return $invoice;
    }

    /** مبلغ قسط بعدی (تومان) */
    public function nextAmount(Invoice $invoice): int
    {
        $remaining = $invoice->payableAmount() - (int) $invoice->paid_amount;
        $part = (int) ceil($invoice->payableAmount() / max(1, (int) $invoice->installments));

        return max(0, min($part, $remaining));
    }

    public function nextNumber(Invoice $invoice): int
    {
        return (int) $invoice->installments_paid + 1;
    }

    /** «قسط ۲ از ۳» یا رشته‌ی خالی برای فاکتور یک‌قسطی */
    public static function label(Invoice $invoice, ?int $number = null): string
    {
        return (int) $invoice->installments > 1
            ? 'قسط '.to_persian_num((string) ($number ?? (int) $invoice->installments_paid + 1)).' از '.to_persian_num((string) $invoice->installments)
            : '';
    }

    /** فاکتوری که اعتبار پلتفرم کاملش کرد: بی درگاه پرداخت می‌شود */
    public function settleWithCredit(Invoice $invoice): Invoice
    {
        return $this->invoices->markPaidFromGateway($invoice, 'CREDIT');
    }

    /**
     * @return 'complete'|'partial'|'late' late = فاکتور دیگر باز نیست؛ مبلغ اعتبار پلتفرم شد
     */
    public function recordPaid(Invoice $invoice, int $toman, string $refId, ?PaymentTransaction $tx = null): string
    {
        $outcome = DB::transaction(function () use ($invoice, $toman, $refId, $tx) {
            $locked = Invoice::withoutGlobalScopes()->whereKey($invoice->id)->lockForUpdate()->first();

            if (! $locked || ! $locked->isPending()) {
                $this->credit->add((int) $invoice->salon_id, $toman, 'late_payment', $invoice->id, $tx?->id, 'قسط پس از بسته شدن فاکتور');

                return 'late';
            }

            $paid = (int) $locked->paid_amount + $toman;
            $locked->forceFill([
                'paid_amount' => $paid,
                'installments_paid' => (int) $locked->installments_paid + 1,
                'ref_id' => $refId,
                'partial_expires_at' => $locked->partial_expires_at ?? now()->addDays(self::OPEN_DAYS),
            ])->save();

            return $paid >= $locked->payableAmount() ? 'complete' : 'partial';
        });

        if ($outcome === 'complete') {
            $this->invoices->markPaidFromGateway(Invoice::withoutGlobalScopes()->findOrFail($invoice->id), $refId);
        }

        return $outcome;
    }

    /** @return bool true = کل فاکتور ناموفق شد (قسط اول) */
    public function fail(Invoice $invoice): bool
    {
        if ((int) $invoice->installments_paid > 0) {
            return false;
        }

        $this->invoices->markFailed($invoice);
        $this->credit->add((int) $invoice->salon_id, (int) $invoice->credit_applied, 'invoice_released', $invoice->id);

        return true;
    }

    /** @return int تعداد فاکتورهای بسته‌شده */
    public function expire(): int
    {
        $closed = 0;

        $candidates = Invoice::withoutGlobalScopes()->where('status', 'pending')
            ->where(fn ($q) => $q->where(fn ($p) => $p->where('installments_paid', '>', 0)->where('partial_expires_at', '<=', now()))
                // بی هیچ قسط پرداخت‌شده (با یا بی اعتبار رزروشده): بعد از یک روز «ناموفق» — تصمیم ابوالفضل ۲۰۲۶-۱۰-۳۱؛ قسطی که بعد از
                // آن برسد (reconcile تا ۲۴ ساعت) late_payment اعتبار پلتفرم می‌شود
                ->orWhere(fn ($r) => $r->where('installments_paid', 0)->where('payment_method', 'online')
                    ->where('created_at', '<=', now()->subHours(self::ABANDONED_RESERVATION_HOURS))))
            ->pluck('id');

        foreach ($candidates as $id) {
            DB::transaction(function () use ($id, &$closed) {
                $invoice = Invoice::withoutGlobalScopes()->whereKey($id)->lockForUpdate()->first();
                if (! $invoice?->isPending()) {
                    return;
                }

                $invoice->forceFill(['status' => 'failed'])->save();
                $this->credit->add((int) $invoice->salon_id, (int) $invoice->paid_amount + (int) $invoice->credit_applied,
                    $invoice->installments_paid > 0 ? 'installments_expired' : 'invoice_released', $invoice->id);
                $closed++;

                Log::info('Unfinished invoice closed; paid installments became platform credit', [
                    'invoice_id' => $invoice->id, 'salon_id' => $invoice->salon_id, 'paid' => $invoice->paid_amount, 'credit' => $invoice->credit_applied,
                ]);
            });
        }

        return $closed;
    }
}
