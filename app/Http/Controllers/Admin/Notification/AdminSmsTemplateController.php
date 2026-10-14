<?php

namespace App\Http\Controllers\Admin\Notification;

use App\Http\Controllers\Controller;
use App\Support\CurrentSalon;
use App\Support\Sms\SmsTemplates;
use App\Support\Sms\SmsTemplateStore;
use App\Support\SmsParts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * «متن پیامک‌ها» پنل مدیر سالن (تصمیم ۲۰۲۶-۱۰-۱۴): هر سالن متن خودش را می‌گذارد؛ جای‌خالی‌های ضروری اجباری، جای‌خالی ناشناخته
 * پذیرفته نمی‌شود، سقف طول SmsTemplates::MAX_LENGTH. «بازگشت به پیش‌فرض» ردیف سالن را پاک می‌کند.
 */
class AdminSmsTemplateController extends Controller
{
    public function index(): View
    {
        $salonId = app(CurrentSalon::class)->id();
        $overrides = SmsTemplateStore::overrides($salonId);

        $templates = collect(SmsTemplates::all())->map(fn (array $t, string $key) => $t + [
            'key' => $key,
            'body' => $overrides[$key] ?? $t['default'],
            'custom' => array_key_exists($key, $overrides),
            'sample' => SmsTemplates::sample($key, $overrides[$key] ?? null),
        ])->groupBy('audience');

        return view('admin.sms-templates.index', ['groups' => $templates, 'maxLength' => SmsTemplates::MAX_LENGTH]);
    }

    public function update(Request $request, string $key): RedirectResponse
    {
        abort_unless(array_key_exists($key, SmsTemplates::all()), 404);

        $body = trim(preg_replace('/[ \t]+/u', ' ', str_replace(["\r\n", "\r"], "\n", (string) $request->input('body'))));
        $errors = self::problems($key, $body);
        if ($errors) {
            throw ValidationException::withMessages(['body' => $errors])->errorBag('sms_'.$key);
        }

        $salonId = app(CurrentSalon::class)->id();
        $body === SmsTemplates::get($key)['default']
            ? SmsTemplateStore::reset($salonId, $key)
            : SmsTemplateStore::save($salonId, $key, $body);

        return redirect()->to(route('admin.sms-templates.index').'#sms-'.$key)
            ->with('success', sprintf('متن «%s» ذخیره شد (نمونه: %s قطعه).', SmsTemplates::get($key)['label'], SmsParts::count(SmsTemplates::sample($key, $body))));
    }

    public function reset(string $key): RedirectResponse
    {
        abort_unless(array_key_exists($key, SmsTemplates::all()), 404);
        SmsTemplateStore::reset(app(CurrentSalon::class)->id(), $key);

        return redirect()->to(route('admin.sms-templates.index').'#sms-'.$key)
            ->with('success', sprintf('متن «%s» به پیش‌فرض برگشت.', SmsTemplates::get($key)['label']));
    }

    /** @return list<string> پیام‌های خطای فارسی؛ خالی = مجاز */
    public static function problems(string $key, string $body): array
    {
        $t = SmsTemplates::get($key);
        $errors = [];

        if ($body === '') {
            return ['متن پیامک نمی‌تواند خالی باشد.'];
        }
        if (mb_strlen($body) > SmsTemplates::MAX_LENGTH) {
            $errors[] = sprintf('متن حداکثر %s نویسه می‌تواند باشد.', SmsTemplates::MAX_LENGTH);
        }

        $used = SmsTemplates::placeholdersIn($body);
        foreach (array_diff($used, array_keys($t['placeholders'])) as $unknown) {
            $errors[] = "جای‌خالی {{$unknown}} برای این پیامک تعریف نشده است.";
        }
        foreach (array_diff($t['required'], $used) as $missing) {
            $errors[] = "جای‌خالی {{$missing}} ضروری است و باید در متن بماند.";
        }

        return $errors;
    }
}
