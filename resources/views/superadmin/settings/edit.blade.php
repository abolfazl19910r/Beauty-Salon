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
@endsection
