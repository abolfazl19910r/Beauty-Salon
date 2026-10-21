@extends('layouts.superadmin')

@section('title', 'اعلان اپ (پوش)')

@section('content')
    @php
        $fa = fn ($n) => to_persian_num(number_format((float) $n));
        $sec = fn ($s) => $s === null ? '—' : ($s < 60 ? to_persian_num((string) $s).' ثانیه' : to_persian_num((string) round($s / 60, 1)).' دقیقه');
        $reasons = ['shadow' => 'دوره‌ی آزمایشی (همیشه)', 'no_receipt' => 'بدون رسید در مهلت', 'unhealthy' => 'دستگاه ناسالم (فوری)', 'outage' => 'قطعی FCM'];
        $urgencies = ['urgent' => 'فوری ۱ دقیقه', 'normal' => 'عادی ۱۰ دقیقه', 'info' => 'فقط پوش'];
    @endphp

    @include('superadmin.push.partials.tabs')

    <div class="sa-card p-5 mb-6 flex flex-wrap items-center justify-between gap-3" data-push-mode="{{ $mode }}">
        <div>
            <div class="sa-label">حالت فعلی</div>
            <div class="font-bold">{{ $modeLabel }}</div>
            @unless ($fcmConfigured)
                <div class="text-sm mt-1" style="color: var(--sa-danger);">کلید حساب سرویس فایربیس روی سرور تنظیم نشده؛ هیچ پوشی فرستاده نمی‌شود.</div>
            @endunless
        </div>
        <div class="flex items-center gap-2 text-sm">
            @foreach ([1 => 'امروز', 7 => '۷ روز', 30 => '۳۰ روز', 90 => '۹۰ روز'] as $d => $label)
                <a href="{{ route('superadmin.push.index', ['days' => $d]) }}" class="px-3 py-1 rounded-full" style="border: 1px solid var(--sa-border); {{ $summary['days'] === $d ? 'background: var(--sa-soft); color: var(--sa-accent);' : '' }}">{{ $label }}</a>
            @endforeach
            <a href="{{ route('superadmin.settings.edit') }}#push-mode" class="sa-btn">تغییر حالت</a>
        </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="sa-card sa-stat">
            <div class="sa-stat__label">پیام‌ها</div>
            <div class="sa-stat__value">{{ $fa($summary['total']) }}</div>
        </div>
        <div class="sa-card sa-stat" data-ack-rate>
            <div class="sa-stat__label">نرخ رسید</div>
            <div class="sa-stat__value" style="color: var(--sa-accent);">{{ $summary['ack_rate'] === null ? '—' : to_persian_num((string) $summary['ack_rate']).'٪' }}</div>
        </div>
        <div class="sa-card sa-stat">
            <div class="sa-stat__label">زمان رسید (میانه / ۹۰٪)</div>
            <div class="sa-stat__value">{{ $sec($summary['median_seconds']) }} <span class="text-sm font-normal" style="color: var(--sa-text-dim);">/ {{ $sec($summary['p90_seconds']) }}</span></div>
        </div>
        <div class="sa-card sa-stat" data-sms-saved>
            @if ($mode === 'shadow')
                <div class="sa-stat__label">اگر «فعال» بود، پیامک لازم نمی‌شد</div>
                <div class="sa-stat__value">{{ $fa($summary['shadow_would_save']['count']) }} <span class="text-xs font-normal" style="color: var(--sa-text-dim);">پیام · {{ $fa($summary['shadow_would_save']['parts']) }} قطعه</span></div>
            @else
                <div class="sa-stat__label">پیامک صرفه‌جویی‌شده</div>
                <div class="sa-stat__value">{{ $fa($summary['saved_count']) }} <span class="text-xs font-normal" style="color: var(--sa-text-dim);">پیام · {{ $fa($summary['saved_parts']) }} قطعه</span></div>
            @endif
        </div>
    </div>

    <div class="grid md:grid-cols-2 gap-6 mb-6">
        <div class="sa-card p-5">
            <h2 class="font-bold mb-3">پیامک‌های رفته</h2>
            <table class="w-full text-sm text-right">
                <tbody>
                @forelse ($summary['sms'] as $reason => $row)
                    <tr style="border-top: 1px solid var(--sa-border);">
                        <td class="py-2">{{ $reasons[$reason] ?? $reason }}</td>
                        <td class="py-2">{{ $fa($row['count']) }} پیام</td>
                        <td class="py-2" style="color: var(--sa-text-dim);">{{ $fa($row['parts']) }} قطعه</td>
                    </tr>
                @empty
                    <tr><td class="py-2" style="color: var(--sa-text-dim);">هنوز پیامکی به‌جای پوش نرفته است.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="sa-card p-5">
            <h2 class="font-bold mb-3">گوشی‌ها</h2>
            <div class="text-sm grid gap-1">
                <div>اپ «ماهرو» (مشتری): {{ $fa($devices['active']['customer'] ?? 0) }}</div>
                <div>اپ «ماهرو همکار»: {{ $fa($devices['active']['staff'] ?? 0) }}</div>
                <div>هنوز هیچ رسیدی نفرستاده: <a href="{{ route('superadmin.push.devices.index', ['status' => 'never_acked']) }}" style="color: var(--sa-accent);">{{ $fa($devices['never_acked']) }}</a></div>
                @foreach ($devices['disabled'] as $reason => $count)
                    <div style="color: var(--sa-text-dim);">غیرفعال ({{ $reason === 'invalid_token' ? 'اپ حذف یا داده پاک شد' : $reason }}): {{ $fa($count) }}</div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="sa-card mb-6 overflow-x-auto">
        <h2 class="font-bold px-5 pt-5 pb-3">به تفکیک پیام</h2>
        <table class="w-full text-sm text-right">
            <thead><tr style="color: var(--sa-text-dim);"><th class="py-2 px-4">پیام</th><th class="py-2 px-4">دسته</th><th class="py-2 px-4">تعداد</th><th class="py-2 px-4">نرخ رسید</th><th class="py-2 px-4">پیامک رفته</th></tr></thead>
            <tbody>
            @forelse ($summary['by_event'] as $row)
                <tr style="border-top: 1px solid var(--sa-border);">
                    <td class="py-2 px-4">{{ $row['title'] }}</td>
                    <td class="py-2 px-4">{{ $urgencies[$row['urgency']] ?? $row['urgency'] }}</td>
                    <td class="py-2 px-4">{{ $fa($row['count']) }}</td>
                    <td class="py-2 px-4">{{ $row['ack_rate'] === null ? '—' : to_persian_num((string) $row['ack_rate']).'٪' }}</td>
                    <td class="py-2 px-4">{{ $fa($row['sms']) }}</td>
                </tr>
            @empty
                <tr><td class="py-3 px-4" style="color: var(--sa-text-dim);" colspan="5">در این بازه پیامی به اپ‌ها نرفته است.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="sa-card overflow-x-auto">
        <h2 class="font-bold px-5 pt-5 pb-1">به تفکیک سازنده‌ی گوشی</h2>
        <p class="px-5 pb-3 text-xs" style="color: var(--sa-text-dim);">شیائومی بدون Autostart و سامسونگ در «Deep sleeping» کمتر رسید می‌دهند (بسته‌ی ۰)؛ اپ راهنمای هر دو را دارد.</p>
        <table class="w-full text-sm text-right">
            <thead><tr style="color: var(--sa-text-dim);"><th class="py-2 px-4">سازنده</th><th class="py-2 px-4">ارسال</th><th class="py-2 px-4">پذیرفته‌ی FCM</th><th class="py-2 px-4">نرخ رسید</th></tr></thead>
            <tbody>
            @forelse ($summary['by_maker'] as $row)
                <tr style="border-top: 1px solid var(--sa-border);">
                    <td class="py-2 px-4" dir="ltr" style="text-align: right;">{{ $row['maker'] }}</td>
                    <td class="py-2 px-4">{{ $fa($row['count']) }}</td>
                    <td class="py-2 px-4">{{ $fa($row['accepted']) }}</td>
                    <td class="py-2 px-4">{{ $row['ack_rate'] === null ? '—' : to_persian_num((string) $row['ack_rate']).'٪' }}</td>
                </tr>
            @empty
                <tr><td class="py-3 px-4" style="color: var(--sa-text-dim);" colspan="4">—</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
