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
    {{-- بسته‌ی ه: روی توکن‌های ماهرو (هر سه پنل و پروفایل مشتری؛ هر جا partials/mahru-theme هست) --}}
    <div class="max-w-4xl mx-auto mt-6 px-4" id="bot-connect">
        <div class="m-card p-5 sm:p-6 text-sm" style="border-radius:22px">
            <div class="flex items-start gap-3 mb-4">
                <span class="shrink-0 inline-flex w-10 h-10 items-center justify-center rounded-full bg-m-gold/15 text-m-gold2">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.4 8.4 0 0 1-12.3 7.4L3 21l2.1-5.6A8.5 8.5 0 1 1 21 11.5z"/><path d="M8.5 11.5h.01M12 11.5h.01M15.5 11.5h.01"/></svg>
                </span>
                <div>
                    <h3 class="font-bold text-base text-m-tx">دریافت اعلان‌ها در پیام‌رسان</h3>
                    <p class="text-m-mu mt-1 leading-6">با اتصال به ربات ماهرو، اعلان‌های نوبت و حساب شما در بله یا تلگرام هم می‌آید.</p>
                </div>
            </div>

            @if ($botConnect)
                <div class="mb-4 rounded-2xl bg-m-gold/10 border border-m-gold/25 p-4">
                    @if ($botConnect['url'])
                        <a href="{{ $botConnect['url'] }}" target="_blank" rel="noopener" class="font-bold text-m-gold2 underline underline-offset-4">باز کردن ربات در {{ $botLabels[$botConnect['messenger']] ?? $botConnect['messenger'] }} و زدن Start</a>
                        <div class="mt-2 text-m-mu">اگر لینک باز نشد، این را به ربات بفرستید:</div>
                    @else
                        <div class="text-m-mu">این را به ربات {{ $botLabels[$botConnect['messenger']] ?? $botConnect['messenger'] }} ماهرو بفرستید:</div>
                    @endif
                    <code dir="ltr" class="inline-block mt-2 px-3 py-1.5 rounded-lg bg-m-bg border border-m-gold/20 text-m-tx">/start {{ $botConnect['code'] }}</code>
                    <div class="mt-2 text-xs text-m-mu">این کد یک بار مصرف است و {{ $botConnect['minutes'] }} دقیقه اعتبار دارد.</div>
                </div>
            @endif

            <div class="divide-y divide-m-gold/10 rounded-2xl border border-m-gold/15 overflow-hidden">
                @foreach ($botMessengers as $messenger)
                    @php $botIsLinked = in_array($messenger, $botLinks, true); @endphp
                    <div class="flex items-center justify-between gap-3 px-4 py-3 bg-m-tx/[.02]">
                        <div class="flex items-center gap-2.5">
                            <span class="font-semibold text-m-tx">{{ $botLabels[$messenger] ?? $messenger }}</span>
                            @if ($botIsLinked)
                                <span class="inline-flex items-center gap-1 rounded-full bg-m-ok/12 px-2.5 py-0.5 text-xs font-semibold text-m-ok"><span class="w-1.5 h-1.5 rounded-full bg-m-ok"></span>وصل است</span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full bg-m-tx/5 px-2.5 py-0.5 text-xs text-m-mu"><span class="w-1.5 h-1.5 rounded-full bg-m-mu/60"></span>وصل نیست</span>
                            @endif
                        </div>
                        @if ($botIsLinked)
                            <form method="POST" action="{{ route($routePrefix.'.disconnect', $botRouteParams + ['messenger' => $messenger]) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-4 py-1.5 rounded-full border border-m-bad/35 text-m-bad text-xs font-semibold hover:bg-m-bad/10 transition">قطع اتصال</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route($routePrefix.'.connect', $botRouteParams + ['messenger' => $messenger]) }}">
                                @csrf
                                <button type="submit" class="m-btn px-4 py-1.5 text-xs">اتصال به ربات</button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif
