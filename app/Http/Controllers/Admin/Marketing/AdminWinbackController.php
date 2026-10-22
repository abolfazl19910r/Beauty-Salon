<?php

namespace App\Http\Controllers\Admin\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Salon;
use App\Models\WinbackMessage;
use App\Models\WinbackSetting;
use App\Services\Marketing\WinbackService;
use App\Support\CurrentSalon;
use App\Support\Sms\SmsTemplates;
use App\Support\Sms\SmsTemplateStore;
use App\Support\SmsParts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** «پیگیری مشتریان غایب» پنل مدیر سالن (۲۰۲۶-۱۰-۱۴): روشن/خاموش، چند روز غیبت، کد تخفیف اختیاری، آمار و پیامک‌های اخیر. */
class AdminWinbackController extends Controller
{
    public function index(WinbackService $winback): View
    {
        /** @var Salon $salon */
        $salon = app(CurrentSalon::class)->get();
        $setting = WinbackSetting::forSalon($salon->id);

        $sample = SmsTemplates::fill(SmsTemplateStore::body($salon->id, 'winback'), [
            'سالن' => (string) $salon->smsName(),
            'تخفیف' => $setting->hasDiscount() ? sprintf('کد BKQ7M2X: %s تخفیف تا %s. ', $setting->discountLabel(), verta(now()->addDays($setting->discount_valid_days))->format('Y/m/d')) : '',
            'لینک_رزرو' => 'arisheh.ir/b/r7p2xk',
            'لینک_انصراف' => 'arisheh.ir/b/u9z1ab',
        ]);

        return view('admin.winback.index', [
            'setting' => $setting,
            'stats' => $winback->stats($salon->id),
            'dueToday' => $setting->exists ? $winback->candidates($salon, $setting)->count() : 0,
            'recent' => WinbackMessage::with(['user:id,name,phone', 'discountCode:id,code,used_count'])->latest('sent_at')->limit(20)->get()
                ->each(fn (WinbackMessage $m) => $m->setAttribute('returned', WinbackService::returnedAfter($m))),
            'sample' => $sample,
            'sampleParts' => SmsParts::count($sample),
            'windowDays' => WinbackService::WINDOW_DAYS,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->merge(['discount_amount' => $this->latinDigits($request->input('discount_amount')), 'discount_max_amount' => $this->latinDigits($request->input('discount_max_amount'))]);

        $input = $request->validate([
            'enabled' => ['boolean'],
            'lapse_days' => ['required', 'integer', 'min:14', 'max:365'],
            'discount_type' => ['nullable', Rule::in(['', 'fixed', 'percentage'])],
            'discount_amount' => ['nullable', 'required_with:discount_type', 'numeric', 'min:1',
                Rule::when($request->input('discount_type') === 'percentage', ['max:100'], ['max:100000000'])],
            'discount_max_amount' => ['nullable', 'numeric', 'min:1000', 'max:100000000'],
            'discount_valid_days' => ['required', 'integer', 'min:3', 'max:90'],
        ], [], [
            'lapse_days' => 'تعداد روز غیبت', 'discount_amount' => 'مقدار تخفیف', 'discount_max_amount' => 'سقف تخفیف', 'discount_valid_days' => 'مهلت استفاده از کد',
        ]);

        $type = ($input['discount_type'] ?? '') ?: null;
        $salonId = app(CurrentSalon::class)->id();

        WinbackSetting::withoutGlobalScope('salon')->updateOrCreate(['salon_id' => $salonId], [
            'enabled' => $request->boolean('enabled'),
            'lapse_days' => (int) $input['lapse_days'],
            'discount_type' => $type,
            'discount_amount' => $type ? $input['discount_amount'] : null,
            'discount_max_amount' => $type === 'percentage' ? ($input['discount_max_amount'] ?? null) : null,
            'discount_valid_days' => (int) $input['discount_valid_days'],
        ]);

        return redirect()->route('admin.winback.index')->with('success', 'تنظیمات پیگیری مشتریان ذخیره شد.');
    }

    private function latinDigits(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return str_replace([',', '٬', ' '], '', strtr((string) $value, ['۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9']));
    }
}
