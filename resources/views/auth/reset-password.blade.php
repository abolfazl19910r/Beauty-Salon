<x-guest-layout>
    <div class="text-center mb-8">
        <div class="w-14 h-14 rounded-full bg-[#C9A24B]/15 flex items-center justify-center mx-auto mb-4">
            <svg class="w-7 h-7 text-[#E6CD8A]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
            </svg>
        </div>
        <h2 class="text-2xl font-bold" style="font-family:'Noto Naskh Arabic','Vazirmatn',serif; color:#E6CD8A">تغییر رمز عبور</h2>
        <p class="text-sm text-[#F8F3E9]/60 mt-1">کد پیامک‌شده و رمز جدید را وارد کنید</p>
    </div>

    @if (session('success'))
        <div class="mb-5 flex items-center gap-2 bg-emerald-900/30 border border-emerald-700/40 rounded-lg px-4 py-3 text-sm text-emerald-300">
            <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div>
            <x-input-label for="code" :value="__('کد تایید ۶ رقمی')" />
            <x-text-input id="code" class="text-center tracking-[0.5em] text-lg font-bold" type="text"
                          name="code" required autofocus maxlength="6" placeholder="------"
                          dir="ltr" inputmode="numeric" />
            <x-input-error :messages="$errors->get('code')" />
        </div>

        <div>
            <x-input-label for="password" :value="__('رمز عبور جدید')" />
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
            <x-input-label for="password_confirmation" :value="__('تکرار رمز عبور جدید')" />
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
            تغییر رمز عبور
        </button>
    </form>

</x-guest-layout>
