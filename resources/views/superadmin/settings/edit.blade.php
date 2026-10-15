@extends('layouts.superadmin')

@section('title', 'تنظیمات پلتفرم')

@section('content')
    <div class="sa-card" style="max-width: 44rem;">
        <h2 class="font-bold text-lg mb-1">راه‌های تماس و اطلاع‌رسانی</h2>
        <p class="mb-5 text-sm" style="color: var(--sa-text-dim);">
            این‌ها در صفحه‌ی فروش (پایین صفحه و فرم دمو) نشان داده می‌شوند؛ فیلد خالی یعنی پیش‌فرض فایل ‎.env و اگر آن هم خالی باشد، آن لینک اصلاً نمایش داده نمی‌شود.
        </p>

        <form method="POST" action="{{ route('superadmin.settings.update') }}" class="grid gap-4">
            @csrf
            @method('PUT')
            @foreach ($fields as $key => $field)
                <div>
                    <label for="ps-{{ $key }}" class="block mb-1 font-medium">{{ $field['label'] }}</label>
                    <input id="ps-{{ $key }}" name="{{ $key }}" value="{{ old($key, $stored[$key] ?? '') }}" dir="ltr"
                           type="{{ $field['type'] === 'email' ? 'email' : ($field['type'] === 'url' ? 'url' : 'text') }}"
                           inputmode="{{ $field['type'] === 'phone' ? 'tel' : ($field['type'] === 'email' ? 'email' : 'url') }}"
                           placeholder="{{ config($field['config']) ?: ($field['type'] === 'url' ? 'https://' : '') }}"
                           class="w-full rounded-lg px-3 py-2" style="border:1px solid var(--sa-border); background: var(--sa-bg); color: var(--sa-text); text-align: left;">
                    @error($key)
                        <p class="mt-1 text-sm" style="color: var(--sa-danger);">{{ $message }}</p>
                    @enderror
                    @if (config($field['config']))
                        <p class="mt-1 text-xs" style="color: var(--sa-text-dim);">پیش‌فرض ‎.env: <span dir="ltr">{{ config($field['config']) }}</span></p>
                    @endif
                    @if ($key === 'demo_notify_email')
                        <p class="mt-1 text-xs" style="color: var(--sa-text-dim);">
                            هر درخواست دمو به این ایمیل هم فرستاده می‌شود (پیامک همیشه به شماره‌ی همه‌ی سوپرادمین‌ها می‌رود).
                            ارسال واقعی ایمیل به تنظیم MAIL_* در ‎.env نیاز دارد؛ الان: <span dir="ltr">{{ config('mail.default') }}</span>
                        </p>
                    @endif
                </div>
            @endforeach
            <div><button type="submit" class="sa-btn">ذخیره</button></div>
        </form>
    </div>

    <div class="sa-card p-6 mt-6" style="max-width: 42rem;" data-bot-status>
        <h2 class="font-bold mb-1">ربات اعلان (بله / تلگرام)</h2>
        <p class="text-sm mb-4" style="color: var(--sa-text-dim);">روشن یا خاموش بودن از کلیدهای <span dir="ltr">BALE_BOT_TOKEN</span> و <span dir="ltr">TELEGRAM_BOT_TOKEN</span> در فایل .env سرور است؛ اینجا فقط نمایش.</p>
        <table class="w-full text-sm text-right">
            <thead><tr style="color: var(--sa-text-dim);"><th class="py-2 px-3">پیام‌رسان</th><th class="py-2 px-3">وضعیت</th><th class="py-2 px-3">نام ربات</th><th class="py-2 px-3">کاربران وصل</th></tr></thead>
            <tbody>
            @foreach (['bale' => 'بله', 'telegram' => 'تلگرام'] as $key => $label)
                <tr style="border-top: 1px solid var(--sa-border);">
                    <td class="py-2 px-3 font-bold">{{ $label }}</td>
                    <td class="py-2 px-3">
                        @if (in_array($key, $bot['enabled'], true))
                            <span style="color: var(--sa-success);">روشن</span>
                        @else
                            <span style="color: var(--sa-text-dim);">خاموش</span>
                        @endif
                    </td>
                    <td class="py-2 px-3" dir="ltr" style="text-align: right;">{{ $bot['usernames'][$key] ? '@'.$bot['usernames'][$key] : '—' }}</td>
                    <td class="py-2 px-3">{{ number_format($bot['links'][$key] ?? 0) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endsection
