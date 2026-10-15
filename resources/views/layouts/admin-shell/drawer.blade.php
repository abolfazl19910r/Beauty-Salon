{{--
    ⭐ بسته‌ی «و»: منوی کناری پنل مدیر از App\Support\Admin\AdminMenu. در قالب‌های «منوی کناری» و «فشرده» روی دسکتاپ دیده می‌شود؛
    در «ریل آیکونی» و «منوی بالایی» فقط کشوی موبایل است (دکمه‌ی ☰ هدر).
--}}
<aside id="admin-drawer" class="adm-drawer min-h-screen shadow-sm border-l overflow-y-auto hidden {{ $desktop ? 'md:block' : '' }}"
       style="background: var(--admin-surface); border-color: var(--admin-border);">
    <div class="p-4 border-b" style="border-color: var(--admin-border);">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-bold flex items-center" style="color: var(--admin-text);">
                <svg class="w-8 h-8 ml-2" style="color: var(--admin-accent);" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="8.5" cy="7" r="4"></circle>
                    <path d="M20 8v6M23 11h-6"></path>
                </svg>
                <span style="color: var(--admin-accent);">پنل مدیریت</span>
            </h2>
        </div>
        {{-- ⭐ ۲۰۲۶-۰۹-۲۴: لوگو و نام همین سالن (وقتی CurrentSalon داریم)؛ سوپرادمین همون متن قبلی رو می‌بینه. --}}
        @if ($adminSalon)
            <div class="flex items-center gap-2 mt-3">
                <x-brand-emblem class="w-9 h-9 rounded" pad="3px" />
                <span class="text-sm font-bold" style="color: var(--admin-text);">{{ $adminSalon->name }}</span>
            </div>
        @else
            <p class="text-xs mt-2" style="color: var(--admin-text-light);">سیستم مدیریت سالن زیبایی</p>
        @endif
    </div>

    <nav class="mt-4 px-2" aria-label="منوی پنل مدیر">
        @foreach ($menu as $group)
            <div class="py-2">
                <h3 class="text-xs font-semibold px-3 mb-2 uppercase tracking-wider" style="color: var(--admin-text-light);">{{ $group['label'] }}</h3>
                @foreach ($group['items'] as $item)
                    <a href="{{ $item['url'] }}" data-menu-key="{{ $item['key'] }}" @if ($item['active']) aria-current="page" @endif
                       class="adm-link flex items-center px-3 py-2.5 mb-1 text-sm font-medium rounded-lg transition-colors {{ $item['active'] ? 'sidebar-active' : '' }}">
                        <svg class="w-5 h-5 ml-2 opacity-75 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $item['icon'] !!}</svg>
                        <span class="flex-1">{{ $item['label'] }}</span>
                        @if ($item['lock'])
                            <span class="mr-auto text-xs" title="در پلن سالن شما نیست">🔒</span>
                        @endif
                        @if ($item['badge'])
                            <span id="{{ $item['badge'] }}" data-notif-badge class="mr-2 px-2.5 py-0.5 rounded-full text-xs font-medium hidden"
                                  style="background: rgb(var(--m-bad) / .14); color: rgb(var(--m-bad));"></span>
                        @endif
                    </a>
                @endforeach
            </div>
        @endforeach
        <div class="py-2">
            <form method="POST" action="{{ route('logout') }}" class="w-full">
                @csrf
                <button type="submit" class="adm-logout w-full flex items-center px-3 py-2.5 mb-1 text-sm font-medium rounded-lg transition-colors text-right">
                    <svg class="w-5 h-5 ml-2 opacity-75" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! \App\Support\Admin\AdminMenu::ICONS['logout'] !!}</svg>
                    خروج
                </button>
            </form>
        </div>
    </nav>
</aside>
