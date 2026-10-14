<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * ⭐ صفحه‌ی اصلی/فروش دامنه‌ی مرکزی (۲۰۲۶-۰۹-۲۳) — جایگزین central/placeholder.blade.php. کسی که
 * هنوز آدرس هیچ سالنی رو نداره (http://127.0.0.1:8000/ یا rasta-app.test) اینجا امکانات،
 * پیش‌نمایش پنل‌ها و پلن‌ها رو می‌بینه و به salon-signup.create می‌ره.
 *
 * همه‌ی اعداد از همون منابع واقعی خونده می‌شن (config/billing.php) — هیچ قیمت/سقفی اینجا
 * هاردکد نیست، پس با تغییر .env صفحه‌ی فروش هم خودکار درست می‌مونه. پلن‌ها عمداً فقط در مدت و
 * قیمت فرق دارن، چون در کد واقعی هم فقط همین فرقه (module_permissions هنوز هیچ‌جا اعمال
 * نمی‌شه) — صفحه‌ی فروش نباید امکانات متفاوتی رو وعده بده که نرم‌افزار واقعاً نداره.
 */
class CentralLandingController extends Controller
{
    private const PLAN_MONTHS = ['1m' => 1, '3m' => 3, '6m' => 6, '12m' => 12];

    private const PLAN_LABELS = ['1m' => 'یک‌ماهه', '3m' => 'سه‌ماهه', '6m' => 'شش‌ماهه', '12m' => 'یک‌ساله'];

    private const JALALI_MONTHS = [
        1 => 'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور',
        'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند',
    ];

    public function __invoke(Request $request): View
    {
        // پلن فروش (بسته‌ی پلن‌ها، ۲۰۲۶-۱۰-۱۴): اولین پلن عمومی فعال؛ بدون پلن (پیش از plans:sync-defaults) همان config
        $salePlan = \App\Models\Plan::query()->offered()->get()->first(fn (\App\Models\Plan $p) => $p->isSellable());
        $trialPlan = \App\Models\Plan::trial();
        $pricing = app(\App\Support\Billing\SubscriptionPricing::class);
        $prices = $salePlan
            ? collect($salePlan->periods())->mapWithKeys(fn ($type) => [$type => $salePlan->priceFor($type)])->all()
            : config('billing.subscription_prices');
        $trialDays = app(\App\Services\SalonSignup\SalonSignupService::class)->trialDays();
        $smsQuota = $salePlan ? (int) $salePlan->sms_quota_per_month : (int) config('billing.sms_quota_per_month');
        $maxSpecialists = $pricing->includedSpecialists($salePlan);
        $extraSpecialistPrice = $pricing->extraSpecialistPricePerMonth($salePlan);

        $paidFrom = now()->addDays($trialDays);
        $monthlyBase = (int) ($prices['1m'] ?? 0) ?: (int) round(collect($prices)->map(fn ($p, $t) => $p / self::PLAN_MONTHS[$t])->max() ?? 0);

        $plans = [];
        foreach (self::PLAN_MONTHS as $type => $months) {
            if (! isset($prices[$type])) {
                continue;
            }

            $price = (int) $prices[$type];
            $fullPrice = $monthlyBase * $months;

            $plans[] = [
                'type' => $type,
                'label' => self::PLAN_LABELS[$type],
                'months' => $months,
                'price' => $price,
                'per_month' => (int) round($price / $months),
                'saving' => max(0, $fullPrice - $price),
                'saving_percent' => $fullPrice > 0 ? (int) round((1 - $price / $fullPrice) * 100) : 0,
                'sms_total' => $smsQuota * $months,
                'trial_end_label' => $this->jalaliLabel(now()->addDays($trialDays)),
                'period_end_label' => $this->jalaliLabel($paidFrom->copy()->addMonths($months)),
                'signup_url' => route('salon-signup.create', ['plan' => $type]),
                // ⭐ «خرید مستقیم بدون دوره‌ی رایگان» (۲۰۲۶-۰۹-۲۳) — بعد از تایید موبایل مستقیم به درگاه.
                'buy_url' => route('salon-signup.create', ['plan' => $type, 'intent' => 'buy']),
            ];
        }

        return view('central.landing', [
            'plans' => $plans,
            'trialDays' => $trialDays,
            'trialSmsQuota' => (int) ($trialPlan?->sms_quota_per_month ?? config('billing.trial_sms_quota')),
            'smsQuota' => $smsQuota,
            'maxSpecialists' => $maxSpecialists,
            'extraSpecialistPrice' => $extraSpecialistPrice,
            'todayLabel' => $this->jalaliLabel(now()),
            'addressPrefix' => $this->addressPrefix($request),
            'addressSuffix' => $this->addressSuffix(),
            // راه‌های تماس از «تنظیمات پلتفرم» سوپرادمین (پیش‌فرض .env)؛ خالی = پنهان
            'contact' => collect(['support_phone', 'public_email', 'instagram_url', 'linkedin_url', 'telegram_url', 'bale_url'])
                ->mapWithKeys(fn (string $key) => [$key => \App\Support\PlatformSettings::get($key)])->all(),
        ]);
    }

    /**
     * پیش‌نمایش زنده‌ی آدرس در hero: با CENTRAL_DOMAIN → «{slug}.rasta-app.test»، بدون اون →
     * «127.0.0.1:8000/s/{slug}» — دقیقاً همون چیزی که Salon::publicUrl() بعداً واقعاً می‌سازه.
     */
    private function addressPrefix(Request $request): string
    {
        return config('app.central_domain') ? '' : $request->getHttpHost().'/s/';
    }

    private function addressSuffix(): string
    {
        $central = config('app.central_domain');

        return $central ? '.'.$central : '';
    }

    private function jalaliLabel(Carbon $date): string
    {
        [$y, $m, $d] = explode('/', jalali_date($date));

        return to_persian_num((string) (int) $d).' '.self::JALALI_MONTHS[(int) $m].' '.to_persian_num($y);
    }
}
