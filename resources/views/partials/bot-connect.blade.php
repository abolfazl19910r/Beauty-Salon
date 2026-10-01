{{--
    ربات پلتفرم بله/تلگرام (۲۰۲۶-۱۰-۰۱): کارت اتصال در پروفایل مدیر، متخصص و مشتری.
    $routePrefix: admin.profile.bot | specialist.profile.bot | profile.bot (مشتری، زیر /s/{slug})
--}}
@php
    $botClient = app(\App\Services\Bot\BotClient::class);
    $botMessengers = $botClient->enabledMessengers();
    $botLinks = auth()->check() ? \App\Models\BotLink::where('user_id', auth()->id())->pluck('messenger')->all() : [];
    $botLabels = ['bale' => 'بله', 'telegram' => 'تلگرام'];
    $botConnect = session('bot_connect');
    $botRouteParams = isset($salonSlug) ? ['salon_slug' => $salonSlug] : (request()->route('salon_slug') ? ['salon_slug' => request()->route('salon_slug')] : []);
@endphp
@if ($botMessengers !== [])
    <div class="max-w-4xl mx-auto mt-6 px-4" id="bot-connect">
        <div class="rounded-xl border border-gray-200 bg-white p-5 text-sm">
            <h3 class="font-semibold mb-1">دریافت اعلان‌ها در پیام‌رسان</h3>
            <p class="text-gray-500 mb-4">با اتصال به ربات ماهرو، اعلان‌های نوبت و حساب شما در بله یا تلگرام هم می‌آید.</p>

            @if ($botConnect)
                <div class="mb-4 rounded-lg bg-blue-50 border border-blue-200 p-3">
                    @if ($botConnect['url'])
                        <a href="{{ $botConnect['url'] }}" target="_blank" rel="noopener" class="font-semibold text-blue-700 underline">باز کردن ربات در {{ $botLabels[$botConnect['messenger']] ?? $botConnect['messenger'] }} و زدن Start</a>
                        <div class="mt-2 text-gray-600">اگر لینک باز نشد، این را به ربات بفرستید:</div>
                    @else
                        <div class="text-gray-600">این را به ربات {{ $botLabels[$botConnect['messenger']] ?? $botConnect['messenger'] }} ماهرو بفرستید:</div>
                    @endif
                    <code dir="ltr" class="inline-block mt-1 px-2 py-1 bg-white rounded border">/start {{ $botConnect['code'] }}</code>
                    <div class="mt-1 text-xs text-gray-500">این کد یک بار مصرف است و {{ $botConnect['minutes'] }} دقیقه اعتبار دارد.</div>
                </div>
            @endif

            <div class="space-y-2">
                @foreach ($botMessengers as $messenger)
                    <div class="flex items-center justify-between gap-3">
                        <span>{{ $botLabels[$messenger] ?? $messenger }}:
                            @if (in_array($messenger, $botLinks, true))
                                <span class="text-green-700">وصل است</span>
                            @else
                                <span class="text-gray-500">وصل نیست</span>
                            @endif
                        </span>
                        @if (in_array($messenger, $botLinks, true))
                            <form method="POST" action="{{ route($routePrefix.'.disconnect', $botRouteParams + ['messenger' => $messenger]) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-3 py-1 rounded-lg border border-red-300 text-red-700">قطع اتصال</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route($routePrefix.'.connect', $botRouteParams + ['messenger' => $messenger]) }}">
                                @csrf
                                <button type="submit" class="px-3 py-1 rounded-lg bg-blue-600 text-white">اتصال به ربات</button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif
