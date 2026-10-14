<?php

namespace App\Services\Plans;

use App\Models\Plan;
use App\Models\Salon;
use App\Support\Features\Feature;
use Illuminate\Support\Facades\DB;

/**
 * پلن‌های پیش‌فرض (تصمیم ۲۰۲۶-۱۰-۱۴): آزمایشی + کامل + پایه/حرفه‌ای/کلینیک، هر پلن پولی با چهار بازه‌ی ۱/۳/۶/۱۲ ماهه.
 *
 * - «آزمایشی» و «کامل» دقیقاً رفتار امروز سالن‌ها (دوره‌ی درمانی و ارسال دستی هنوز ساخته نشده‌اند و در «کامل» نیستند؛ اضافه‌کردنشان
 *   به «کامل» بعداً تصمیم قیمتی است): قیمت، سهمیه، متخصص شامل و روزهای آزمایشی از config/billing.php (یعنی همان
 *   کلیدهای .env)، و همه‌ی امکاناتی که امروز وجود دارند. کلیدهای .env فقط مقدار اولیه‌اند؛ بعد از ساخت، منبع حقیقت جدول است.
 *   برداشتن «یادآوری پیامکی» از آزمایشی کار سوپرادمین در صفحه‌ی پلن‌هاست (تا سالن‌های در آزمایشی ناگهان چیزی از دست ندهند).
 * - «کامل» تنها پلن عمومی می‌ماند تا صفحه‌ی فروش و صورتحساب امروز عوض نشوند؛ سه پلن تازه فعال ولی غیرعمومی‌اند (سوپرادمین
 *   تخصیصشان می‌دهد یا عمومی‌شان می‌کند). قیمت بازه‌های بلندتر با همان نردبان تخفیف امروز (≈۸٪/۱۵٪/۲۳٪)، رُند به ۵۰ هزار.
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
                'features' => $existing,
            ],
            [
                'key' => Plan::FULL, 'name' => 'کامل', 'description' => 'همه‌ی امکانات امروز؛ پلن سالن‌های پیش از پلن‌بندی.',
                'is_trial' => false, 'is_public' => true, 'is_active' => true, 'sort' => 10,
                'price_1m' => (int) $prices['1m'], 'price_3m' => (int) $prices['3m'],
                'price_6m' => (int) $prices['6m'], 'price_12m' => (int) $prices['12m'],
                'included_specialists' => max(1, (int) config('billing.included_specialists', 10)),
                'extra_specialist_price_per_month' => max(0, (int) config('billing.extra_specialist_price_per_month', 0)),
                'sms_quota_per_month' => (int) config('billing.sms_quota_per_month'),
                'trial_days' => null,
                'features' => $existing,
            ],
            [
                'key' => 'basic', 'name' => 'پایه', 'description' => 'رزرو آنلاین و پیامک‌های نوبت برای سالن کوچک.',
                'is_trial' => false, 'is_public' => false, 'is_active' => true, 'sort' => 20,
                'price_1m' => 990000, 'price_3m' => 2750000, 'price_6m' => 5050000, 'price_12m' => 9150000,
                'included_specialists' => 5, 'extra_specialist_price_per_month' => 250000, 'sms_quota_per_month' => 1000,
                'trial_days' => null,
                'features' => array_values(array_diff($existing, [Feature::Winback->value, Feature::Loyalty->value, Feature::ReportsExport->value])),
            ],
            [
                'key' => 'pro', 'name' => 'حرفه‌ای', 'description' => 'همه‌ی امکانات موجود.',
                'is_trial' => false, 'is_public' => false, 'is_active' => true, 'sort' => 30,
                'price_1m' => 1500000, 'price_3m' => 4150000, 'price_6m' => 7650000, 'price_12m' => 13850000,
                'included_specialists' => 10, 'extra_specialist_price_per_month' => 250000, 'sms_quota_per_month' => 2000,
                'trial_days' => null,
                'features' => $existing,
            ],
            [
                'key' => 'clinic', 'name' => 'کلینیک', 'description' => 'حرفه‌ای + دوره‌های درمانی و ارسال دستی به مشتری‌های قدیمی.',
                'is_trial' => false, 'is_public' => true, 'is_active' => true, 'sort' => 40,
                'price_1m' => 2400000, 'price_3m' => 6650000, 'price_6m' => 12250000, 'price_12m' => 22150000,
                'included_specialists' => 15, 'extra_specialist_price_per_month' => 250000, 'sms_quota_per_month' => 4000,
                'trial_days' => null,
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
