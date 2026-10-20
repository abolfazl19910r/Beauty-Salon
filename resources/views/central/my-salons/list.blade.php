<x-guest-layout>
    @include('central.my-salons._head', ['subtitle' => 'سالن‌هایی که شماره‌ی '.$phone.' در آن‌ها حساب دارد'])

    @if ($salons->isEmpty())
        <p class="text-center text-sm text-m-tx/70 leading-7" data-my-salons-empty>
            این شماره در هیچ سالن فعالی حساب ندارد. اگر سالنتان را می‌شناسید، از نشانی سایت همان سالن ثبت‌نام کنید.
        </p>
    @else
        <ul class="space-y-3" data-my-salons-list>
            @foreach ($salons as $salon)
                <li>
                    <a href="{{ route('salon.login', ['salon_slug' => $salon->slug, 'phone' => $phone]) }}"
                       class="flex items-center gap-3 rounded-xl p-3 border border-m-gold/20 hover:border-m-gold/60 transition-colors">
                        @if ($salon->logoUrl())
                            <img src="{{ $salon->logoUrl() }}" alt="" class="w-11 h-11 rounded-lg object-contain shrink-0">
                        @else
                            <span class="w-11 h-11 rounded-lg shrink-0 flex items-center justify-center font-bold text-m-gold bg-m-gold/10">{{ mb_substr($salon->name, 0, 1) }}</span>
                        @endif
                        <span class="flex-1 min-w-0">
                            <span class="block font-bold text-m-tx truncate">{{ \App\Support\SalonTitle::for($salon->name) }}</span>
                            <span class="block text-xs text-m-tx/50 truncate" dir="ltr">{{ \App\Support\SalonAddress::short($salon) }}</span>
                        </span>
                        <span class="text-sm text-m-gold2 shrink-0">ورود ←</span>
                    </a>
                </li>
            @endforeach
        </ul>
        <p class="text-xs text-m-tx/50 mt-4 leading-6">ورود هر سالن جداست؛ شماره پر شده و فقط رمز یا کد همان سالن را وارد می‌کنید.</p>
    @endif

    <form method="POST" action="{{ route('my-salons.forget') }}" class="text-center mt-5">
        @csrf
        <button type="submit" class="text-sm text-m-tx/60 hover:text-m-gold">شماره‌ی دیگر</button>
    </form>
</x-guest-layout>
