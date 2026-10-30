<?php

namespace App\Services\Payment;

use App\Models\PlatformCreditEntry;
use App\Models\Salon;
use Illuminate\Support\Facades\DB;

/**
 * ⭐ «اعتبار پلتفرم» سالن (تصمیم ۲۰۲۶-۱۰-۳۱): مبلغ قسط‌های فاکتوری که در ۷ روز کامل نشد (یا قسطی که بعد از بسته شدن فاکتور رسید) اعتبار
 * سالن می‌شود و هنگام ساخت فاکتور بعدی اشتراک/ارتقا از آن کم می‌شود. دفتر جمع‌شونده؛ مانده = جمع ردیف‌ها (تومان).
 */
class PlatformCredit
{
    public function balance(Salon|int $salon): int
    {
        return (int) PlatformCreditEntry::where('salon_id', $salon instanceof Salon ? $salon->id : $salon)->sum('amount');
    }

    public function add(Salon|int $salon, int $toman, string $reason, ?int $invoiceId = null, ?int $transactionId = null, ?string $note = null): void
    {
        if ($toman === 0) {
            return;
        }

        PlatformCreditEntry::create([
            'salon_id' => $salon instanceof Salon ? $salon->id : $salon,
            'amount' => $toman, 'reason' => $reason, 'invoice_id' => $invoiceId, 'payment_transaction_id' => $transactionId, 'note' => $note,
        ]);
    }

    /** تا سقف $toman از اعتبار برای فاکتور برمی‌دارد (ردیف سالن قفل می‌شود تا دو فاکتور هم‌زمان اعتبار را دوبار مصرف نکنند). */
    public function reserve(Salon $salon, int $toman, int $invoiceId): int
    {
        return DB::transaction(function () use ($salon, $toman, $invoiceId) {
            Salon::withoutGlobalScopes()->whereKey($salon->id)->lockForUpdate()->first();
            $take = max(0, min($toman, $this->balance($salon)));
            $this->add($salon, -$take, 'invoice_reserved', $invoiceId);

            return $take;
        });
    }
}
