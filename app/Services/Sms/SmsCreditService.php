<?php

namespace App\Services\Sms;

use App\Models\Salon;
use App\Models\SmsCreditPurchase;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * بسته‌ی پیامک و اعتبار سالن (۲۰۲۶-۰۹-۳۰). قیمت: تعداد قطعه × SMS_PART_PRICE، بدون هیچ تخفیفی (تصمیم صریح ابوالفضل).
 * اعتبار منقضی نمی‌شود و فقط بعد از تمام شدن سهمیه‌ی ماه مصرف می‌شود (SmsQuotaService::consume).
 */
class SmsCreditService
{
    public function __construct(private readonly SmsQuotaService $quota) {}

    /** @return int[] اندازه‌ی بسته‌ها بر حسب قطعه، از SMS_PACKS */
    public function packs(): array
    {
        return array_values(array_filter(array_map('intval', (array) config('billing.sms_packs')), fn ($p) => $p > 0));
    }

    public function partPrice(): int
    {
        return (int) config('billing.sms_part_price');
    }

    /** قیمت بسته به تومان — بدون تخفیف */
    public function price(int $parts): int
    {
        return $parts * $this->partPrice();
    }

    public function isPack(int $parts): bool
    {
        return in_array($parts, $this->packs(), true);
    }

    public function startOnlinePurchase(Salon $salon, int $parts, ?User $by): SmsCreditPurchase
    {
        return SmsCreditPurchase::create([
            'salon_id' => $salon->id,
            'parts' => $parts,
            'amount' => $this->price($parts),
            'source' => 'online',
            'status' => 'pending',
            'created_by' => $by?->id,
        ]);
    }

    /**
     * پرداخت تأیید شد: اعتبار اضافه می‌شود. یک‌بارمصرف: callback تکراری یا هم‌زمان (قفل ردیف) اعتبار را دو بار اضافه نمی‌کند.
     */
    public function completeOnlinePurchase(SmsCreditPurchase $purchase, string $refId): bool
    {
        return DB::transaction(function () use ($purchase, $refId) {
            $locked = SmsCreditPurchase::whereKey($purchase->id)->lockForUpdate()->first();
            if (! $locked || ! $locked->isPending()) {
                return false;
            }

            $locked->update(['status' => 'paid', 'ref_id' => $refId, 'paid_at' => now()]);
            $this->quota->addCredit($locked->salon()->withoutGlobalScopes()->firstOrFail(), $locked->parts);

            return true;
        });
    }

    public function markFailed(SmsCreditPurchase $purchase): void
    {
        SmsCreditPurchase::whereKey($purchase->id)->where('status', 'pending')->update(['status' => 'failed']);
    }

    /** اعطای دستی سوپرادمین (هدیه، جبران خطا) — در تاریخچه با مبلغ ۰ و نام اعطاکننده ثبت می‌شود */
    public function grant(Salon $salon, int $parts, User $by, ?string $note): SmsCreditPurchase
    {
        return DB::transaction(function () use ($salon, $parts, $by, $note) {
            $purchase = SmsCreditPurchase::create([
                'salon_id' => $salon->id,
                'parts' => $parts,
                'amount' => 0,
                'source' => 'grant',
                'status' => 'paid',
                'note' => $note,
                'created_by' => $by->id,
                'paid_at' => now(),
            ]);
            $this->quota->addCredit($salon, $parts);

            return $purchase;
        });
    }

    public function history(Salon $salon, int $limit = 20)
    {
        return SmsCreditPurchase::where('salon_id', $salon->id)->where('status', '!=', 'pending')
            ->latest('id')->limit($limit)->get();
    }
}
