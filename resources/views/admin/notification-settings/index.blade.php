@extends('layouts.admin')
@section('title', 'تنظیمات اطلاع‌رسانی')

@section('content')
    <div class="fade-in">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-5">
            <div>
                <h1 class="text-xl font-bold flex items-center gap-2" style="color:var(--admin-text);">
                    <svg class="w-5 h-5" style="color:var(--admin-accent);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                    </svg>
                    تنظیمات اطلاع‌رسانی
                </h1>
                <p class="text-sm mt-0.5" style="color:var(--admin-text-dim);">
                    برای هر رویداد مشخص کنید که آیا پیامک ارسال شود، نوتیفیکیشن داخل‌برنامه‌ای ثبت شود، و/یا از طریق ربات (تلگرام/بله) اطلاع‌رسانی شود.
                    خاموش‌کردن یک ستون فقط همان کانال را برای همان رویداد غیرفعال می‌کند؛ رویدادهای دیگر تحت تأثیر قرار نمی‌گیرند.
                </p>
            </div>
        </div>

        @unless($botConfigured)
            <div class="rounded-xl p-4 mb-5 text-sm" style="background:rgba(217,119,6,0.1); border:1px solid rgba(217,119,6,0.3); color:#B45309;">
                ⚠️ هنوز توکن/چت‌آیدی هیچ رباتی (تلگرام یا بله) در تنظیمات سرور (.env) وارد نشده؛ فعال‌کردن ستون «ربات» برای یک رویداد، تا وقتی این مقادیر تنظیم نشوند، هیچ پیامی واقعاً ارسال نمی‌کند.
                متغیرهای لازم: <code dir="ltr">TELEGRAM_BOT_TOKEN</code>، <code dir="ltr">TELEGRAM_CHAT_ID</code> (یا معادل <code dir="ltr">BALE_BOT_TOKEN</code>/<code dir="ltr">BALE_CHAT_ID</code>).
            </div>
        @endunless

        <form method="POST" action="{{ route('admin.notification-settings.update') }}">
            @csrf

            @foreach($groups as $groupTitle => $events)
                <div class="rounded-xl overflow-hidden mb-6" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                    <div class="px-4 py-3" style="background-color: var(--admin-accent-light);">
                        <h2 class="text-sm font-bold" style="color:var(--admin-accent);">{{ $groupTitle }}</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                            <tr style="border-bottom:1px solid var(--admin-border);">
                                <th class="px-4 py-2.5 text-right font-medium" style="color:var(--admin-text-dim);">رویداد</th>
                                <th class="px-4 py-2.5 text-center font-medium w-28" style="color:var(--admin-text-dim);">پیامک</th>
                                <th class="px-4 py-2.5 text-center font-medium w-32" style="color:var(--admin-text-dim);">نوتیفیکیشن</th>
                                <th class="px-4 py-2.5 text-center font-medium w-24" style="color:var(--admin-text-dim);">ربات</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($events as $key => $meta)
                                @php
                                    $row = $settings->get($key);
                                    $safeKey = str_replace('.', '__', $key);
                                @endphp
                                <tr style="border-top:1px solid var(--admin-border);">
                                    <td class="px-4 py-3" style="color:var(--admin-text);">{{ $meta['label'] }}</td>
                                    <td class="px-4 py-3 text-center">
                                        @if($meta['sms'] ?? true)
                                            <input type="checkbox" name="sms[{{ $safeKey }}]" value="1"
                                                   @checked($row?->sms_enabled) class="w-4 h-4 rounded cursor-pointer" style="accent-color:var(--admin-accent);">
                                        @else
                                            <span class="text-xs" style="color:var(--admin-text-light);">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <input type="checkbox" name="database[{{ $safeKey }}]" value="1"
                                               @checked($row?->database_enabled ?? true) class="w-4 h-4 rounded cursor-pointer" style="accent-color:var(--admin-accent);">
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <input type="checkbox" name="telegram[{{ $safeKey }}]" value="1"
                                               @checked($row?->telegram_enabled) class="w-4 h-4 rounded cursor-pointer" style="accent-color:var(--admin-accent);">
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach

            <button type="submit" class="px-6 py-2.5 rounded-lg text-sm font-medium text-white" style="background-color: var(--admin-accent);">
                ذخیره‌ی تنظیمات
            </button>
        </form>
    </div>
@endsection
