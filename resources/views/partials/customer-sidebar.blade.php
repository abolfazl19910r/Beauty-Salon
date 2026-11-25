{{-- نوار کناری حساب مشتری (آزمون ویندوز بسته‌ی ۴، تصمیم ابوالفضل): جای ردیف دوم سربرگ و نوار زبانه‌ها. فقط دسکتاپ و
     فقط صفحه‌های حساب؛ موبایل منوی پایین و منوی نام را دارد. کارت مشتری + منو با آیکون و نشان + رزرو و خروج، چسبان. --}}
@php
    $__user = auth()->user();
    $__summary = \App\Support\Customer\CustomerMenu::summary($__user);
@endphp
<aside class="hidden md:block md:sticky md:top-24 self-start" aria-label="حساب من" data-account-sidebar>
    <div class="rounded-2xl border border-m-gold/15 bg-m-bg2/70 overflow-hidden">
        <div class="p-4 border-b border-m-gold/10" data-account-card>
            <div class="flex items-center gap-3">
                <span class="w-11 h-11 shrink-0 rounded-full bg-m-gold/15 text-m-gold2 font-bold text-lg flex items-center justify-center" aria-hidden="true">{{ mb_substr($__user->name ?? '؟', 0, 1) }}</span>
                <div class="min-w-0">
                    <div class="font-bold text-m-tx truncate">{{ $__user->name }}</div>
                    @if ($__user->phone)<div class="text-xs text-m-tx/55 persian-number" dir="ltr" style="text-align:right">{{ \App\Support\Fa::digits($__user->phone) }}</div>@endif
                </div>
            </div>
            <div class="grid {{ $__summary['loyaltyOn'] ? 'grid-cols-2' : 'grid-cols-1' }} gap-2 mt-3 text-center">
                <a href="{{ route('wallet.index') }}" class="rounded-xl bg-m-tx/[0.04] hover:bg-m-gold/10 px-2 py-2" data-account-wallet>
                    <div class="text-[11px] text-m-tx/55">موجودی کیف پول</div>
                    <div class="text-sm font-bold text-m-tx">{{ fa_money($__summary['walletBalance']) }} <span class="text-[11px] font-normal text-m-tx/55">تومان</span></div>
                </a>
                @if ($__summary['loyaltyOn'])
                    <a href="{{ route('loyalty.index') }}" class="rounded-xl bg-m-tx/[0.04] hover:bg-m-gold/10 px-2 py-2" data-account-points>
                        <div class="text-[11px] text-m-tx/55">امتیاز باشگاه</div>
                        <div class="text-sm font-bold text-m-tx">{{ fa_num($__summary['loyaltyPoints']) }}</div>
                    </a>
                @endif
            </div>
        </div>
        <nav class="p-2 flex flex-col gap-0.5">
            @foreach ($__accountTabs as $__tab)
                <a href="{{ $__tab['url'] }}" data-account-tab="{{ $__tab['key'] }}" @if ($__tab['active']) aria-current="page" @endif
                   class="flex items-center gap-2.5 rounded-xl px-3 py-2.5 text-sm transition-colors {{ $__tab['active'] ? 'bg-m-gold/15 text-m-tx font-bold' : 'text-m-tx/75 hover:text-m-tx hover:bg-m-tx/5' }}">
                    <svg class="w-5 h-5 shrink-0 {{ $__tab['active'] ? 'text-m-gold2' : 'text-m-tx/45' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $__tab['icon'] }}" /></svg>
                    <span class="flex-1">{{ $__tab['label'] }}</span>
                    @if ($__tab['badge'] > 0)<span class="text-[11px] font-bold rounded-full px-1.5 bg-m-gold/20 text-m-gold2" data-account-badge>{{ fa_num($__tab['badge']) }}</span>@endif
                </a>
            @endforeach
        </nav>
        <div class="p-3 border-t border-m-gold/10 space-y-2">
            <a href="{{ route('bookings.create') }}" class="btn-gold block text-center rounded-xl px-4 py-2.5 text-sm font-bold">رزرو نوبت تازه</a>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="w-full rounded-xl px-4 py-2 text-sm text-m-bad hover:bg-m-bad/10" data-account-logout>خروج</button>
            </form>
        </div>
    </div>
</aside>
