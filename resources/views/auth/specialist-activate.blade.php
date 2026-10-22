{{-- ⭐ فعال‌سازی حساب متخصص (۲۰۲۶-۱۰-۲۲): فقط شماره‌ای که مدیر سالن در «متخصصین» ثبت کرده و هنوز حساب ندارد --}}
<x-guest-layout>
    <div class="text-center mb-8">
        <div class="flex items-center justify-center mx-auto mb-4">
            <x-brand-emblem class="h-16 w-auto max-w-[180px]" />
        </div>
        <h2 class="text-2xl font-bold" style="font-family:'Mahru','Vazirmatn',sans-serif;font-weight:800;color:rgb(var(--m-gold2))">فعال‌سازی حساب متخصص</h2>
        <p class="text-sm text-m-tx/60 mt-1">با همان شماره‌ای که مدیر سالن برای شما ثبت کرده؛ یک رمز برای ورودهای بعد انتخاب کنید</p>
    </div>

    <form method="POST" action="{{ route('specialist.activate') }}" class="space-y-5" data-specialist-activate>
        @csrf

        <div>
            <x-input-label for="phone" value="شماره موبایل" />
            <x-text-input id="phone" type="text" name="phone" :value="old('phone')" required autofocus autocomplete="tel"
                          dir="ltr" inputmode="numeric" placeholder="09123456789" />
            <x-input-error :messages="$errors->get('phone')" />
        </div>

        <div>
            <x-input-label for="password" value="رمز عبور (حداقل ۸ حرف)" />
            <x-text-input id="password" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div>
            <x-input-label for="password_confirmation" value="تکرار رمز عبور" />
            <x-text-input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" />
        </div>

        <button type="submit"
                class="w-full py-3 rounded-lg font-semibold text-sm transition-all duration-300
                   bg-gradient-to-l from-[#C9A24B] to-[#E6CD8A] text-m-ongold
                   hover:shadow-lg hover:shadow-[#C9A24B]/30 hover:-translate-y-0.5">
            ارسال کد تایید
        </button>

        <p class="text-center text-sm text-m-tx/60">
            حساب دارید؟
            <a href="{{ route('login') }}" class="text-m-gold2 hover:text-m-gold transition-colors font-medium mr-1">ورود</a>
        </p>
    </form>
</x-guest-layout>
