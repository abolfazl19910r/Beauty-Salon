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
            // پوش اپ‌ها (بسته‌ی ۴)
            'push' => [
                'mode' => \App\Services\Push\PushMode::current(),
                'modes' => collect(\App\Services\Push\PushMode::ALL)->mapWithKeys(fn ($m) => [$m => \App\Services\Push\PushMode::label($m)])->all(),
                'fcm_configured' => app(\App\Services\Push\FcmClient::class)->configured(),
                'ntfy_configured' => app(\App\Services\Push\NtfyClient::class)->configured(),
                'devices' => \App\Models\PushDevice::query()->active()->count(),
                // پنجره‌ی تأیید رفتن به «فعال» با همان اعداد کارت آمادگی (تصمیم ۶-۱ ب)
                'live_confirm' => \App\Services\Push\PushMode::current() === \App\Services\Push\PushMode::LIVE ? null : app(\App\Services\Push\PushStats::class)->readinessSummary(),
            ],
        ]);
    }

    /** حالت پوش پلتفرم: خاموش / آزمایشی / فعال (تصمیم ۴ بسته‌ی ۴) */
    public function updatePush(Request $request): RedirectResponse
    {
        $data = $request->validate(['mode' => ['required', 'in:'.implode(',', \App\Services\Push\PushMode::ALL)]], [
            'mode.*' => 'حالت پوش معتبر نیست.',
        ]);

        $before = \App\Services\Push\PushMode::current();
        \App\Services\Push\PushMode::set($data['mode']);
        if ($before !== $data['mode']) {
            app(\App\Services\SuperAdmin\SuperAdminAudit::class)->record('push.mode', 'حالت پوش اپ‌ها', null, ['mode' => [$before, $data['mode']]]);
        }

        return back()->with('success', 'حالت پوش اپ‌ها: '.\App\Services\Push\PushMode::label($data['mode']));
    }

    /** بررسی اتصال سرور به گوگل با حساب سرویس فایربیس (همان تبادل توکن دسترسی ارسال واقعی) */
    public function checkPush(): RedirectResponse
    {
        $fcm = app(\App\Services\Push\FcmClient::class);
        if (! $fcm->configured()) {
            return back()->withErrors(['push' => 'PUSH_FCM_CREDENTIALS در .env سرور تنظیم نشده است.']);
        }

        $result = $fcm->check();

        return $result->ok
            ? back()->with('success', 'اتصال به FCM سالم است (پروژه‌ی '.$fcm->projectId().').')
            : back()->withErrors(['push' => 'اتصال به FCM ناموفق: '.$result->error]);
    }

    /**
     * توکن ربات بله/تلگرام از پنل (۲۰۲۶-۱۰-۱۵). توکن تازه پیش از ذخیره با getMe بررسی می‌شود: رد صریح پیام‌رسان = ذخیره نمی‌شود؛
     * نرسیدن به پیام‌رسان (تلگرام از ایران) = ذخیره با هشدار. خالی گذاشتن = توکن فعلی می‌ماند؛ «حذف» = برگشت به .env.
     */
    public function updateBot(Request $request, \App\Services\Bot\BotClient $client): RedirectResponse
    {
        abort_unless(\App\Services\Bot\BotSettings::featureEnabled(), 404);

        $data = $request->validate([
            'messenger' => ['required', 'in:'.implode(',', \App\Services\Bot\BotSettings::MESSENGERS)],
            'token' => ['nullable', 'string', 'max:200', 'regex:/^[0-9]{3,15}:[A-Za-z0-9_-]{10,120}$/'],
            'username' => ['nullable', 'string', 'max:64', 'regex:/^@?[A-Za-z][A-Za-z0-9_]{3,63}$/'],
            'api_base' => ['nullable', 'url:https,http', 'max:255'],
            'remove' => ['nullable', 'boolean'],
        ], [
            'token.regex' => 'قالب توکن درست نیست (مثل 123456789:AbCdEf… که BotFather یا «بازو»ی بله می‌دهد).',
            'username.regex' => 'نام ربات فقط حروف لاتین، رقم و _ است.',
            'api_base.url' => 'آدرس API باید با https:// شروع شود.',
        ]);

        $messenger = $data['messenger'];
        $label = $messenger === 'bale' ? 'بله' : 'تلگرام';
        $audit = app(\App\Services\SuperAdmin\SuperAdminAudit::class);

        if (! empty($data['remove'])) {
            \App\Services\Bot\BotSettings::saveToken($messenger, null);
            \App\Services\Bot\BotSettings::saveUsername($messenger, null);
            \App\Services\Bot\BotSettings::saveApiBase($messenger, null);
            $audit->record('bot.token_removed', "توکن ربات {$label} از پنل حذف شد", null);

            return back()->with('success', "توکن ربات {$label} از پنل حذف شد؛ از این به بعد مقدار .env (اگر باشد) به کار می‌رود.");
        }

        if ($messenger === 'telegram') {
            \App\Services\Bot\BotSettings::saveApiBase($messenger, $data['api_base'] ?? null);
        }

        $warning = null;
        if (! empty($data['token'])) {
            $check = $client->getMe($messenger, $data['token'], $messenger === 'telegram' ? ($data['api_base'] ?? null) : null);
            if ($check['reachable'] && ! $check['ok']) {
                return back()->withInput($request->except('token'))->withErrors(['bot_'.$messenger => "پیام‌رسان {$label} این توکن را نپذیرفت: ".$check['description']]);
            }
            if (! $check['reachable']) {
                $warning = "به {$label} وصل نشدیم و توکن بررسی نشد (برای تلگرام از سرور ایران، آدرس پراکسی را بگذارید). ذخیره شد.";
            }

            \App\Services\Bot\BotSettings::saveToken($messenger, $data['token']);
            \App\Services\Bot\BotSettings::saveUsername($messenger, $check['username'] ?? ($data['username'] ?? null));
            $audit->record('bot.token_updated', "توکن ربات {$label} از پنل عوض شد", null);
        } elseif (array_key_exists('username', $data) && $data['username']) {
            \App\Services\Bot\BotSettings::saveUsername($messenger, $data['username']);
        }

        $redirect = back()->with('success', "تنظیمات ربات {$label} ذخیره شد.");

        return $warning ? $redirect->with('warning', $warning) : $redirect;
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

        $before = PlatformSettings::stored();
        PlatformSettings::save($data);
        $changes = \App\Services\SuperAdmin\SuperAdminAudit::diff(array_intersect_key($before, $data), PlatformSettings::stored());
        if ($changes) {
            app(\App\Services\SuperAdmin\SuperAdminAudit::class)->record('settings.updated', 'تنظیمات پلتفرم', null, $changes);
        }

        return back()->with('success', 'تنظیمات پلتفرم ذخیره شد.');
    }

    private static function latinDigits(string $value): string
    {
        return strtr(trim($value), ['۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9']);
    }
}
