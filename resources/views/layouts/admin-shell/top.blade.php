{{--
    ⭐ بسته‌ی «و» — قالب پنل مدیر «منوی بالایی»: هر گروه AdminMenu یک منوی کشویی در نوار افقی زیر هدر؛ تمام عرض برای محتوا.
    روی موبایل همان کشوی کناری (دکمه‌ی ☰).
--}}
<nav class="adm-top hidden md:flex" aria-label="منوی پنل مدیر">
    @foreach ($menu as $group)
        @php $groupActive = collect($group['items'])->contains('active', true); @endphp
        @if (count($group['items']) === 1)
            @php $item = $group['items'][0]; @endphp
            <a href="{{ $item['url'] }}" data-menu-key="{{ $item['key'] }}" class="adm-top__link {{ $item['active'] ? 'is-active' : '' }}" @if ($item['active']) aria-current="page" @endif>{{ $item['label'] }}</a>
        @else
            <details class="adm-top__group" data-adm-top-group>
                <summary class="adm-top__link {{ $groupActive ? 'is-active' : '' }}">
                    {{ $group['label'] }}
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
                </summary>
                <div class="adm-top__panel">
                    @foreach ($group['items'] as $item)
                        <a href="{{ $item['url'] }}" data-menu-key="{{ $item['key'] }}" class="adm-top__item {{ $item['active'] ? 'is-active' : '' }}" @if ($item['active']) aria-current="page" @endif>
                            <svg class="w-4 h-4 shrink-0 opacity-75" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $item['icon'] !!}</svg>
                            <span class="flex-1">{{ $item['label'] }}</span>
                            @if ($item['lock'])<span title="در پلن سالن شما نیست">🔒</span>@endif
                            @if ($item['badge'])<span data-notif-badge class="adm-rail__badge hidden"></span>@endif
                        </a>
                    @endforeach
                </div>
            </details>
        @endif
    @endforeach
</nav>
