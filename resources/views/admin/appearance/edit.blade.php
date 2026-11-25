@extends('layouts.admin')
@section('title', 'ظاهر سالن')

{{--
    ⭐ «ظاهر سالن» (بسته‌ی «و») — AdminAppearanceController. قالب سایت روی سایت سالن، پنل مشتری، پنل متخصص و هر دو اپ اثر دارد؛
    قالب پنل مدیر فقط روی همین پنل. قفل‌شده‌ها پیش‌نمایش دارند ولی انتخاب نمی‌شوند.
--}}
@php
    $periodLabels = ['1m' => 'یک‌ماهه', '3m' => 'سه‌ماهه', '6m' => 'شش‌ماهه', '12m' => 'یک‌ساله'];
    $mahruTokens = \App\Support\Themes\ThemeCatalog::sitePaletteTokens(null);
    $selectedSite = old('site_theme', $salon->site_theme ?? 'mahru');
    $selectedAdmin = old('admin_theme', $salon->admin_theme ?? 'side');
    $previewBase = $salon->publicUrl();
@endphp

@section('content')
    <style>
        .ap-card{position:relative;display:block;border:1px solid var(--admin-border);border-radius:16px;padding:10px;background:var(--admin-surface);cursor:pointer;transition:border-color .2s,box-shadow .2s}
        .ap-card:hover{border-color:rgb(var(--m-gold) / .5)}
        .ap-card input{position:absolute;opacity:0;pointer-events:none}
        .ap-card:has(input:checked){border-color:rgb(var(--m-gold));box-shadow:0 0 0 3px rgb(var(--m-gold) / .2)}
        .ap-card:has(input:focus-visible){outline:2px solid rgb(var(--m-gold));outline-offset:2px}
        .ap-card.is-locked{cursor:default}
        .ap-card.is-locked .ap-pair{opacity:.55}
        .ap-pair{display:grid;grid-template-columns:1fr 1fr;gap:6px}
        .ap-badge{font-size:11px;font-weight:700;padding:2px 8px;border-radius:999px}
        /* طرح‌های کوچک: admin/appearance/mini-styles (بسته‌ی ۵الف، مشترک با «قالب‌ها»ی ابرمدیر) */
    </style>
    @include('admin.appearance.mini-styles')

    <div class="max-w-6xl mx-auto">
        <div class="mb-5">
            <h1 class="text-xl font-bold" style="color: var(--admin-text);">ظاهر سالن</h1>
            <p class="text-sm mt-1 leading-7" style="color: var(--admin-text-dim);">
                قالب سایت روی سایت رزرو سالن، پنل مشتری، پنل کارشناس و هر دو اپ اثر می‌گذارد؛ هر قالب نسخه‌ی تیره و روشن دارد و هر کس
                با دکمه‌ی تم خودش بینشان جابه‌جا می‌شود. قالب پنل مدیر فقط ظاهر همین پنل را عوض می‌کند و همه‌ی صفحه‌ها و دسترسی‌ها در هر قالب یکی است.
            </p>
        </div>

        <div class="rounded-xl p-4 mb-5 text-sm leading-7" style="background: var(--admin-accent-light); color: var(--admin-text);" data-appearance-allowance>
            پلن <b>{{ $plan?->name ?? 'کامل' }}</b>@if ($appearance->period) · اشتراک {{ $periodLabels[$appearance->period] ?? $appearance->period }}@endif:
            <b>{{ to_persian_num((string) count($appearance->allowedSite)) }}</b> قالب سایت و
            <b>{{ to_persian_num((string) (count($appearance->allowedAdmin) - 1)) }}</b> قالب پنل مدیر علاوه بر پیش‌فرض باز است.
            @if (count($appearance->allowedSite) < \App\Support\Themes\ThemeCatalog::siteTotal())
                <a href="{{ route('admin.billing.index') }}" class="font-bold underline" style="color: var(--admin-accent);">ارتقا یا اشتراک بلندتر</a> قالب‌های بیشتری باز می‌کند.
            @endif
        </div>

        @if ($appearance->siteChoiceSuspended() || $appearance->adminChoiceSuspended())
            <div class="rounded-xl p-4 mb-5 text-sm" style="background: rgb(var(--m-warn) / .14); color: rgb(var(--m-warn));" data-appearance-suspended>
                قالبی که انتخاب کرده‌اید در پلن یا بازه‌ی فعلی باز نیست؛ تا تمدید یا ارتقا، قالب پیش‌فرض نمایش داده می‌شود و انتخاب‌تان ذخیره می‌ماند.
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-lg p-3 mb-4 text-sm" style="background:rgb(var(--m-bad) / .14); color:rgb(var(--m-bad));">
                @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('admin.appearance.update') }}">
            @csrf
            @method('PUT')

            <h2 class="font-bold text-lg mb-3" style="color: var(--admin-text);">قالب سایت، پنل مشتری، پنل کارشناس و اپ‌ها</h2>

            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
                <label class="ap-card" data-site-theme="mahru">
                    <input type="radio" name="site_theme" value="mahru" @checked($selectedSite === 'mahru')>
                    <div class="ap-pair">
                        @include('admin.appearance.mini', ['layout' => 'mahru', 'tokens' => $mahruTokens['dark']])
                        @include('admin.appearance.mini', ['layout' => 'mahru', 'tokens' => $mahruTokens['light']])
                    </div>
                    <div class="flex items-center justify-between mt-2 text-sm">
                        <b style="color: var(--admin-text);">{{ config('themes.mahru.name') }}</b>
                        <span class="ap-badge" style="background: rgb(var(--m-ok) / .14); color: rgb(var(--m-ok));">پیش‌فرض، رایگان</span>
                    </div>
                    <a href="{{ $previewBase }}?theme_preview=mahru" target="_blank" rel="noopener" class="text-xs mt-1 inline-block" style="color: var(--admin-accent);">پیش‌نمایش در سایت ←</a>
                </label>
            </div>

            @foreach ($layouts as $lk => $layout)
                <div class="mb-6">
                    <div class="flex items-baseline gap-3 mb-2">
                        <h3 class="font-bold" style="color: var(--admin-text);">{{ $layout['name'] }}</h3>
                        <span class="text-xs" style="color: var(--admin-text-dim);">{{ $layout['description'] }}</span>
                    </div>
                    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        @foreach ($palettes as $pk => $palette)
                            @php
                                $key = "$lk:$pk";
                                $open = $appearance->siteAllowed($key);
                                $selectable = $open || $salon->site_theme === $key;
                            @endphp
                            <label class="ap-card {{ $open ? '' : 'is-locked' }}" data-site-theme="{{ $key }}" data-locked="{{ $open ? '0' : '1' }}">
                                <input type="radio" name="site_theme" value="{{ $key }}" @checked($selectedSite === $key) @disabled(! $selectable)>
                                <div class="ap-pair">
                                    @include('admin.appearance.mini', ['layout' => $lk, 'tokens' => $palette['dark']])
                                    @include('admin.appearance.mini', ['layout' => $lk, 'tokens' => $palette['light']])
                                </div>
                                <div class="flex items-center justify-between mt-2 text-sm gap-2">
                                    <b class="flex items-center gap-2" style="color: var(--admin-text);"><span class="inline-block w-3 h-3 rounded-full" style="background: {{ $palette['swatch'] }}"></span><span class="inline-block w-3 h-3 rounded-full -mr-3" style="background: rgb({{ $palette['light']['accent2'] }})" data-accent2-swatch></span>{{ $palette['name'] }}</b>
                                    @if ($open)
                                        @if ($appearance->site === $key)<span class="ap-badge" style="background: rgb(var(--m-gold) / .14); color: rgb(var(--m-gold));">فعال</span>@endif
                                    @else
                                        <span class="ap-badge" style="background: rgb(var(--m-mu) / .14); color: var(--admin-text-dim);">🔒 با ارتقا باز می‌شود</span>
                                    @endif
                                </div>
                                <a href="{{ $previewBase }}?theme_preview={{ $key }}" target="_blank" rel="noopener" class="text-xs mt-1 inline-block" style="color: var(--admin-accent);">پیش‌نمایش در سایت ←</a>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <h2 class="font-bold text-lg mb-3 mt-8" style="color: var(--admin-text);">قالب پنل مدیر</h2>
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
                @foreach ($adminThemes as $ak => $theme)
                    @php
                        $open = $appearance->adminAllowed($ak);
                        $selectable = $open || $salon->admin_theme === $ak;
                        $tokens = \App\Support\Themes\ThemeCatalog::adminPaletteTokens($ak) ?? $mahruTokens;
                    @endphp
                    <label class="ap-card {{ $open ? '' : 'is-locked' }}" data-admin-theme="{{ $ak }}" data-locked="{{ $open ? '0' : '1' }}">
                        <input type="radio" name="admin_theme" value="{{ $ak }}" @checked($selectedAdmin === $ak) @disabled(! $selectable)>
                        <div class="ap-pair">
                            @foreach (['dark', 'light'] as $mode)
                                @php $t = $tokens[$mode]; @endphp
                                <div class="mm" style="--mm-bg:{{ $t['bg'] }};--mm-bg2:{{ $t['bg2'] }};--mm-bg3:{{ $t['bg3'] }};--mm-tx:{{ $t['tx'] }};--mm-mu:{{ $t['mu'] }};--mm-ac:{{ $t['gold'] }};display:flex;{{ $ak === 'top' ? 'flex-direction:column;' : '' }}" aria-hidden="true">
                                    @if ($ak === 'top')
                                        <span style="height:14px;background:rgb(var(--mm-bg2));border-bottom:1px solid rgb(var(--mm-mu) / .25);display:flex;gap:4px;align-items:center;padding:0 5px"><i class="mm-bar" style="width:14px;background:rgb(var(--mm-ac))"></i><i class="mm-bar" style="width:14px"></i><i class="mm-bar" style="width:14px"></i></span>
                                    @else
                                        <span style="width:{{ ['side' => '30px', 'rail' => '12px', 'dense' => '24px'][$ak] }};background:rgb(var(--mm-bg2));border-left:1px solid rgb(var(--mm-mu) / .25);display:flex;flex-direction:column;gap:4px;padding:5px 4px"><i class="mm-bar" style="background:rgb(var(--mm-ac))"></i><i class="mm-bar"></i><i class="mm-bar"></i><i class="mm-bar"></i></span>
                                    @endif
                                    <span style="flex:1;padding:5px;display:flex;flex-direction:column;gap:4px"><span class="mm-row"><i class="mm-blk mm-s"></i><i class="mm-blk mm-s"></i><i class="mm-blk mm-s" style="background:rgb(var(--mm-ac) / .25)"></i></span><i class="mm-blk" style="flex:1;background:rgb(var(--mm-bg2))"></i></span>
                                </div>
                            @endforeach
                        </div>
                        <div class="flex items-center justify-between mt-2 text-sm gap-2">
                            <b style="color: var(--admin-text);">{{ $theme['name'] }}</b>
                            @if ($loop->first)
                                <span class="ap-badge" style="background: rgb(var(--m-ok) / .14); color: rgb(var(--m-ok));">پیش‌فرض</span>
                            @elseif (! $open)
                                <span class="ap-badge" style="background: rgb(var(--m-mu) / .14); color: var(--admin-text-dim);">🔒 با ارتقا</span>
                            @endif
                        </div>
                        <p class="text-xs mt-1 leading-6" style="color: var(--admin-text-dim);">{{ $theme['description'] }}</p>
                    </label>
                @endforeach
            </div>

            <button type="submit" class="admin-btn-primary px-6 py-2.5 text-sm font-bold rounded-lg">ذخیره</button>
        </form>
    </div>
@endsection
