<x-guest-layout>
    @include('central.my-salons._head', ['subtitle' => 'کد پیامک‌شده به '.$phone.' را وارد کنید'])

    @if (session('status'))
        <div class="mb-4 text-sm text-m-ok">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('my-salons.verify.store') }}" class="space-y-5" data-my-salons-verify>
        @csrf
        <div>
            <x-input-label for="code" :value="__('کد تأیید')" />
            <x-text-input id="code" type="text" name="code" inputmode="numeric" maxlength="6" required autofocus
                          autocomplete="one-time-code" dir="ltr" placeholder="------" />
            <x-input-error :messages="$errors->get('code')" />
        </div>
        <button type="submit" class="w-full py-3 rounded-lg font-semibold text-sm transition-all duration-300 bg-gradient-to-l from-[#C9A24B] to-[#E6CD8A] text-m-ongold hover:shadow-lg hover:shadow-[#C9A24B]/30 hover:-translate-y-0.5">دیدن سالن‌ها</button>
    </form>

    <div class="flex items-center justify-between text-sm mt-5">
        <form method="POST" action="{{ route('my-salons.resend') }}">
            @csrf
            <button type="submit" class="text-m-gold2 hover:text-m-gold">ارسال دوباره‌ی کد</button>
        </form>
        <a href="{{ route('my-salons.show') }}" class="text-m-tx/60 hover:text-m-gold">شماره‌ی دیگر</a>
    </div>
</x-guest-layout>
