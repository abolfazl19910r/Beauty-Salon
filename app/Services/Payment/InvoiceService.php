<?php

namespace App\Services\Payment;

use App\Models\Invoice;
use App\Models\Salon;
use App\Models\User;
use App\Services\SuperAdmin\SuperAdminService;
use Illuminate\Support\Facades\DB;

/**
 * ⭐ فاز ۲ از ۲، محور «۱. پرداخت آنلاین و صورتحساب». هر تغییر در وضعیت اشتراک یک سالن —
 * چه پرداخت آنلاین موفق، چه تمدید دستی سوپر ادمین — دقیقاً از همین سرویس عبور می‌کند تا هم
 * جدول `invoices` (تاریخچه) و هم تاریخ واقعی اشتراک سالن (SuperAdminService::renewSubscription())
 * همیشه هم‌راستا بمانند؛ نه AdminBillingController نه SuperAdminController مستقیماً این دو تا
 * را جدا صدا نمی‌زنند.
 *
 * محاسبه‌ی period_start/period_end صرفاً برای ثبت در تاریخچه است، نه منبع اصلی حقیقت — منبع
 * اصلی همیشه salons.subscription_ends_at است (که SuperAdminService::renewSubscription() آن را
 * به‌روزرسانی می‌کند)؛ اینجا فقط قبل/بعد از آن فراخوانی را برای این فاکتور خاص یادداشت می‌کنیم.
 */
class InvoiceService
{
    public function __construct(protected readonly SuperAdminService $superAdminService) {}

    /**
     * قدم اول مسیر آنلاین: یک فاکتور pending می‌سازد، پیش از رفتن به درگاه. اشتراک سالن هنوز
     * دست‌نخورده می‌ماند تا پرداخت واقعاً تأیید شود (markPaidFromGateway()).
     */
    public function createPendingOnlinePurchase(Salon $salon, string $subscriptionType, ?User $createdBy): Invoice
    {
        return Invoice::create([
            'salon_id' => $salon->id,
            'subscription_type' => $subscriptionType,
            'amount' => $this->priceFor($subscriptionType),
            'status' => 'pending',
            'payment_method' => 'online',
            'created_by' => $createdBy?->id,
        ]);
    }

    /**
     * بعد از تأیید موفق زرین‌پال صدا زده می‌شود: فاکتور را paid می‌کند و اشتراک سالن را واقعاً
     * تمدید می‌کند — این دو کار همیشه با هم و اتمیک انجام می‌شوند.
     */
    public function markPaidFromGateway(Invoice $invoice, string $refId): Invoice
    {
        return DB::transaction(function () use ($invoice, $refId) {
            $salon = Salon::lockForUpdate()->findOrFail($invoice->salon_id);
            $periodStart = $salon->subscription_ends_at?->isFuture() ? $salon->subscription_ends_at : now();

            $this->superAdminService->renewSubscription($salon, $invoice->subscription_type);

            $invoice->update([
                'status' => 'paid',
                'ref_id' => $refId,
                'paid_at' => now(),
                'period_start' => $periodStart,
                'period_end' => $salon->fresh()->subscription_ends_at,
            ]);

            return $invoice->fresh();
        });
    }

    public function markFailed(Invoice $invoice): Invoice
    {
        $invoice->update(['status' => 'failed']);

        return $invoice->fresh();
    }

    /**
     * مسیر دستی سوپر ادمین (همان تمدید فاز ۱، حالا در همین جدول هم ثبت می‌شود) — بدون درگاه،
     * فاکتور بلافاصله paid ساخته می‌شود.
     */
    public function recordManualRenewal(Salon $salon, string $subscriptionType, User $performedBy): Invoice
    {
        return DB::transaction(function () use ($salon, $subscriptionType, $performedBy) {
            $periodStart = $salon->subscription_ends_at?->isFuture() ? $salon->subscription_ends_at : now();

            $this->superAdminService->renewSubscription($salon, $subscriptionType);

            return Invoice::create([
                'salon_id' => $salon->id,
                'subscription_type' => $subscriptionType,
                'amount' => $this->priceFor($subscriptionType),
                'status' => 'paid',
                'payment_method' => 'manual',
                'ref_id' => null,
                'paid_at' => now(),
                'period_start' => $periodStart,
                'period_end' => $salon->fresh()->subscription_ends_at,
                'created_by' => $performedBy->id,
            ]);
        });
    }

    public function priceFor(string $subscriptionType): int
    {
        $price = config("billing.subscription_prices.{$subscriptionType}");

        if ($price === null) {
            throw new \InvalidArgumentException("قیمت برای نوع اشتراک نامعتبر: {$subscriptionType}");
        }

        return (int) $price;
    }
}
