<?php

namespace App\Http\Controllers\Admin\Marketing;

use App\Http\Controllers\Controller;
use App\Models\BeautyService;
use App\Models\Salon;
use App\Models\WinbackCampaign;
use App\Models\WinbackCampaignRecipient;
use App\Services\Marketing\WinbackCampaignService;
use App\Support\CurrentSalon;
use App\Support\JalaliDateInput;
use App\Support\Sms\SmsTemplateStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * «ارسال دستی به مشتری‌های قدیمی» در پنل مدیر (بسته‌ی ج): فهرست کمپین‌ها، فرم با پیش‌نمایش (تعداد، متن نمونه با یک مشتری واقعی،
 * قطعه‌ها، سهمیه‌ی پس از کمپین، پیشنهاد «فقط N نفر جدیدتر» یا خرید بسته)، تأیید صریح «تبلیغاتی»، صفحه‌ی کمپین با پیشرفت و نتیجه،
 * توقف/ادامه/لغو. پشت feature:winback_manual.
 */
class AdminWinbackCampaignController extends Controller
{
    public function __construct(private readonly WinbackCampaignService $campaigns) {}

    public function index(): View
    {
        $salon = $this->salon();

        return view('admin.winback.campaigns.index', [
            'campaigns' => WinbackCampaign::query()->latest('id')->limit(30)->get(),
            'limitsError' => $this->campaigns->limitsError($salon),
        ]);
    }

    public function create(): View
    {
        $salon = $this->salon();

        return view('admin.winback.campaigns.form', $this->formData($salon, [
            'name' => 'دعوت مشتری‌های قدیمی '.verta()->format('Y/m'),
            'last_visit_from_days' => $this->campaigns->minFromDays($salon),
            'last_visit_to_days' => WinbackCampaignService::MAX_AGE_DAYS,
            'max_recipients' => WinbackCampaignService::MAX_RECIPIENTS,
            'discount_valid_days' => 14,
            'message' => SmsTemplateStore::body($salon->id, 'winback'),
        ]));
    }

    /** پیش‌نمایش (چیزی ذخیره نمی‌شود) یا ساخت با تأیید صریح */
    public function store(Request $request): View|RedirectResponse
    {
        $salon = $this->salon();
        $data = $this->validated($request, $salon);

        if ($request->input('action') !== 'start') {
            return view('admin.winback.campaigns.form', array_merge($this->formData($salon, $data), [
                'preview' => $this->campaigns->preview($salon, $data, $data['message'], $this->discountModel($data)),
            ]));
        }

        $request->validate(['confirm_marketing' => ['accepted']], ['confirm_marketing.accepted' => 'تأیید کنید که این پیامک تبلیغاتی است.']);

        try {
            $campaign = $this->campaigns->create($salon, $request->user(), $data);
        } catch (\DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.winback.campaigns.show', $campaign)->with('success', sprintf('کمپین برای %s مشتری ساخته شد؛ ارسال %s.',
            to_persian_num((string) $campaign->targeted),
            $campaign->scheduled_at->gt(now()->addMinutes(5)) ? 'از '.verta($campaign->scheduled_at)->format('Y/m/d').' ساعت ۹' : 'در ساعت‌های ۹ تا ۲۰ از همین حالا'));
    }

    public function show(WinbackCampaign $campaign): View
    {
        return view('admin.winback.campaigns.show', [
            'campaign' => $campaign,
            'results' => $this->campaigns->results($campaign),
            'pending' => WinbackCampaignRecipient::query()->where('winback_campaign_id', $campaign->id)->whereIn('status', ['pending', 'sending'])->count(),
            'recent' => WinbackCampaignRecipient::query()->where('winback_campaign_id', $campaign->id)->whereIn('status', ['sent', 'skipped'])
                ->with(['user:id,name,phone', 'discountCode:id,code,used_count'])->latest('updated_at')->limit(30)->get(),
            'insideHours' => $this->campaigns->insideSendingHours(),
        ]);
    }

    public function pause(WinbackCampaign $campaign): RedirectResponse
    {
        $this->campaigns->pause($campaign);

        return back()->with('success', 'کمپین متوقف شد.');
    }

    public function resume(WinbackCampaign $campaign): RedirectResponse
    {
        return $this->campaigns->resume($campaign)
            ? back()->with('success', 'ارسال ادامه پیدا می‌کند (فقط ۹ تا ۲۰).')
            : back()->with('error', 'این کمپین متوقف نیست.');
    }

    public function cancel(Request $request, WinbackCampaign $campaign): RedirectResponse
    {
        return $this->campaigns->cancel($campaign, $request->user())
            ? back()->with('success', 'کمپین لغو شد؛ به باقی مشتری‌ها پیامکی نمی‌رود.')
            : back()->with('error', 'این کمپین دیگر فعال نیست.');
    }

    // ─── کمکی ──────────────────────────────────────────────────────

    private function salon(): Salon
    {
        return app(CurrentSalon::class)->get();
    }

    private function validated(Request $request, Salon $salon): array
    {
        $digits = fn ($v) => is_string($v) ? strtr($v, ['۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9', ',' => '', '٬' => '']) : $v;
        foreach (['last_visit_from_days', 'last_visit_to_days', 'min_visits', 'max_recipients', 'discount_amount', 'discount_max_amount', 'discount_valid_days'] as $field) {
            $request->merge([$field => ($v = $digits($request->input($field))) === '' ? null : $v]);
        }
        $min = $this->campaigns->minFromDays($salon);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'last_visit_from_days' => ['required', 'integer', 'min:'.$min, 'max:'.WinbackCampaignService::MAX_AGE_DAYS],
            'last_visit_to_days' => ['required', 'integer', 'gte:last_visit_from_days', 'max:'.WinbackCampaignService::MAX_AGE_DAYS],
            'service_ids' => ['nullable', 'array'],
            'service_ids.*' => ['integer', Rule::exists('beauty_services', 'id')->where('salon_id', $salon->id)],
            'min_visits' => ['nullable', 'integer', 'min:1', 'max:100'],
            'max_recipients' => ['required', 'integer', 'min:1', 'max:'.WinbackCampaignService::MAX_RECIPIENTS],
            'discount_type' => ['nullable', Rule::in(['', 'fixed', 'percentage'])],
            'discount_amount' => ['nullable', 'required_with:discount_type', 'numeric', 'min:1',
                Rule::when($request->input('discount_type') === 'percentage', ['max:100'], ['max:100000000'])],
            'discount_max_amount' => ['nullable', 'numeric', 'min:1000', 'max:100000000'],
            'discount_valid_days' => ['required', 'integer', 'min:3', 'max:90'],
            'message' => ['required', 'string', 'max:'.\App\Support\Sms\SmsTemplates::MAX_LENGTH],
            'start_date' => ['nullable', 'string', 'max:20'],
        ], [
            'last_visit_from_days.min' => sprintf('مشتری‌هایی که کمتر از %s روز نیامده‌اند را پیگیری خودکار پوشش می‌دهد.', to_persian_num((string) $min)),
            'last_visit_from_days.max' => 'حداکثر ۲ سال (شماره‌های قدیمی‌تر ممکن است صاحبشان عوض شده باشد).',
            'last_visit_to_days.max' => 'حداکثر ۲ سال (شماره‌های قدیمی‌تر ممکن است صاحبشان عوض شده باشد).',
        ], [
            'name' => 'نام کمپین', 'last_visit_from_days' => 'از چند روز', 'last_visit_to_days' => 'تا چند روز', 'min_visits' => 'حداقل مراجعه',
            'max_recipients' => 'سقف گیرنده', 'discount_amount' => 'مقدار تخفیف', 'discount_max_amount' => 'سقف تخفیف',
            'discount_valid_days' => 'مهلت کد', 'message' => 'متن پیامک',
        ]);

        if ($error = $this->campaigns->messageError($data['message'])) {
            throw \Illuminate\Validation\ValidationException::withMessages(['message' => $error]);
        }

        $data['discount_type'] = ($data['discount_type'] ?? '') ?: null;
        $data['scheduled_at'] = null;
        if (filled($data['start_date'] ?? null)) {
            $day = JalaliDateInput::toCarbon($data['start_date']);
            if (! $day) {
                throw \Illuminate\Validation\ValidationException::withMessages(['start_date' => 'تاریخ را به شکل ۱۴۰۵/۰۸/۱۰ وارد کنید.']);
            }
            // ساعت ۹ صبح تهرانِ همان روز
            $data['scheduled_at'] = Carbon::parse($day->format('Y-m-d').' 09:00', WinbackCampaignService::TIMEZONE)->setTimezone(config('app.timezone'));
            if ($data['scheduled_at']->lt(now()->startOfDay())) {
                throw \Illuminate\Validation\ValidationException::withMessages(['start_date' => 'تاریخ شروع گذشته است.']);
            }
        }

        return $data;
    }

    private function discountModel(array $data): WinbackCampaign
    {
        return new WinbackCampaign([
            'discount_type' => $data['discount_type'] ?? null,
            'discount_amount' => $data['discount_amount'] ?? null,
            'discount_valid_days' => (int) ($data['discount_valid_days'] ?? 14),
        ]);
    }

    private function formData(Salon $salon, array $values): array
    {
        return [
            'values' => $values,
            'services' => BeautyService::query()->orderBy('name')->get(['id', 'name']),
            'minFromDays' => $this->campaigns->minFromDays($salon),
            'limitsError' => $this->campaigns->limitsError($salon),
            'preview' => null,
        ];
    }
}
