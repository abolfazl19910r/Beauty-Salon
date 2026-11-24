{{--
    ⭐ بسته‌ی «و» — قالب پنل مدیر «منوی بالایی».
    بسته‌ی ۲ب: ردیف دوم هدر — آیتم‌های «کار روزانه» مستقیم (زیرخط برای صفحه‌ی جاری، نشان شمار)، جداکننده، ۴ گروه کشویی.
    هر کشویی کارت دوستونه: آیکون، نام و توضیح یک‌خطی هر آیتم + میانبرش. Esc می‌بندد. روی موبایل کشوی کناری (☰).
--}}
@php
    $fixed = collect($menu)->firstWhere('collapsible', false);
    $groups = collect($menu)->where('collapsible', true)->values();
    $ico = fn (string $inner, string $class) => '<svg class="'.$class.' shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$inner.'</svg>';
@endphp
<nav class="adm-top hidden md:flex" aria-label="منوی پنل مدیر">
    @foreach ($fixed['items'] ?? [] as $item)
        <a href="{{ $item['url'] }}" data-menu-key="{{ $item['key'] }}" @if ($item['shortcut']) data-shortcut="{{ $item['shortcut'] }}" @endif
           class="adm-top__link {{ $item['active'] ? 'is-active' : '' }}" @if ($item['active']) aria-current="page" @endif>
            {{ $item['label'] }}
            @if ($item['count'] > 0)<span class="adm-count">{{ fa_num($item['count']) }}</span>@endif
            @if ($item['badge'])<span data-notif-badge class="adm-count hidden"></span>@endif
        </a>
    @endforeach

    <span class="adm-top__sep" role="separator"></span>

    @foreach ($groups as $group)
        @php $groupActive = collect($group['items'])->contains('active', true); @endphp
        <details class="adm-top__group" data-adm-top-group="{{ $group['key'] }}">
            <summary class="adm-top__link {{ $groupActive ? 'is-active' : '' }}">
                {{ $group['label'] }}
                @if (collect($group['items'])->sum('count') > 0)<span class="adm-count">{{ fa_num(collect($group['items'])->sum('count')) }}</span>@endif
                <svg class="w-3.5 h-3.5 adm-top__chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
            </summary>
            <div class="adm-top__panel">
                <div class="adm-top__panel-head"><b>{{ $group['label'] }}</b><span>{{ fa_num(count($group['items'])) }} مورد</span></div>
                <div class="adm-top__cards">
                    @foreach ($group['items'] as $item)
                        <a href="{{ $item['url'] }}" data-menu-key="{{ $item['key'] }}" @if ($item['shortcut']) data-shortcut="{{ $item['shortcut'] }}" @endif
                           class="adm-top__item {{ $item['active'] ? 'is-active' : '' }}" @if ($item['active']) aria-current="page" @endif>
                            <span class="adm-top__ico">{!! $ico($item['icon'], 'w-[18px] h-[18px]') !!}</span>
                            <span class="min-w-0 flex-1">
                                <span class="adm-top__name">{{ $item['label'] }}@if ($item['lock']) <span title="در پلن سالن شما نیست">🔒</span>@endif
                                    @if ($item['count'] > 0)<span class="adm-count">{{ fa_num($item['count']) }}</span>@endif</span>
                                @if ($item['hint'])<span class="adm-top__hint">{{ $item['hint'] }}</span>@endif
                            </span>
                            @if ($item['shortcut'])<kbd dir="ltr" class="adm-top__kbd">{{ strtoupper($item['shortcut']) }}</kbd>@endif
                        </a>
                    @endforeach
                </div>
            </div>
        </details>
    @endforeach
</nav>
