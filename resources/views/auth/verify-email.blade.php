<x-guest-layout>
    <div class="text-center mb-8">
        <div class="w-14 h-14 rounded-full bg-[#C9A24B]/15 flex items-center justify-center mx-auto mb-4">
            <svg class="w-7 h-7 text-[#E6CD8A]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
        </div>
        <h2 class="text-2xl font-bold" style="font-family:'Noto Naskh Arabic','Vazirmatn',serif; color:#E6CD8A">تایید آدرس ایمیل</h2>
        <p class="text-sm text-[#F8F3E9]/60 mt-1">لطفاً ایمیل خود را تایید کنید</p>
    </div>

    <div class="mb-6 flex items-start gap-2 bg-sky-900/30 border border-sky-700/40 rounded-lg px-4 py-3 text-sm text-sky-300">
        <svg class="w-4 h-4 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
        ممنون از ثبت‌نام شما! قبل از شروع، لطفاً ایمیل خود را با کلیک روی لینکی که برای شما ارسال کردیم تایید کنید.
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-6 flex items-center gap-2 bg-emerald-900/30 border border-emerald-700/40 rounded-lg px-4 py-3 text-sm text-emerald-300">
            <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            یک لینک تایید جدید به آدرس ایمیل شما ارسال شد.
        </div>
    @endif

    <div class="space-y-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit"
                    class="w-full py-3 rounded-lg font-semibold text-sm transition-all duration-300
                       bg-gradient-to-l from-[#C9A24B] to-[#E6CD8A] text-[#1A1410]
                       hover:shadow-lg hover:shadow-[#C9A24B]/30 hover:-translate-y-0.5">
                ارسال مجدد ایمیل تاییدیه
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="text-center">
            @csrf
            <button type="submit" class="text-sm text-[#F8F3E9]/50 hover:text-[#F8F3E9] transition-colors underline">
                خروج از حساب کاربری
            </button>
        </form>
    </div>
</x-guest-layout>
