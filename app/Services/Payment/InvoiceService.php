<?php

namespace App\Services\Payment;

use App\Models\Invoice;
use App\Models\Salon;
use App\Models\User;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use App\Repositories\Contracts\SalonRepositoryInterface;
use App\Services\SuperAdmin\SuperAdminService;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function __construct(
        protected readonly SuperAdminService $superAdminService,
        protected readonly InvoiceRepositoryInterface $invoiceRepository,
        protected readonly SalonRepositoryInterface $salonRepository,
    ) {}

    public function createPendingOnlinePurchase(Salon $salon, string $subscriptionType, ?User $createdBy): Invoice
    {
        return $this->invoiceRepository->create([
            'salon_id' => $salon->id,
            'subscription_type' => $subscriptionType,
            'amount' => $this->priceFor($subscriptionType, $salon),
            'status' => 'pending',
            'payment_method' => 'online',
            'created_by' => $createdBy?->id,
        ]);
    }

    public function markPaidFromGateway(Invoice $invoice, string $refId): Invoice
    {
        return DB::transaction(function () use ($invoice, $refId) {
            $salon = $this->salonRepository->lockForUpdateFindOrFail($invoice->salon_id);
            $periodStart = $salon->subscriptionPeriodBase();

            $this->superAdminService->renewSubscription($salon, $invoice->subscription_type);

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

    public function recordManualRenewal(Salon $salon, string $subscriptionType, User $performedBy): Invoice
    {
        return DB::transaction(function () use ($salon, $subscriptionType, $performedBy) {
            $periodStart = $salon->subscriptionPeriodBase();

            $this->superAdminService->renewSubscription($salon, $subscriptionType);

            return $this->invoiceRepository->create([
                'salon_id' => $salon->id,
                'subscription_type' => $subscriptionType,
                'amount' => $this->priceFor($subscriptionType, $salon),
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

    /** قیمت پلن + متخصص‌های بیشتر از تعداد شامل‌شده (سقف متخصص همین سالن). */
    public function priceFor(string $subscriptionType, Salon $salon): int
    {
        return app(\App\Support\Billing\SubscriptionPricing::class)->price($subscriptionType, (int) $salon->max_specialists_count);
    }
}
