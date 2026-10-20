@extends('layouts.admin')
@section('title', 'QR و پوستر سالن')

{{--
    بسته‌ی ۵ (۲۰۲۶-۱۰-۲۰): QR سالن یا یک متخصص. PNG اینستاگرام در مرورگر (resources/js/salon-qr-poster.js)، PDF چاپی سمت سرور
    (SalonQrService). آدرس QR = آدرس عمومی + ?src=qr (بازدیدها پایین صفحه شمرده می‌شوند).
--}}
@section('content')
    <div class="fade-in" data-salon-qr-page>
        <div class="mb-5">
            <h1 class="text-xl font-bold flex items-center gap-2" style="color:var(--admin-text);">
                <svg class="w-5 h-5" style="color:var(--admin-accent);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">{!! \App\Support\Admin\AdminMenu::ICONS['qr'] !!}</svg>
                QR و پوستر سالن
            </h1>
            <p class="text-sm mt-0.5" style="color:var(--admin-text-dim);">
                مشتری با دوربین گوشی صفحه‌ی رزرو را باز می‌کند؛ اگر اپ ماهرو نصب باشد، خود اپ باز می‌شود.
            </p>
        </div>

        <form method="GET" action="{{ route('admin.salon-qr.index') }}" class="flex flex-wrap items-end gap-2 mb-5">
            <div>
                <label for="qr-target" class="block text-xs mb-1" style="color:var(--admin-text-dim);">QR برای</label>
                <select id="qr-target" name="specialist" onchange="this.form.submit()" class="rounded-lg px-3 py-2 text-sm"
                        style="border:1px solid var(--admin-border); background:var(--admin-surface); color:var(--admin-text); min-width: 14rem;">
                    <option value="">کل سالن (صفحه‌ی اصلی)</option>
                    @foreach ($specialists as $specialist)
                        <option value="{{ $specialist->id }}" @selected($selected?->id === $specialist->id)>متخصص: {{ $specialist->name }}</option>
                    @endforeach
                </select>
            </div>
            <noscript><button type="submit" class="px-3 py-2 rounded-lg text-sm font-bold text-m-onsolid" style="background:var(--admin-accent);">نمایش</button></noscript>
        </form>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 items-start">
            <div class="rounded-xl p-5 text-center" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                @if ($logo)
                    <img src="{{ $logo['data_uri'] }}" alt="" class="mx-auto mb-3 max-h-16 max-w-[160px] object-contain">
                @endif
                <div class="font-bold text-lg" style="color:var(--admin-text);">{{ $target['title'] }}</div>
                <div class="text-sm mt-1" style="color:var(--admin-text-dim);">{{ $target['subtitle'] }}</div>
                <div class="w-56 h-56 mx-auto mt-4 rounded-lg p-2" style="background:#fff; border:1px solid var(--admin-border);"
                     data-salon-qr="{{ $target['url'] }}" data-salon-qr-name="{{ $target['file'] }}" role="img" aria-label="QR"></div>
                <p class="text-xs mt-3 break-all" dir="ltr" style="color:var(--admin-text-dim);" data-salon-qr-url>{{ $target['url'] }}</p>
                <p class="text-sm mt-2" style="color:var(--admin-text);">کد سالن در اپ ماهرو: <span dir="ltr" class="font-mono font-bold">{{ $target['code'] }}</span></p>
            </div>

            <div class="space-y-5">
                <div class="rounded-xl p-5" style="background:var(--admin-surface); border:1px solid var(--admin-border);"
                     data-qr-poster
                     data-url="{{ $target['url'] }}" data-title="{{ $target['title'] }}" data-subtitle="{{ $target['subtitle'] }}"
                     data-code="{{ $target['code'] }}" data-brand="{{ config('brand.name') }}" data-file="{{ $target['file'] }}"
                     @if ($logo) data-logo="{{ $logo['data_uri'] }}" @endif>
                    <h2 class="font-bold mb-1" style="color:var(--admin-text);">تصویر برای اینستاگرام</h2>
                    <p class="text-xs mb-3" style="color:var(--admin-text-dim);">PNG با نام سالن، QR و کد سالن؛ استوری را با برچسب لینک هم می‌توانید بگذارید.</p>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" data-qr-poster-format="story" class="px-3 py-2 rounded-lg text-sm font-bold text-m-onsolid" style="background:var(--admin-accent);">استوری (۱۰۸۰×۱۹۲۰)</button>
                        <button type="button" data-qr-poster-format="post" class="px-3 py-2 rounded-lg text-sm font-bold text-m-onsolid" style="background:var(--admin-accent);">پست مربع (۱۰۸۰×۱۰۸۰)</button>
                        <button type="button" data-qr-poster-format="plain" class="px-3 py-2 rounded-lg text-sm font-bold" style="border:1px solid var(--admin-border); color:var(--admin-text);">فقط QR</button>
                    </div>
                </div>

                <div class="rounded-xl p-5" style="background:var(--admin-surface); border:1px solid var(--admin-border);">
                    <h2 class="font-bold mb-1" style="color:var(--admin-text);">نسخه‌ی چاپی (PDF)</h2>
                    <p class="text-xs mb-3" style="color:var(--admin-text-dim);">با رنگ تیره روی کاغذ سفید چاپ کنید؛ QR کوچک‌تر از ۳ سانتی‌متر نشود.</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach (\App\Services\Qr\SalonQrService::LAYOUTS as $layout => $label)
                            <a href="{{ route('admin.salon-qr.pdf', array_filter(['layout' => $layout, 'specialist' => $selected?->id])) }}" target="_blank" rel="noopener"
                               data-qr-pdf="{{ $layout }}"
                               class="px-3 py-2 rounded-lg text-sm font-bold" style="border:1px solid var(--admin-border); color:var(--admin-text);">{{ $label }}</a>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-xl p-5" style="background:var(--admin-surface); border:1px solid var(--admin-border);" data-qr-scans>
                    <h2 class="font-bold mb-2" style="color:var(--admin-text);">بازدید از QR در ۳۰ روز اخیر</h2>
                    <p class="text-sm" style="color:var(--admin-text);">QR سالن: <span class="persian-number font-bold">{{ to_persian_num((string) $scans['salon']) }}</span></p>
                    @if ($scans['specialists'] !== [])
                        <ul class="text-sm mt-2 space-y-1" style="color:var(--admin-text-dim);">
                            @foreach ($specialists as $specialist)
                                @if (isset($scans['specialists'][$specialist->id]))
                                    <li>QR {{ $specialist->name }}: <span class="persian-number">{{ to_persian_num((string) $scans['specialists'][$specialist->id]) }}</span></li>
                                @endif
                            @endforeach
                        </ul>
                    @endif
                    <p class="text-xs mt-2" style="color:var(--admin-text-light);">هر گوشی در هر روز یک بار شمرده می‌شود.</p>
                </div>
            </div>
        </div>
    </div>

    @vite('resources/js/salon-qr.js')
    @vite('resources/js/salon-qr-poster.js')
@endsection
