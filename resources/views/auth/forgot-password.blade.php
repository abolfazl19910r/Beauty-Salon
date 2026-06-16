<x-guest-layout>
    <div class="text-center mb-8">
        <div class="w-14 h-14 rounded-full bg-[#C9A24B]/15 flex items-center justify-center mx-auto mb-4">
            <svg class="w-7 h-7 text-[#E6CD8A]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
            </svg>
        </div>
        <h2 class="text-2xl font-bold" style="font-family:'Noto Naskh Arabic','Vazirmatn',serif; color:#E6CD8A">بازیابی رمز عبور</h2>
        <p class="text-sm text-[#F8F3E9]/60 mt-1">شماره موبایل خود را وارد کنید تا کد تایید ارسال شود</p>
    </div>

    <x-auth-session-status class="mb-5" :status="session('status')" />

    @if ($errors->any())
        <div class="mb-4 flex items-start gap-2 bg-red-900/30 border border-red-700/40 rounded-lg px-4 py-3 text-sm text-red-300">
            <svg class="w-4 h-4 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
            </svg>
            <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('password.send') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="phone" :value="__('شماره موبایل')" />
            <div class="relative">
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                    <svg class="h-4 w-4 text-[#C9A24B]/60" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/>
                    </svg>
                </div>
                <x-text-input id="phone" class="pr-10" type="text" name="phone"
                              :value="old('phone')" required autofocus dir="ltr" placeholder="09123456789" />
            </div>
            <x-input-error :messages="$errors->get('phone')" />
        </div>

        <button type="submit"
                class="w-full py-3 rounded-lg font-semibold text-sm transition-all duration-300
                   bg-gradient-to-l from-[#C9A24B] to-[#E6CD8A] text-[#1A1410]
                   hover:shadow-lg hover:shadow-[#C9A24B]/30 hover:-translate-y-0.5">
            ارسال کد تایید
        </button>

        <p class="text-center">
            <a href="{{ route('login') }}" class="text-sm text-[#E6CD8A]/70 hover:text-[#E6CD8A] transition-colors">
                ← بازگشت به صفحه ورود
            </a>
        </p>
    </form>

</x-guest-layout>
