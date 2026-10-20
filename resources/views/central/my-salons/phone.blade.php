<x-guest-layout>
    @include('central.my-salons._head', ['subtitle' => 'سالنی را که در آن نوبت گرفته‌اید پیدا کنید'])

    <form method="POST" action="{{ route('my-salons.send') }}" class="space-y-5" data-my-salons-phone>
        @csrf
        <div>
            <x-input-label for="phone" :value="__('شماره موبایل')" />
            <x-text-input id="phone" type="text" name="phone" inputmode="tel" :value="old('phone')" required autofocus
                          autocomplete="tel" dir="ltr" placeholder="09123456789" />
            <x-input-error :messages="$errors->get('phone')" />
        </div>
        <p class="text-xs text-m-tx/60 leading-6">اگر این شماره در سالنی حساب داشته باشد، کد تأیید پیامک می‌شود و بعد از وارد کردن کد، فهرست سالن‌هایتان را می‌بینید.</p>
        <button type="submit" class="w-full py-3 rounded-lg font-semibold text-sm transition-all duration-300 bg-gradient-to-l from-[#C9A24B] to-[#E6CD8A] text-m-ongold hover:shadow-lg hover:shadow-[#C9A24B]/30 hover:-translate-y-0.5">فرستادن کد</button>
        <p class="text-center text-sm text-m-tx/60">
            مدیر یا متخصص سالن هستید؟
            <a href="{{ route('login') }}" class="text-m-gold2 hover:text-m-gold font-medium mr-1">ورود به پنل</a>
        </p>
    </form>
</x-guest-layout>
