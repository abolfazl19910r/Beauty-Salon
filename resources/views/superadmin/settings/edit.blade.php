@extends('layouts.superadmin')

@section('title', 'تنظیمات پلتفرم')

@section('content')
    {{-- بسته‌ی «ح»، ۲: کادرها فاصله‌ی داخلی نداشتند (متن به لبه چسبیده بود) و همه در ستون باریک ۴۴rem بودند --}}
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 items-start">
    <div class="sa-card sa-card-pad">
        <h2 class="sa-card-title">راه‌های تماس و اطلاع‌رسانی</h2>
        <p class="sa-card-desc mb-5">
            این‌ها در صفحه‌ی فروش (پایین صفحه و فرم دمو) نشان داده می‌شوند؛ فیلد خالی یعنی پیش‌فرض فایل ‎.env و اگر آن هم خالی باشد، آن لینک اصلاً نمایش داده نمی‌شود.
        </p>

        <form method="POST" action="{{ route('superadmin.settings.update') }}" class="grid md:grid-cols-2 gap-4">
            @csrf
            @method('PUT')
            @foreach ($fields as $key => $field)
                <div>
                    <label for="ps-{{ $key }}" class="sa-label">{{ $field['label'] }}</label>
                    <input id="ps-{{ $key }}" name="{{ $key }}" value="{{ old($key, $stored[$key] ?? '') }}" dir="ltr"
                           type="{{ $field['type'] === 'email' ? 'email' : ($field['type'] === 'url' ? 'url' : 'text') }}"
                           inputmode="{{ $field['type'] === 'phone' ? 'tel' : ($field['type'] === 'email' ? 'email' : 'url') }}"
                           placeholder="{{ config($field['config']) ?: ($field['type'] === 'url' ? 'https://' : '') }}"
                           class="sa-input" style="text-align: left;">
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
            <div class="md:col-span-2"><button type="submit" class="sa-btn">ذخیره</button></div>
        </form>
    </div>

    {{-- بسته‌ی مجوز 5د: «تماس با ما»، هویت صاحب پلتفرم در صفحه‌های حقوقی و لوگوی اینماد --}}
    <div class="sa-card sa-card-pad" id="legal-settings">
        <h2 class="sa-card-title">اطلاعات حقوقی و اینماد</h2>
        <p class="sa-card-desc mb-5">
            در صفحه‌ی «تماس با ما» و صفحه‌های «قوانین» و «حریم خصوصی» نشان داده می‌شوند. اینماد درج تلفن ثابت، ایمیل و نشانی پستی را در «تماس با ما»
            الزامی می‌داند. کد لوگو را <b>بی هیچ تغییری</b> از پنل اینماد کپی کنید؛ عیناً روی صفحه‌ی اصلی می‌آید (تصویر بی لینک یا کپی‌شده تخلف است).
        </p>
        <form method="POST" action="{{ route('superadmin.settings.legal') }}" class="grid md:grid-cols-2 gap-4">
            @csrf
            @method('PUT')
            @foreach ($legalFields as $key => $field)
                <div class="{{ in_array($field['type'], ['textarea', 'html'], true) ? 'md:col-span-2' : '' }}">
                    <label for="ps-{{ $key }}" class="sa-label">{{ $field['label'] }}</label>
                    @if (in_array($field['type'], ['textarea', 'html'], true))
                        <textarea id="ps-{{ $key }}" name="{{ $key }}" rows="{{ $field['type'] === 'html' ? 4 : 2 }}" class="sa-input"
                                  @if ($field['type'] === 'html') dir="ltr" style="text-align: left; font-family: monospace; font-size: 12px;" spellcheck="false" @endif>{{ old($key, $stored[$key] ?? '') }}</textarea>
                    @else
                        <input id="ps-{{ $key }}" name="{{ $key }}" value="{{ old($key, $stored[$key] ?? '') }}"
                               @if ($field['type'] !== 'text') dir="ltr" style="text-align: left;" @endif
                               type="{{ $field['type'] === 'email' ? 'email' : 'text' }}"
                               inputmode="{{ in_array($field['type'], ['phone', 'postal'], true) ? 'numeric' : ($field['type'] === 'email' ? 'email' : 'text') }}"
                               class="sa-input">
                    @endif
                    @error($key)
                        <p class="mt-1 text-sm" style="color: var(--sa-danger);">{{ $message }}</p>
                    @enderror
                </div>
            @endforeach
            <div class="md:col-span-2 flex flex-wrap gap-4 items-center">
                <button type="submit" class="sa-btn">ذخیره</button>
                <a href="{{ \App\Support\CentralUrl::route('legal.contact') }}" target="_blank" class="text-sm" style="color: var(--sa-accent);">دیدن «تماس با ما»</a>
            </div>
        </form>
    </div>

    <div class="space-y-6">
    <div class="sa-card sa-card-pad" data-push-settings>
        <h2 class="sa-card-title">اعلان اپ‌ها (پوش)</h2>
        <p class="sa-card-desc mb-4">
            «آزمایشی»: پیامک مثل قبل همیشه می‌رود و پوش هم؛ فقط نرخ رسید سنجیده می‌شود (۲ تا ۴ هفته). «فعال»: پیامک فقط وقتی اپ رسید نفرستد
            (لغو و تغییر نوبت و یادآوری ۱ دقیقه، بقیه ۱۰ دقیقه؛ امتیاز، کد تخفیف و درخواست نظر فقط پوش). نتیجه در
            <a href="{{ route('superadmin.push.index') }}" style="color: var(--sa-accent);">اعلان اپ (پوش)</a>.
        </p>
        <form method="POST" action="{{ route('superadmin.settings.push') }}" class="flex flex-wrap items-end gap-3"
              @if ($push['live_confirm']) data-live-confirm="{{ $push['live_confirm'] }}"
              onsubmit="return this.mode.value !== 'live' || confirm(this.dataset.liveConfirm);" @endif>
            @csrf
            @method('PUT')
            <div class="grow">
                <label for="push-mode" class="sa-label">حالت</label>
                <select id="push-mode" name="mode" class="sa-input">
                    @foreach ($push['modes'] as $value => $label)
                        <option value="{{ $value }}" @selected($push['mode'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="sa-btn">ذخیره</button>
        </form>
        <div class="mt-4 text-sm flex flex-wrap items-center gap-x-6 gap-y-2">
            <span>FCM: @if ($push['fcm_configured'])<span style="color: var(--sa-success);">کلید حساب سرویس تنظیم شده</span>@else<span style="color: var(--sa-danger);">تنظیم نشده (<span dir="ltr">PUSH_FCM_CREDENTIALS</span>)</span>@endif</span>
            <span>ntfy: @if ($push['ntfy_configured'])<span style="color: var(--sa-success);">تنظیم شده</span>@else<span style="color: var(--sa-text-dim);">بدون سرور (برای قطعی اینترنت بین‌الملل)</span>@endif</span>
            <span>گوشی‌های ثبت‌شده: {{ to_persian_num(number_format($push['devices'])) }}</span>
            <a href="{{ route('superadmin.push.index') }}#push-readiness" style="color: var(--sa-accent);">آمادگی برای «فعال»</a>
            @if ($push['fcm_configured'])
                <form method="POST" action="{{ route('superadmin.settings.push.check') }}">
                    @csrf
                    <button type="submit" class="text-sm underline" style="color: var(--sa-accent);">بررسی اتصال به FCM</button>
                </form>
            @endif
        </div>
    </div>

    {{-- ربات کنار گذاشته شده (بسته‌ی ۸): فقط با BOT_ENABLED=true دیده می‌شود --}}
    @if (\App\Services\Bot\BotSettings::featureEnabled())
    <div class="sa-card sa-card-pad" data-bot-status>
        <h2 class="sa-card-title">ربات اعلان (بله / تلگرام)</h2>
        <p class="sa-card-desc mb-4">
            مدیر، متخصص و مشتری از کارت «دریافت اعلان‌ها در پیام‌رسان» در پروفایل خودشان به ربات وصل می‌شوند و اعلان‌های حسابشان علاوه بر پیامک در پیام‌رسان هم می‌آید.
            توکن اینجا بر <span dir="ltr">BALE_BOT_TOKEN</span>/<span dir="ltr">TELEGRAM_BOT_TOKEN</span> فایل .env مقدم است. بعد از گذاشتن توکن روی سرور یک بار
            <span dir="ltr">php artisan bot:webhook bale</span> (روی لوکال <span dir="ltr">php artisan bot:poll bale</span>).
            ربات پس از پایدار شدن اپ جمع می‌شود (تصمیم ۲۰۲۶-۱۰-۰۲).
        </p>
        <div class="sa-table-wrap mt-4"><table class="w-full text-sm text-right mb-2">
            <thead><tr style="color: var(--sa-text-dim);"><th class="py-2 px-3">پیام‌رسان</th><th class="py-2 px-3">وضعیت</th><th class="py-2 px-3">نام ربات</th><th class="py-2 px-3">توکن</th><th class="py-2 px-3">کاربران وصل</th></tr></thead>
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
                    <td class="py-2 px-3" data-bot-token-source="{{ $key }}">
                        @if (($bot['sources'][$key] ?? null) === 'panel')
                            <span dir="ltr">{{ $bot['masked'][$key] }}</span> <span style="color: var(--sa-text-dim);">(از پنل)</span>
                        @elseif (($bot['sources'][$key] ?? null) === 'env')
                            <span dir="ltr">{{ $bot['masked'][$key] }}</span> <span style="color: var(--sa-text-dim);">(از .env)</span>
                        @else
                            —
                        @endif
                    </td>
                    <td class="py-2 px-3">{{ number_format($bot['links'][$key] ?? 0) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table></div>

        @foreach (['bale' => 'بله', 'telegram' => 'تلگرام'] as $key => $label)
            <form method="POST" action="{{ route('superadmin.settings.bot') }}" class="grid gap-3 mt-5 pt-5" style="border-top: 1px solid var(--sa-border);" data-bot-form="{{ $key }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="messenger" value="{{ $key }}">
                <h3 class="font-bold text-sm">ربات {{ $label }}</h3>
                <div>
                    <label for="bot-{{ $key }}-token" class="sa-label">توکن</label>
                    <input id="bot-{{ $key }}-token" name="token" type="password" autocomplete="off" dir="ltr" placeholder="{{ $bot['masked'][$key] ? 'خالی = همان توکن فعلی' : '123456789:AbCd…' }}" class="sa-input" style="text-align: left;">
                    <p class="mt-1 text-xs" style="color: var(--sa-text-dim);">
                        @if ($key === 'bale')
                            از ربات «بازو»ی بله (<span dir="ltr">@BotFather</span> بله) بگیرید؛ نام ربات بعد از بررسی خودکار پر می‌شود.
                        @else
                            از <span dir="ltr">@BotFather</span> تلگرام. از سرور ایران بدون پراکسی بررسی نمی‌شود.
                        @endif
                    </p>
                </div>
                <div>
                    <label for="bot-{{ $key }}-username" class="sa-label">نام ربات (اختیاری)</label>
                    <input id="bot-{{ $key }}-username" name="username" dir="ltr" value="{{ old('messenger') === $key ? old('username') : '' }}" placeholder="{{ $bot['usernames'][$key] ? '@'.$bot['usernames'][$key] : 'Mahru_Bot' }}" class="sa-input" style="text-align: left;">
                </div>
                @if ($key === 'telegram')
                    <div>
                        <label for="bot-telegram-api" class="sa-label">آدرس پراکسی API تلگرام (Worker کلودفلر)</label>
                        <input id="bot-telegram-api" name="api_base" type="url" dir="ltr" value="{{ old('api_base', $bot['api_bases']['telegram'] ?? '') }}" placeholder="https://api.telegram.org" class="sa-input" style="text-align: left;">
                    </div>
                @endif
                @error('bot_'.$key)
                    <p class="text-sm" style="color: var(--sa-danger);">{{ $message }}</p>
                @enderror
                <div class="flex items-center gap-4">
                    <button type="submit" class="sa-btn">ذخیره</button>
                    @if (($bot['sources'][$key] ?? null) === 'panel')
                        <button type="submit" name="remove" value="1" class="text-sm" style="color: var(--sa-danger);" onclick="return confirm('توکن پنل حذف شود؟ (مقدار .env به کار می‌رود)')">حذف توکن پنل</button>
                    @endif
                </div>
            </form>
        @endforeach
    </div>
    @endif
    </div>
    </div>
@endsection
