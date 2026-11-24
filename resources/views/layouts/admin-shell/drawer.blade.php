{{--
    ⭐ بسته‌ی «و»: منوی کناری پنل مدیر از App\Support\Admin\AdminMenu. در قالب‌های «منوی کناری» و «فشرده» روی دسکتاپ دیده می‌شود؛
    در «ریل آیکونی» و «منوی بالایی» فقط کشوی موبایل است (دکمه‌ی ☰ هدر).
    بسته‌ی ۲ ظاهری (۲۰۲۶-۱۱-۲۴): «کار روزانه» همیشه باز با نشان شمار، ۴ گروه جمع‌شونده (گروه صفحه‌ی فعلی خودکار باز؛ بقیه را
    مرورگر به یاد می‌آورد)، کارت اشتراک پایین منو.
--}}
@php
    $subscriptionEnds = $adminSalon?->subscription_ends_at;
@endphp
<aside id="admin-drawer" data-nav-scroll class="adm-drawer min-h-screen shadow-sm border-l overflow-y-auto hidden {{ $desktop ? 'md:flex' : '' }} flex-col"
       style="background: var(--admin-surface); border-color: var(--admin-border);">
    <div class="px-4 pt-4 pb-3 border-b" style="border-color: var(--admin-border);">
        @if ($adminSalon)
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5" style="color: var(--admin-text);">
                <x-brand-emblem class="w-10 h-10 rounded" pad="3px" />
                <span class="flex flex-col min-w-0">
                    <b class="text-sm truncate">{{ $adminSalon->name }}</b>
                    <span class="text-xs truncate" style="color: var(--admin-text-light);">پنل مدیریت سالن</span>
                </span>
            </a>
            <a href="{{ $adminSalon->publicUrl() }}" target="_blank" rel="noopener" class="adm-drawer__site font-mono" dir="ltr">{{ \App\Support\SalonAddress::short($adminSalon) }}</a>
        @else
            <h2 class="text-lg font-bold" style="color: var(--admin-accent);">پنل مدیریت</h2>
            <p class="text-xs mt-1" style="color: var(--admin-text-light);">سیستم مدیریت سالن زیبایی</p>
        @endif
    </div>

    <nav class="mt-3 px-2 flex-1" aria-label="منوی پنل مدیر">
        @foreach ($menu as $group)
            @php
                $groupActive = collect($group['items'])->contains('active', true);
                $groupCount = collect($group['items'])->sum('count');
            @endphp
            @if (! $group['collapsible'])
                <div class="py-1.5" data-adm-group-fixed="{{ $group['key'] }}">
                    <h3 class="adm-drawer__glabel">{{ $group['label'] }}</h3>
                    @foreach ($group['items'] as $item)
                        @include('layouts.admin-shell.drawer-link', ['item' => $item])
                    @endforeach
                </div>
            @else
                <details class="adm-group" data-adm-group="{{ $group['key'] }}" @if ($groupActive) open data-adm-group-active @endif>
                    <summary class="adm-group__head">
                        <span class="flex-1">{{ $group['label'] }}</span>
                        @if ($groupCount > 0)<span class="adm-count">{{ fa_num($groupCount) }}</span>@endif
                        <span class="adm-group__n">{{ fa_num(count($group['items'])) }} مورد</span>
                    </summary>
                    <div class="pb-1">
                        @foreach ($group['items'] as $item)
                            @include('layouts.admin-shell.drawer-link', ['item' => $item])
                        @endforeach
                    </div>
                </details>
            @endif
        @endforeach
    </nav>

    @if ($adminSalon && $subscriptionEnds)
        <div class="adm-drawer__sub" data-drawer-subscription>
            @if ($adminSalon->isOnTrial())
                دوره‌ی آزمایشی: {{ fa_num($adminSalon->trialDaysLeft()) }} روز مانده.
            @elseif ($subscriptionEnds->isPast())
                <span style="color: rgb(var(--m-bad)); font-weight: 700;">اشتراک تمام شده است.</span>
            @else
                اشتراک تا {{ fa_date($subscriptionEnds, false, false) }}.
            @endif
            <a href="{{ route('admin.billing.index') }}" class="font-bold" style="color: var(--admin-accent);">اشتراک و صورتحساب</a>
        </div>
    @endif
</aside>
<script @nonce>
    // گروه‌های جمع‌شونده‌ی منو: باز/بسته را مرورگر به یاد می‌آورد؛ گروه صفحه‌ی فعلی همیشه باز
    (function () {
        var KEY = 'mahru-admin-menu-open';
        var saved = {};
        try { saved = JSON.parse(localStorage.getItem(KEY) || '{}') || {}; } catch (e) {}
        document.querySelectorAll('[data-adm-group]').forEach(function (group) {
            var key = group.getAttribute('data-adm-group');
            if (!group.hasAttribute('data-adm-group-active') && saved[key] === true) group.open = true;
            group.addEventListener('toggle', function () {
                saved[key] = group.open;
                try { localStorage.setItem(KEY, JSON.stringify(saved)); } catch (e) {}
            });
        });
    })();
</script>
