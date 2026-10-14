<?php

namespace App\Services\Plans;

use App\Models\Plan;
use App\Models\Salon;
use App\Support\Billing\SubscriptionPricing;
use Illuminate\Support\Collection;

/**
 * قواعد خرید پلن در صفحه‌ی صورتحساب سالن (بسته‌ی الف، تصمیم‌های ۲۰۲۶-۱۰-۱۴):
 * - پلن غیرفعال یا غیرعمومی به سالن فروخته نمی‌شود، جز همان پلنی که سالن رویش است (تمدید با قیمت فعلی همان پلن). سوپرادمین هر پلنی
 *   را دستی تخصیص می‌دهد.
 * - در آزمایشی یا بعد از انقضا، پلن خریده‌شده همان لحظه اثر می‌کند؛ وسط اشتراک فعال از پایان دوره‌ی فعلی (تنزل/تغییر، بدون برگشت پول).
 *   فقط یک تغییر زمان‌بندی‌شده در هر زمان.
 * - ارتقا (پلن گران‌تر) وسط اشتراک فعال: فوری با پرداخت مابه‌التفاوت روزهای مانده (SubscriptionPricing::upgradePrice)، پایان دوره ثابت.
 */
class PlanBilling
{
    public function __construct(private readonly SubscriptionPricing $pricing) {}

    /** پلن زمان‌بندی‌شده‌ای که هنوز شروع نشده */
    public function scheduledPlan(Salon $salon): ?Plan
    {
        return $salon->next_plan_id && $salon->next_plan_starts_at?->isFuture() ? $salon->nextPlan : null;
    }

    /** پلنی که سالن در پایان دوره‌های پرداخت‌شده رویش خواهد بود */
    public function planAtEnd(Salon $salon): ?Plan
    {
        return $this->scheduledPlan($salon) ?? $salon->effectivePlan();
    }

    public function isMidTerm(Salon $salon): bool
    {
        return ! $salon->isOnTrial() && (bool) $salon->subscription_ends_at?->isFuture();
    }

    /** @return Collection<int, Plan> پلن‌هایی که این سالن در صفحه‌ی صورتحساب می‌تواند بخرد */
    public function purchasablePlans(Salon $salon): Collection
    {
        $plans = Plan::query()->offered()->with('features')->get();
        $own = $this->planAtEnd($salon);

        if ($own && ! $own->is_trial && $own->isSellable() && ! $plans->contains('id', $own->id)) {
            $plans->prepend($own->loadMissing('features'));
        }

        return $plans->filter(fn (Plan $p) => $p->isSellable())->values();
    }

    /** پلن پیش‌فرض خرید: همان پلن فعلی اگر فروختنی است، وگرنه اولین پلن عمومی؛ null = سالن پیش از پلن‌ها (قیمت config) */
    public function defaultPurchasePlan(Salon $salon): ?Plan
    {
        $own = $this->planAtEnd($salon);
        if ($own && ! $own->is_trial && $own->isSellable()) {
            return $own;
        }

        return Plan::query()->offered()->get()->first(fn (Plan $p) => $p->isSellable());
    }

    /** null = مجاز؛ وگرنه پیام خطا برای مدیر */
    public function purchaseError(Salon $salon, Plan $plan, string $subscriptionType): ?string
    {
        if ($plan->is_trial || ! $this->pricing->sells($subscriptionType, $plan)) {
            return 'این بازه برای این پلن فروخته نمی‌شود.';
        }

        if (! $this->purchasablePlans($salon)->contains('id', $plan->id)) {
            return 'این پلن دیگر فروخته نمی‌شود.';
        }

        $scheduled = $this->scheduledPlan($salon);
        if ($this->isMidTerm($salon) && $scheduled && $scheduled->id !== $plan->id) {
            return sprintf('تغییر به پلن «%s» از قبل زمان‌بندی شده است؛ فعلاً فقط تمدید همان پلن ممکن است.', $scheduled->name);
        }

        return null;
    }

    /** null = ارتقا مجاز؛ وگرنه پیام خطا */
    public function upgradeError(Salon $salon, Plan $to): ?string
    {
        if (! $this->isMidTerm($salon) || $salon->is_suspended) {
            return 'ارتقای وسط دوره فقط برای اشتراک پولی فعال است؛ پلن را مستقیم بخرید.';
        }
        if ($this->scheduledPlan($salon)) {
            return 'یک تغییر پلن از قبل زمان‌بندی شده است.';
        }
        if ($to->is_trial || ! $to->is_active || ! $to->isSellable() || $to->id === $salon->effectivePlanId()) {
            return 'این پلن برای ارتقا در دسترس نیست.';
        }
        if (! $this->purchasablePlans($salon)->contains('id', $to->id)) {
            return 'این پلن دیگر فروخته نمی‌شود.';
        }
        if ($this->pricing->upgradePrice($salon, $to) <= 0) {
            return 'این پلن گران‌تر از پلن فعلی نیست؛ تغییر به آن از تمدید بعد ممکن است.';
        }

        return null;
    }
}
