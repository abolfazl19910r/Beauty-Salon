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
        $prices = config('billing.subscription_prices');
        $trialDays = max(0, (int) config('billing.trial_days', 0));
        $smsQuota = (int) config('billing.sms_quota_per_month');
        $maxSpecialists = (int) config('billing.default_max_specialists_count');

        $paidFrom = now()->addDays($trialDays);
        $monthlyBase = (int) ($prices['1m'] ?? 0);

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
            ];
        }

        return view('central.landing', [
            'plans' => $plans,
            'trialDays' => $trialDays,
            'trialSmsQuota' => (int) config('billing.trial_sms_quota'),
            'smsQuota' => $smsQuota,
            'maxSpecialists' => $maxSpecialists,
            'todayLabel' => $this->jalaliLabel(now()),
            'addressPrefix' => $this->addressPrefix($request),
            'addressSuffix' => $this->addressSuffix(),
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
