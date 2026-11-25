{{-- بسته‌ی ۵الف: منوی حساب / ورود و ثبت‌نام سربرگ سایت سالن (مشترک همه‌ی چیدمان‌ها؛ متغیرها از layouts/app) --}}
@auth
    <details class="relative" data-account-menu>
        <summary class="nav-link inline-flex items-center gap-1 cursor-pointer list-none" aria-haspopup="menu">
            <span class="max-w-[9rem] truncate">{{ auth()->user()->name ?: 'حساب من' }}</span>
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
        </summary>
        <div class="absolute top-full left-0 mt-3 w-56 rounded-2xl border border-m-gold/20 bg-m-card shadow-2xl p-2 flex flex-col text-sm" role="menu">
            @if ($__isCustomer)
                @foreach ($__accountTabs as $__tab)
                    <a href="{{ $__tab['url'] }}" role="menuitem" class="flex items-center justify-between rounded-xl px-3 py-2 hover:bg-m-gold/10 {{ $__tab['active'] ? 'text-m-gold2 font-bold' : 'text-m-tx/85' }}">
                        {{ $__tab['label'] }}
                        @if ($__tab['badge'] > 0)<span class="text-xs text-m-tx/55">{{ fa_num($__tab['badge']) }} پیش رو</span>@endif
                    </a>
                @endforeach
            @elseif (auth()->user()->isSpecialistAccount())
                <a href="{{ route('specialist.my-dashboard') }}" role="menuitem" class="rounded-xl px-3 py-2 hover:bg-m-gold/10 text-m-tx/85">پنل کاری</a>
                <a href="{{ route('specialist.profile.show') }}" role="menuitem" class="rounded-xl px-3 py-2 hover:bg-m-gold/10 text-m-tx/85">پروفایل من</a>
            @endif
            <form action="{{ route('logout') }}" method="POST" class="border-t border-m-gold/15 mt-1 pt-1">
                @csrf
                <button type="submit" role="menuitem" class="w-full text-right rounded-xl px-3 py-2 hover:bg-m-bad/10 text-m-bad">خروج</button>
            </form>
        </div>
    </details>
@else
    <a href="{{ route('salon.login') }}" class="nav-link">ورود</a>
    <a href="{{ route('salon.register') }}" class="nav-link">ثبت نام</a>
@endauth
