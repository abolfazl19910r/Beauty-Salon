<?php

namespace App\Services\Payment;

use App\Models\Invoice;
use App\Models\Salon;
use App\Models\User;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use App\Services\SuperAdmin\SuperAdminService;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function __construct(
        protected readonly SuperAdminService $superAdminService,
        protected readonly InvoiceRepositoryInterface $invoiceRepository,
    ) {}

    public function createPendingOnlinePurchase(Salon $salon, string $subscriptionType, ?User $createdBy): Invoice
    {
        return $this->invoiceRepository->create([
            'salon_id' => $salon->id,
            'subscription_type' => $subscriptionType,
            'amount' => $this->priceFor($subscriptionType),
            'status' => 'pending',
            'payment_method' => 'online',
            'created_by' => $createdBy?->id,
        ]);
    }

    public function markPaidFromGateway(Invoice $invoice, string $refId): Invoice
    {
        return DB::transaction(function () use ($invoice, $refId) {
            $salon = Salon::lockForUpdate()->findOrFail($invoice->salon_id);
            $periodStart = $salon->subscription_ends_at?->isFuture() ? $salon->subscription_ends_at : now();

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
            $periodStart = $salon->subscription_ends_at?->isFuture() ? $salon->subscription_ends_at : now();

            $this->superAdminService->renewSubscription($salon, $subscriptionType);

            return $this->invoiceRepository->create([
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
