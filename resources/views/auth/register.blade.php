<x-guest-layout>
    <div class="text-center mb-8">
        <div class="w-14 h-14 rounded-full bg-[#C9A24B]/15 flex items-center justify-center mx-auto mb-4">
            <svg class="w-7 h-7 text-[#E6CD8A]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="8.5" cy="7" r="4"/>
                <line x1="20" y1="8" x2="20" y2="14"/>
                <line x1="23" y1="11" x2="17" y2="11"/>
            </svg>
        </div>
        <h2 class="text-2xl font-bold" style="font-family:'Noto Naskh Arabic','Vazirmatn',serif; color:#E6CD8A">ثبت نام در راستا</h2>
        <p class="text-sm text-[#F8F3E9]/60 mt-1">برای استفاده از خدمات ما ثبت نام کنید</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="name" :value="__('نام و نام خانوادگی')" />
            <div class="relative">
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                    <svg class="h-4 w-4 text-[#C9A24B]/60" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <x-text-input id="name" class="pr-10" type="text" name="name"
                              :value="old('name')" required autofocus autocomplete="name" />
            </div>
            <x-input-error :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="phone" :value="__('شماره موبایل')" />
            <div class="relative">
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                    <svg class="h-4 w-4 text-[#C9A24B]/60" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/>
                    </svg>
                </div>
                <x-text-input id="phone" class="pr-10" type="text" name="phone"
                              :value="old('phone')" required autocomplete="phone"
                              dir="ltr" placeholder="09123456789" />
            </div>
            <x-input-error :messages="$errors->get('phone')" />
        </div>

        <div>
            <x-input-label for="password" :value="__('رمز عبور')" />
            <div class="relative">
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                    <svg class="h-4 w-4 text-[#C9A24B]/60" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <x-text-input id="password" class="pr-10" type="password" name="password"
                              required autocomplete="new-password" />
            </div>
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div>
            <x-input-label for="password_confirmation" :value="__('تکرار رمز عبور')" />
            <div class="relative">
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                    <svg class="h-4 w-4 text-[#C9A24B]/60" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <x-text-input id="password_confirmation" class="pr-10" type="password"
                              name="password_confirmation" required autocomplete="new-password" />
            </div>
            <x-input-error :messages="$errors->get('password_confirmation')" />
        </div>

        <button type="submit"
                class="w-full py-3 rounded-lg font-semibold text-sm transition-all duration-300
                   bg-gradient-to-l from-[#C9A24B] to-[#E6CD8A] text-[#1A1410]
                   hover:shadow-lg hover:shadow-[#C9A24B]/30 hover:-translate-y-0.5">
            ثبت نام
        </button>

        <p class="text-center text-sm text-[#F8F3E9]/60">
            قبلاً ثبت‌نام کرده‌اید؟
            <a href="{{ route('login') }}" class="text-[#E6CD8A] hover:text-[#C9A24B] transition-colors font-medium mr-1">
                ورود به حساب
            </a>
        </p>
    </form>
</x-guest-layout>
