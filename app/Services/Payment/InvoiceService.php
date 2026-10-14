<?php

namespace App\Services\Payment;

use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Salon;
use App\Models\User;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use App\Repositories\Contracts\SalonRepositoryInterface;
use App\Services\Plans\PlanBilling;
use App\Services\SuperAdmin\SuperAdminService;
use App\Support\Billing\SubscriptionPricing;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function __construct(
        protected readonly SuperAdminService $superAdminService,
        protected readonly InvoiceRepositoryInterface $invoiceRepository,
        protected readonly SalonRepositoryInterface $salonRepository,
    ) {}

    /**
     * فاکتور خرید/تمدید. $plan null = پلن پیش‌فرض خرید سالن (PlanBilling::defaultPurchasePlan)؛ پلن و snapshot آن روی فاکتور ثبت
     * می‌شود تا تغییر بعدی قیمت/امکانات پلن فاکتورهای قبلی را عوض نکند (بسته‌ی پلن‌ها، ۲۰۲۶-۱۰-۱۴).
     */
    public function createPendingOnlinePurchase(Salon $salon, string $subscriptionType, ?User $createdBy, ?Plan $plan = null): Invoice
    {
        $plan ??= app(PlanBilling::class)->defaultPurchasePlan($salon);

        return $this->invoiceRepository->create([
            'salon_id' => $salon->id,
            'subscription_type' => $subscriptionType,
            'plan_id' => $plan?->id,
            'kind' => 'renewal',
            'plan_snapshot' => $plan?->snapshot(),
            'amount' => $this->priceFor($subscriptionType, $salon, $plan),
            'status' => 'pending',
            'payment_method' => 'online',
            'created_by' => $createdBy?->id,
        ]);
    }

    /** فاکتور مابه‌التفاوت ارتقای وسط دوره؛ مبلغ از SubscriptionPricing::upgradePrice در همین لحظه */
    public function createPendingUpgrade(Salon $salon, Plan $to, ?User $createdBy): Invoice
    {
        return $this->invoiceRepository->create([
            'salon_id' => $salon->id,
            'subscription_type' => $salon->subscription_type,
            'plan_id' => $to->id,
            'kind' => 'upgrade',
            'plan_snapshot' => $to->snapshot() + ['from_plan_id' => $salon->effectivePlanId(), 'remaining_days' => app(SubscriptionPricing::class)->remainingDays($salon)],
            'amount' => app(SubscriptionPricing::class)->upgradePrice($salon, $to),
            'status' => 'pending',
            'payment_method' => 'online',
            'created_by' => $createdBy?->id,
        ]);
    }

    public function markPaidFromGateway(Invoice $invoice, string $refId): Invoice
    {
        return DB::transaction(function () use ($invoice, $refId) {
            $salon = $this->salonRepository->lockForUpdateFindOrFail($invoice->salon_id);

            if ($invoice->kind === 'upgrade') {
                // ارتقا: پلن همین لحظه عوض می‌شود، پایان دوره ثابت
                $salon->update(['plan_id' => $invoice->plan_id, 'next_plan_id' => null, 'next_plan_starts_at' => null]);

                $invoice = $this->invoiceRepository->update($invoice, [
                    'status' => 'paid',
                    'ref_id' => $refId,
                    'paid_at' => now(),
                    'period_start' => now(),
                    'period_end' => $salon->subscription_ends_at,
                ]);

                return $invoice->fresh();
            }

            $periodStart = $salon->subscriptionPeriodBase();

            $this->superAdminService->renewSubscription($salon, $invoice->subscription_type, $invoice->plan_id ? Plan::find($invoice->plan_id) : null);

            $invoice = $this->invoiceRepository->update($invoice, [
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
        $invoice = $this->invoiceRepository->update($invoice, ['status' => 'failed']);

        return $invoice->fresh();
    }

    /** تمدید دستی سوپرادمین: همان پلنی که سالن در پایان دوره رویش است (تغییر پلن از ویرایش سالن) */
    public function recordManualRenewal(Salon $salon, string $subscriptionType, User $performedBy): Invoice
    {
        return DB::transaction(function () use ($salon, $subscriptionType, $performedBy) {
            $periodStart = $salon->subscriptionPeriodBase();
            $plan = app(PlanBilling::class)->planAtEnd($salon);
            $plan = $plan && ! $plan->is_trial ? $plan : app(PlanBilling::class)->defaultPurchasePlan($salon);
            $amount = $this->priceFor($subscriptionType, $salon, $plan);

            $this->superAdminService->renewSubscription($salon, $subscriptionType, $plan);

            return $this->invoiceRepository->create([
                'salon_id' => $salon->id,
                'subscription_type' => $subscriptionType,
                'plan_id' => $plan?->id,
                'kind' => 'renewal',
                'plan_snapshot' => $plan?->snapshot(),
                'amount' => $amount,
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

    /** قیمت پلن + متخصص‌های بیشتر از تعداد شامل‌شده (سقف متخصص همین سالن). $plan null = قیمت‌های config (پیش از پلن‌ها). */
    public function priceFor(string $subscriptionType, Salon $salon, ?Plan $plan = null): int
    {
        return app(SubscriptionPricing::class)->price($subscriptionType, (int) $salon->max_specialists_count, $plan);
    }
}
