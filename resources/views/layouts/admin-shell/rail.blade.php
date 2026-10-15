{{--
    ⭐ بسته‌ی «و» — قالب پنل مدیر «ریل آیکونی»: منوی جمع‌شده به آیکون؛ با نشانگر یا Tab روی آن باز می‌شود و برچسب‌ها را
    روی محتوا نشان می‌دهد (محتوا جابه‌جا نمی‌شود). همان آیتم‌های AdminMenu.
--}}
<div class="adm-rail-slot hidden md:block">
    <nav class="adm-rail" aria-label="منوی پنل مدیر">
        <a href="{{ route('admin.dashboard') }}" class="adm-rail__brand" aria-label="پنل مدیریت {{ $adminSalon?->name }}">
            @if ($adminSalon)
                <x-brand-emblem class="w-9 h-9 rounded" pad="3px" />
            @else
                <span class="adm-rail__mark">م</span>
            @endif
            <span class="adm-rail__label font-bold">{{ $adminSalon?->name ?? 'پنل مدیریت' }}</span>
        </a>
        @foreach ($menu as $group)
            <div class="adm-rail__group">
                <span class="adm-rail__glabel">{{ $group['label'] }}</span>
                @foreach ($group['items'] as $item)
                    <a href="{{ $item['url'] }}" data-menu-key="{{ $item['key'] }}" title="{{ $item['label'] }}"
                       class="adm-rail__item {{ $item['active'] ? 'is-active' : '' }}" @if ($item['active']) aria-current="page" @endif>
                        <svg class="w-5 h-5 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $item['icon'] !!}</svg>
                        <span class="adm-rail__label">{{ $item['label'] }}@if ($item['lock']) <span title="در پلن سالن شما نیست">🔒</span>@endif</span>
                        @if ($item['badge'])
                            <span data-notif-badge class="adm-rail__badge hidden"></span>
                        @endif
                    </a>
                @endforeach
            </div>
        @endforeach
        <form method="POST" action="{{ route('logout') }}" class="adm-rail__group">
            @csrf
            <button type="submit" class="adm-rail__item adm-rail__logout" title="خروج">
                <svg class="w-5 h-5 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! \App\Support\Admin\AdminMenu::ICONS['logout'] !!}</svg>
                <span class="adm-rail__label">خروج</span>
            </button>
        </form>
    </nav>
</div>
