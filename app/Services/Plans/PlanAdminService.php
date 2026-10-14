<?php

namespace App\Services\Plans;

use App\Models\Plan;
use App\Models\Salon;
use App\Models\SalonFeatureOverride;
use App\Models\User;
use App\Support\Features\Feature;
use Illuminate\Support\Facades\DB;

/**
 * کارهای سوپرادمین روی پلن‌ها و امکانات سالن‌ها (بسته‌ی پلن‌ها، ۲۰۲۶-۱۰-۱۴). هر تغییری که امکانات یک سالن را عوض می‌کند در
 * activity_log ثبت می‌شود (چه کسی، چه وقت، از چه به چه).
 */
class PlanAdminService
{
    /**
     * @param  list<string>  $features
     * @return list<string> امکاناتی که برداشته شد (برای پیام «N سالن از دست دادند»)
     */
    public function save(?Plan $plan, array $attributes, array $features, User $by): array
    {
        return DB::transaction(function () use ($plan, $attributes, $features, $by) {
            $before = $plan?->featureKeys() ?? [];
            $plan ??= new Plan;
            $plan->fill($attributes)->save();
            $plan->syncFeatures($features);
            $after = $plan->featureKeys();

            activity()->performedOn($plan)->causedBy($by)
                ->withProperties(['attributes' => $plan->only(array_keys($attributes)), 'features_before' => $before, 'features_after' => $after])
                ->log($plan->wasRecentlyCreated ? 'plan_created' : 'plan_updated');

            return array_values(array_diff($before, $after));
        });
    }

    public function salonCount(Plan $plan): int
    {
        return Salon::withoutGlobalScopes()->where(fn ($q) => $q->where('plan_id', $plan->id)->orWhere('next_plan_id', $plan->id))->count();
    }

    /** پلنی که سالن یا فاکتور دارد حذف نمی‌شود (فقط غیرفعال) */
    public function delete(Plan $plan, User $by): void
    {
        if ($this->salonCount($plan) > 0) {
            throw new \InvalidArgumentException('این پلن سالن دارد و حذف نمی‌شود؛ به‌جایش غیرفعالش کنید.');
        }
        if ($plan->is_trial) {
            throw new \InvalidArgumentException('پلن آزمایشی حذف نمی‌شود؛ برای خاموش کردن دوره‌ی آزمایشی غیرفعالش کنید.');
        }

        activity()->performedOn($plan)->causedBy($by)->withProperties(['key' => $plan->key, 'name' => $plan->name])->log('plan_deleted');
        $plan->delete();
    }

    /** تغییر دستی پلن سالن، بدون پرداخت و بدون تغییر تاریخ اشتراک؛ تغییر زمان‌بندی‌شده‌ی قبلی لغو می‌شود */
    public function assignPlan(Salon $salon, Plan $plan, User $by): void
    {
        $from = $salon->effectivePlanId();
        $salon->update(['plan_id' => $plan->id, 'next_plan_id' => null, 'next_plan_starts_at' => null]);

        activity()->performedOn($salon)->causedBy($by)
            ->withProperties(['from_plan_id' => $from, 'to_plan_id' => $plan->id, 'to_plan' => $plan->name])
            ->log('salon_plan_changed');
    }

    public function setOverride(Salon $salon, Feature $feature, bool $enabled, ?\DateTimeInterface $expiresAt, ?string $note, User $by): SalonFeatureOverride
    {
        $override = SalonFeatureOverride::updateOrCreate(
            ['salon_id' => $salon->id, 'feature' => $feature->value],
            ['enabled' => $enabled, 'expires_at' => $expiresAt, 'note' => $note, 'set_by' => $by->id],
        );

        activity()->performedOn($salon)->causedBy($by)
            ->withProperties(['feature' => $feature->value, 'enabled' => $enabled, 'expires_at' => $expiresAt?->format('Y-m-d H:i'), 'note' => $note])
            ->log('salon_feature_override_set');

        return $override;
    }

    public function removeOverride(SalonFeatureOverride $override, User $by): void
    {
        $salon = Salon::withoutGlobalScopes()->find($override->salon_id);
        $override->delete();

        if ($salon) {
            activity()->performedOn($salon)->causedBy($by)->withProperties(['feature' => $override->feature])->log('salon_feature_override_removed');
        }
    }
}
