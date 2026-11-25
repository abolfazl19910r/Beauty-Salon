{{--
    منوی پایین ثابت موبایل سایت سالن (بسته‌ی ۴ ظاهری برای مشتری؛ بسته‌ی ۵ج برای مهمان) — دکمه‌ی گرد وسط «رزرو»، نشان شمار.
    ناحیه‌ی امن آیفون: .m-bottom-nav (partials/pwa-shell). ورودی: $items (CustomerMenu::bottom / guestBottom)، $attr (نشانه‌ی data-*).
--}}
<nav class="md:hidden fixed bottom-0 inset-x-0 z-40 border-t border-m-gold/15 bg-m-bg2/95 backdrop-blur grid grid-cols-5 m-bottom-nav" aria-label="منوی پایین" {{ $attr }}>
    @foreach ($items as $__item)
        @if ($__item['key'] === 'book')
            <a href="{{ $__item['url'] }}" class="flex flex-col items-center justify-center gap-0.5 py-1.5 text-[11px] font-bold text-m-gold2" @if ($__item['active']) aria-current="page" @endif>
                <span class="w-11 h-11 -mt-5 rounded-full btn-gold flex items-center justify-center shadow-lg" aria-hidden="true">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" d="{{ $__item['icon'] }}" /></svg>
                </span>
                {{ $__item['label'] }}
            </a>
        @else
            <a href="{{ $__item['url'] }}" @if ($__item['active']) aria-current="page" @endif
               class="relative flex flex-col items-center justify-center gap-1 py-2.5 text-[11px] {{ $__item['active'] ? 'text-m-gold2 font-bold' : 'text-m-tx/70' }}">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $__item['icon'] }}" /></svg>
                {{ $__item['label'] }}
                @if ($__item['badge'] > 0)
                    <span class="absolute text-[10px] font-bold rounded-full px-1.5 bg-m-gold text-m-ongold" style="top:4px; right:calc(50% - 24px);" data-account-badge>{{ fa_num($__item['badge']) }}</span>
                @endif
            </a>
        @endif
    @endforeach
</nav>
