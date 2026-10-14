<x-guest-layout>
    <div class="text-center mb-8">
        <div class="w-14 h-14 rounded-full bg-m-gold/15 flex items-center justify-center mx-auto mb-4">
            <svg class="w-7 h-7 text-m-gold2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
        </div>
        <h2 class="text-2xl font-bold" style="font-family:'Mahru','Vazirmatn',sans-serif;font-weight:800;color:rgb(var(--m-gold2))">تایید رمز عبور</h2>
        <p class="text-sm text-m-tx/60 mt-1">این بخش امن است، لطفاً رمز عبور خود را تایید کنید</p>
    </div>

    <div class="mb-6 flex items-start gap-2 bg-m-info/30 border border-m-info/40 rounded-lg px-4 py-3 text-sm text-m-info">
        <svg class="w-4 h-4 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
        این یک بخش امن از برنامه است. لطفاً قبل از ادامه رمز عبور خود را تایید کنید.
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="password" :value="__('رمز عبور')" />
            <div class="relative">
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                    <svg class="h-4 w-4 text-m-gold/60" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <x-text-input id="password" class="pr-10" type="password" name="password"
                              required autocomplete="current-password" />
            </div>
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <button type="submit"
                class="w-full py-3 rounded-lg font-semibold text-sm transition-all duration-300
                   bg-gradient-to-l from-[#C9A24B] to-[#E6CD8A] text-m-ongold
                   hover:shadow-lg hover:shadow-[#C9A24B]/30 hover:-translate-y-0.5">
            تایید
        </button>
    </form>

</x-guest-layout>
