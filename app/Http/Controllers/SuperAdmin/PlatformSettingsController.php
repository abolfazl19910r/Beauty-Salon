<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Support\PlatformSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * «تنظیمات پلتفرم» سوپرادمین (۲۰۲۶-۱۰-۱۴): راه‌های تماس صفحه‌ی فروش و ایمیل گیرنده‌ی درخواست‌های دمو، بدون ویرایش .env روی سرور.
 * خالی گذاشتن یک فیلد = برگشت به پیش‌فرض .env (که کنار هر فیلد نشان داده می‌شود).
 */
class PlatformSettingsController extends Controller
{
    public function edit(): View
    {
        return view('superadmin.settings.edit', [
            'fields' => PlatformSettings::FIELDS,
            'stored' => PlatformSettings::stored(),
            // ⭐ ۲۰۲۶-۱۰-۱۵: وضعیت ربات بله/تلگرام (قبلاً فقط در .env معلوم بود)
            'bot' => app(\App\Services\SuperAdmin\PlatformOverview::class)->botStatus(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->merge(['support_phone' => self::latinDigits((string) $request->input('support_phone'))]);

        $rules = [];
        foreach (PlatformSettings::FIELDS as $key => $field) {
            $rules[$key] = match ($field['type']) {
                'phone' => ['nullable', 'string', 'regex:/^\+?[0-9][0-9 -]{6,18}$/'],
                'email' => ['nullable', 'email', 'max:190'],
                'url' => ['nullable', 'url:https,http', 'max:255'],
            };
        }

        $data = $request->validate($rules, [
            'support_phone.regex' => 'شماره‌ی تلفن معتبر نیست (فقط رقم، فاصله، خط تیره و + اول شماره).',
            '*.email' => 'ایمیل معتبر نیست.',
            '*.url' => 'آدرس باید با https:// شروع شود.',
        ]);

        PlatformSettings::save($data);

        return back()->with('success', 'تنظیمات پلتفرم ذخیره شد.');
    }

    private static function latinDigits(string $value): string
    {
        return strtr(trim($value), ['۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9']);
    }
}
