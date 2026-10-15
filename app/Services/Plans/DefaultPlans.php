<?php

namespace App\Services\Plans;

use App\Models\Plan;
use App\Models\Salon;
use App\Support\Features\Feature;
use Illuminate\Support\Facades\DB;

/**
 * پلن‌های پیش‌فرض (تصمیم ۲۰۲۶-۱۰-۱۴): آزمایشی + کامل + پایه/حرفه‌ای/کلینیک، هر پلن پولی با چهار بازه‌ی ۱/۳/۶/۱۲ ماهه.
 *
 * - «آزمایشی» رفتار امروز سالن تازه: سهمیه و روزهای آزمایشی از config/billing.php (کلیدهای .env فقط مقدار اولیه‌اند؛ بعد از ساخت،
 *   منبع حقیقت جدول است). برداشتن «یادآوری پیامکی» از آزمایشی کار سوپرادمین در صفحه‌ی پلن‌هاست.
 * - ⭐ بسته‌ی «و» (۲۰۲۶-۱۰-۱۵): چهار پلن عمومی و متفاوت، به ترتیب پایه ← حرفه‌ای ← کامل ← کلینیک:
 *   پایه (۵ متخصص، ۱٬۰۰۰ پیامک، بی باشگاه/بازگشت مشتری/خروجی گزارش) · حرفه‌ای (۱۰ متخصص، ۲٬۰۰۰ پیامک، همه‌ی امکانات اصلی) ·
 *   کامل (حرفه‌ای + ارسال دستی به مشتری‌های قدیمی، ۱۲ متخصص، ۳٬۰۰۰ پیامک، ۱٫۹ میلیون) · کلینیک (کامل + دوره‌های درمانی، ۱۵ متخصص،
 *   ۴٬۰۰۰ پیامک). قالب‌ها: چند چیدمان × چند پالت در هر بازه (Plan::siteThemeGrid). قیمت بازه‌های بلندتر با نردبان ≈۸٪/۱۵٪/۲۳٪.
 *   پلن‌هایی که پیش از این بسته ساخته شده‌اند را migration 2026_10_15_000004 (فقط اگر هنوز پیش‌فرض‌اند) به همین جدول می‌رساند.
 *
 * sync() فقط پلن‌هایی را که با این key وجود ندارند می‌سازد (هرگز پلن ویرایش‌شده را بازنویسی نمی‌کند) و سالن‌های بی‌پلن را وصل می‌کند.
 */
class DefaultPlans
{
    /** @return list<array<string, mixed>> */
    public static function definitions(): array
    {
        $current = Feature::keys();
        $notYet = [Feature::WinbackManual->value, Feature::TreatmentCourses->value];
        $existing = array_values(array_diff($current, $notYet));
        $prices = (array) config('billing.subscription_prices');

        return [
            [
                'key' => Plan::TRIAL, 'name' => 'آزمایشی', 'description' => 'دوره‌ی آزمایشی رایگان سالن تازه.',
                'is_trial' => true, 'is_public' => false, 'is_active' => true, 'sort' => 0,
                'price_1m' => null, 'price_3m' => null, 'price_6m' => null, 'price_12m' => null,
                'included_specialists' => max(1, (int) config('billing.included_specialists', 10)),
                'extra_specialist_price_per_month' => 0,
                'sms_quota_per_month' => (int) config('billing.trial_sms_quota'),
                'trial_days' => max(0, (int) config('billing.trial_days', 0)),
                'theme_layouts' => 0, 'theme_palettes' => 0, 'theme_layouts_6m' => 0, 'theme_palettes_6m' => 0, 'theme_layouts_12m' => 0, 'theme_palettes_12m' => 0, 'theme_admin_count' => 0,
                'features' => $existing,
            ],
            [
                'key' => Plan::FULL, 'name' => 'کامل', 'description' => 'حرفه‌ای + ارسال دستی به مشتری‌های قدیمی، ۱۲ متخصص و ۳٬۰۰۰ پیامک در ماه.',
                'is_trial' => false, 'is_public' => true, 'is_active' => true, 'sort' => 35,
                'price_1m' => 1900000, 'price_3m' => 5250000, 'price_6m' => 9700000, 'price_12m' => 17550000,
                'included_specialists' => 12,
                'extra_specialist_price_per_month' => max(0, (int) config('billing.extra_specialist_price_per_month', 0)),
                'sms_quota_per_month' => 3000,
                'trial_days' => null,
                'theme_layouts' => 4, 'theme_palettes' => 2, 'theme_layouts_6m' => 4, 'theme_palettes_6m' => 3, 'theme_layouts_12m' => 4, 'theme_palettes_12m' => 4, 'theme_admin_count' => 2,
                'features' => array_values(array_merge($existing, [Feature::WinbackManual->value])),
            ],
            [
                'key' => 'basic', 'name' => 'پایه', 'description' => 'رزرو آنلاین و پیامک‌های نوبت برای سالن کوچک.',
                'is_trial' => false, 'is_public' => true, 'is_active' => true, 'sort' => 20,
                'price_1m' => 990000, 'price_3m' => 2750000, 'price_6m' => 5050000, 'price_12m' => 9150000,
                'included_specialists' => 5, 'extra_specialist_price_per_month' => 250000, 'sms_quota_per_month' => 1000,
                'trial_days' => null,
                'theme_layouts' => 0, 'theme_palettes' => 0, 'theme_layouts_6m' => 1, 'theme_palettes_6m' => 1, 'theme_layouts_12m' => 2, 'theme_palettes_12m' => 1, 'theme_admin_count' => 0,
                'features' => array_values(array_diff($existing, [Feature::Winback->value, Feature::Loyalty->value, Feature::ReportsExport->value])),
            ],
            [
                'key' => 'pro', 'name' => 'حرفه‌ای', 'description' => 'همه‌ی امکانات اصلی سالن، با ۱۰ متخصص و ۲٬۰۰۰ پیامک در ماه.',
                'is_trial' => false, 'is_public' => true, 'is_active' => true, 'sort' => 30,
                'price_1m' => 1500000, 'price_3m' => 4150000, 'price_6m' => 7650000, 'price_12m' => 13850000,
                'included_specialists' => 10, 'extra_specialist_price_per_month' => 250000, 'sms_quota_per_month' => 2000,
                'trial_days' => null,
                'theme_layouts' => 2, 'theme_palettes' => 2, 'theme_layouts_6m' => 3, 'theme_palettes_6m' => 2, 'theme_layouts_12m' => 4, 'theme_palettes_12m' => 2, 'theme_admin_count' => 1,
                'features' => $existing,
            ],
            [
                'key' => 'clinic', 'name' => 'کلینیک', 'description' => 'کامل + دوره‌های درمانی چندجلسه‌ای، ۱۵ متخصص و ۴٬۰۰۰ پیامک در ماه.',
                'is_trial' => false, 'is_public' => true, 'is_active' => true, 'sort' => 40,
                'price_1m' => 2400000, 'price_3m' => 6650000, 'price_6m' => 12250000, 'price_12m' => 22150000,
                'included_specialists' => 15, 'extra_specialist_price_per_month' => 250000, 'sms_quota_per_month' => 4000,
                'trial_days' => null,
                'theme_layouts' => 4, 'theme_palettes' => 4, 'theme_layouts_6m' => 4, 'theme_palettes_6m' => 4, 'theme_layouts_12m' => 4, 'theme_palettes_12m' => 4, 'theme_admin_count' => 3,
                'features' => $current,
            ],
        ];
    }

    /** @return array{created: list<string>, assigned_trial: int, assigned_full: int} */
    public function sync(): array
    {
        $created = [];

        DB::transaction(function () use (&$created) {
            foreach (self::definitions() as $definition) {
                if (Plan::byKey($definition['key'])) {
                    continue;
                }
                $features = $definition['features'];
                unset($definition['features']);
                Plan::create($definition)->syncFeatures($features);
                $created[] = $definition['key'];
            }
        });

        $trial = Plan::byKey(Plan::TRIAL);
        $full = Plan::byKey(Plan::FULL);
        $assigned = ['trial' => 0, 'full' => 0];

        Salon::withoutGlobalScopes()->whereNull('plan_id')->orderBy('id')->each(function (Salon $salon) use ($trial, $full, &$assigned) {
            $plan = $salon->isOnTrial() ? $trial : $full;
            if ($plan) {
                $attributes = ['plan_id' => $plan->id];
                // سهمیه‌ی آزمایشیِ قدیمی (override برابر عدد آزمایشی) حالا از خود پلن آزمایشی می‌آید — همان عدد
                if ($plan->is_trial && $salon->isTrialSmsQuotaInEffect() && (int) $salon->sms_quota_per_month === (int) $plan->sms_quota_per_month) {
                    $attributes['sms_quota_per_month'] = null;
                }
                $salon->update($attributes);
                $assigned[$plan->is_trial ? 'trial' : 'full']++;
            }
        });

        return ['created' => $created, 'assigned_trial' => $assigned['trial'], 'assigned_full' => $assigned['full']];
    }
}
