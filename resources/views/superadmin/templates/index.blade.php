@extends('layouts.superadmin')

@section('title', 'قالب‌ها')

@section('content')
    <div class="sa-card p-5 mb-5 text-sm leading-7">
        <div class="font-bold mb-1">قالب‌های ظاهری سالن‌ها</div>
        <p style="color: var(--sa-text-dim);">
            «طلایی ماهرو» قالب پیش‌فرض و رایگان همه‌ی پلن‌هاست. علاوه بر آن {{ to_persian_num((string) ($layoutTotal * $paletteTotal)) }} قالب برای
            سایت سالن، پنل مشتری، پنل متخصص و اپ‌ها هست ({{ to_persian_num((string) $layoutTotal) }} چیدمان × {{ to_persian_num((string) $paletteTotal) }} پالت،
            هر کدام تیره و روشن) و {{ to_persian_num((string) $adminTotal) }} قالب برای پنل مدیر.
            هر پلن در هر بازه‌ی اشتراک <b style="color: var(--sa-text);">چند چیدمانِ اول × چند پالتِ اول</b> را باز می‌کند، به ترتیبی که پایین چیده‌اید.
            پایان اشتراک یا پایین آمدن پلن، سالن را به پیش‌فرض برمی‌گرداند و انتخابش برای تمدید نگه داشته می‌شود.
        </p>
    </div>

    <form method="POST" action="{{ route('superadmin.templates.update') }}">
        @csrf
        @method('PUT')

        <div class="sa-card overflow-x-auto mb-5">
            <table class="w-full text-sm text-right">
                <thead>
                <tr style="color: var(--sa-text-dim);">
                    <th class="py-3 px-4">پلن</th>
                    <th class="py-3 px-4">سایت · ۱ و ۳ ماهه<div class="text-xs font-normal">چیدمان × پالت</div></th>
                    <th class="py-3 px-4">سایت · ۶ ماهه<div class="text-xs font-normal">چیدمان × پالت</div></th>
                    <th class="py-3 px-4">سایت · ۱۲ ماهه<div class="text-xs font-normal">چیدمان × پالت</div></th>
                    <th class="py-3 px-4">پنل مدیر<div class="text-xs font-normal">علاوه بر پیش‌فرض</div></th>
                </tr>
                </thead>
                <tbody>
                @foreach ($plans as $plan)
                    <tr style="border-top: 1px solid var(--sa-border);" data-template-plan="{{ $plan->key }}">
                        <td class="py-3 px-4">
                            <div class="font-bold">{{ $plan->name }}</div>
                            <div class="text-xs" style="color: var(--sa-text-dim);">{{ $plan->is_trial ? 'آزمایشی' : ($plan->is_public ? 'عمومی' : 'غیرعمومی') }}{{ $plan->is_active ? '' : ' · غیرفعال' }}</div>
                        </td>
                        @foreach ([['theme_layouts', 'theme_palettes', null], ['theme_layouts_6m', 'theme_palettes_6m', '6m'], ['theme_layouts_12m', 'theme_palettes_12m', '12m']] as [$lf, $pf, $period])
                            <td class="py-3 px-4 whitespace-nowrap">
                                <input type="number" min="0" max="{{ $layoutTotal }}" name="plans[{{ $plan->id }}][{{ $lf }}]" class="sa-input" style="width: 4.2rem;"
                                       aria-label="چیدمان {{ $plan->name }}" value="{{ old("plans.$plan->id.$lf", $plan->$lf) }}">
                                <span style="color: var(--sa-text-dim);">×</span>
                                <input type="number" min="0" max="{{ $paletteTotal }}" name="plans[{{ $plan->id }}][{{ $pf }}]" class="sa-input" style="width: 4.2rem;"
                                       aria-label="پالت {{ $plan->name }}" value="{{ old("plans.$plan->id.$pf", $plan->$pf) }}">
                                <div class="text-xs mt-1" style="color: var(--sa-text-dim);">= {{ to_persian_num((string) $plan->siteThemeAllowance($period)) }} قالب</div>
                                @error("plans.$plan->id.$lf")<p class="text-xs mt-1" style="color: var(--sa-danger);">{{ $message }}</p>@enderror
                                @error("plans.$plan->id.$pf")<p class="text-xs mt-1" style="color: var(--sa-danger);">{{ $message }}</p>@enderror
                            </td>
                        @endforeach
                        <td class="py-3 px-4">
                            <input type="number" min="0" max="{{ $adminTotal }}" name="plans[{{ $plan->id }}][theme_admin_count]" class="sa-input" style="width: 4.2rem;"
                                   aria-label="قالب پنل مدیر {{ $plan->name }}" value="{{ old("plans.$plan->id.theme_admin_count", $plan->theme_admin_count) }}">
                            <span class="text-xs" style="color: var(--sa-text-dim);">از {{ to_persian_num((string) $adminTotal) }}</span>
                            @error("plans.$plan->id.theme_admin_count")<p class="text-xs mt-1" style="color: var(--sa-danger);">{{ $message }}</p>@enderror
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="grid md:grid-cols-2 gap-5 mb-5">
            <div class="sa-card p-5">
                <div class="font-bold mb-1">ترتیب باز شدن چیدمان‌ها</div>
                <p class="text-xs mb-3" style="color: var(--sa-text-dim);">پلنی که «۲ چیدمان» دارد دو چیدمانِ اول این فهرست را می‌گیرد.</p>
                @foreach (array_keys($layouts) as $i => $current)
                    <label class="flex items-center gap-3 mb-2 text-sm">
                        <span style="width: 1.5rem; color: var(--sa-text-dim);">{{ to_persian_num((string) ($i + 1)) }}</span>
                        <select name="layout_order[]" class="sa-input" aria-label="چیدمان شماره‌ی {{ $i + 1 }}">
                            @foreach (config('themes.layouts') as $key => $layout)
                                <option value="{{ $key }}" @selected(old("layout_order.$i", $current) === $key)>{{ $layout['name'] }}</option>
                            @endforeach
                        </select>
                    </label>
                @endforeach
                @error('layout_order')<p class="text-xs mt-1" style="color: var(--sa-danger);">{{ $message }}</p>@enderror
            </div>
            <div class="sa-card p-5">
                <div class="font-bold mb-1">ترتیب باز شدن پالت‌ها</div>
                <p class="text-xs mb-3" style="color: var(--sa-text-dim);">«پالت اول» همان است که حرفه‌ای و کامل در ۱ و ۳ ماهه می‌گیرند.</p>
                @foreach (array_keys($palettes) as $i => $current)
                    <label class="flex items-center gap-3 mb-2 text-sm">
                        <span style="width: 1.5rem; color: var(--sa-text-dim);">{{ to_persian_num((string) ($i + 1)) }}</span>
                        <select name="palette_order[]" class="sa-input" aria-label="پالت شماره‌ی {{ $i + 1 }}">
                            @foreach (config('themes.palettes') as $key => $palette)
                                <option value="{{ $key }}" @selected(old("palette_order.$i", $current) === $key)>{{ $palette['name'] }}</option>
                            @endforeach
                        </select>
                    </label>
                @endforeach
                @error('palette_order')<p class="text-xs mt-1" style="color: var(--sa-danger);">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="mb-6"><button type="submit" class="sa-btn">ذخیره</button></div>
    </form>

    <div class="sa-card p-5">
        <div class="font-bold mb-3">قالب‌ها و تعداد سالن‌هایی که انتخابشان کرده‌اند</div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3 text-sm">
            <div class="p-3 rounded-lg" style="border: 1px solid var(--sa-border);" data-template-usage="mahru">
                <div class="flex items-center gap-2"><span class="inline-block w-3 h-3 rounded-full" style="background: {{ config('themes.mahru.swatch') }}"></span><b>{{ config('themes.mahru.name') }}</b></div>
                <div class="text-xs mt-1" style="color: var(--sa-text-dim);">پیش‌فرض · {{ to_persian_num((string) ($usage['mahru'] ?? 0)) }} انتخاب صریح</div>
            </div>
            @foreach ($layouts as $lk => $layout)
                @foreach ($palettes as $pk => $palette)
                    <div class="p-3 rounded-lg" style="border: 1px solid var(--sa-border);" data-template-usage="{{ $lk }}:{{ $pk }}">
                        <div class="flex items-center gap-2"><span class="inline-block w-3 h-3 rounded-full" style="background: {{ $palette['swatch'] }}"></span><b>{{ $layout['name'] }} · {{ $palette['name'] }}</b></div>
                        <div class="text-xs mt-1" style="color: var(--sa-text-dim);">{{ to_persian_num((string) ($usage["$lk:$pk"] ?? 0)) }} سالن</div>
                    </div>
                @endforeach
            @endforeach
        </div>
        <div class="font-bold mt-5 mb-3">قالب‌های پنل مدیر</div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3 text-sm">
            @foreach ($adminThemes as $key => $theme)
                <div class="p-3 rounded-lg" style="border: 1px solid var(--sa-border);">
                    <b>{{ $theme['name'] }}</b>@if ($loop->first) <span class="text-xs" style="color: var(--sa-text-dim);">(پیش‌فرض)</span>@endif
                    <div class="text-xs mt-1" style="color: var(--sa-text-dim);">{{ $theme['description'] }}</div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
