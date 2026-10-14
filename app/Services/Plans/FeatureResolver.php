<?php

namespace App\Services\Plans;

use App\Models\PlanFeature;
use App\Models\Salon;
use App\Models\SalonFeatureOverride;
use App\Support\Features\Feature;
use Illuminate\Support\Facades\Cache;

/**
 * تنها جایی که «این سالن این امکان را دارد؟» حساب می‌شود (بسته‌ی الف، ۲۰۲۶-۱۰-۱۴):
 * استثنای دستی فعال و منقضی‌نشده (اگر هست) ← وگرنه امکانات پلنِ مؤثر سالن (Salon::effectivePlanId) ← سالن بی‌پلن = همه‌ی امکانات
 * (رفتار پیش از پلن‌ها). اشتراک منقضی/تعلیق مثل قبل در middlewareها همه‌چیز را می‌بندد؛ این‌جا به آن کاری ندارد.
 *
 * نتیجه برای هر سالن کش می‌شود (salon_features:{id}) و با تغییر پلن/استثنا/امکانات پلن پاک می‌شود. چون استثنا تاریخ انقضا دارد و
 * پلن بعدی از تاریخی شروع می‌شود، هر ردیف کش «تا کی معتبر است» را هم نگه می‌دارد.
 */
class FeatureResolver
{
    private const CACHE_PREFIX = 'salon_features:';

    private const MAX_TTL = 3600;

    public function enabled(Salon|int|null $salon, Feature $feature): bool
    {
        if ($salon === null) {
            return true;
        }

        return in_array($feature->value, $this->enabledKeys($salon), true);
    }

    /** @return list<string> */
    public function enabledKeys(Salon|int $salon): array
    {
        $salonId = $salon instanceof Salon ? (int) $salon->id : $salon;
        $cached = Cache::get(self::CACHE_PREFIX.$salonId);

        if (is_array($cached) && ($cached['until'] ?? 0) > now()->getTimestamp()) {
            return $cached['keys'];
        }

        $model = $salon instanceof Salon ? $salon : Salon::withoutGlobalScopes()->find($salonId);
        if (! $model) {
            return Feature::keys(); // سالن ناشناخته هیچ پیامک/امکانی را بی‌صدا قطع نمی‌کند (مثل SmsQuotaService)
        }

        [$keys, $until] = $this->compute($model);
        Cache::put(self::CACHE_PREFIX.$salonId, ['keys' => $keys, 'until' => $until], max(1, $until - now()->getTimestamp()));

        return $keys;
    }

    public function forget(int $salonId): void
    {
        Cache::forget(self::CACHE_PREFIX.$salonId);
    }

    public function forgetPlan(int $planId): void
    {
        Salon::withoutGlobalScopes()
            ->where(fn ($q) => $q->where('plan_id', $planId)->orWhere('next_plan_id', $planId))
            ->pluck('id')
            ->each(fn ($id) => $this->forget((int) $id));
    }

    /** @return array{0: list<string>, 1: int} امکانات روشن و timestamp پایان اعتبار */
    private function compute(Salon $salon): array
    {
        $until = now()->getTimestamp() + self::MAX_TTL;

        if ($salon->next_plan_id && $salon->next_plan_starts_at?->isFuture()) {
            $until = min($until, $salon->next_plan_starts_at->getTimestamp());
        }

        $planId = $salon->effectivePlanId();
        $keys = $planId === null
            ? Feature::keys()
            : PlanFeature::query()->where('plan_id', $planId)->pluck('feature')->all();

        $overrides = SalonFeatureOverride::query()->where('salon_id', $salon->id)->inEffect()->get();
        foreach ($overrides as $override) {
            if ($override->expires_at) {
                $until = min($until, $override->expires_at->getTimestamp());
            }
            $keys = $override->enabled
                ? array_merge($keys, [$override->feature])
                : array_diff($keys, [$override->feature]);
        }

        return [array_values(array_intersect(Feature::keys(), $keys)), $until];
    }
}
