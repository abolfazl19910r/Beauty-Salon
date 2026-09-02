<x-guest-layout>
    <div class="text-center mb-8">
        <div class="w-14 h-14 rounded-full bg-[#C9A24B]/15 flex items-center justify-center mx-auto mb-4">
            <svg class="w-7 h-7 text-[#E6CD8A]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
        </div>
        <h2 class="text-2xl font-bold" style="font-family:'Noto Naskh Arabic','Vazirmatn',serif; color:#E6CD8A">تایید ورود</h2>
        <p class="text-sm text-[#F8F3E9]/60 mt-1">کد تایید ارسال‌شده به موبایل را وارد کنید</p>
    </div>

    @if (session('success'))
        <div class="mb-5 flex items-center gap-2 bg-emerald-900/30 border border-emerald-700/40 rounded-lg px-4 py-3 text-sm text-emerald-300">
            <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-5 flex items-start gap-2 bg-red-900/30 border border-red-700/40 rounded-lg px-4 py-3 text-sm text-red-300">
            <svg class="w-4 h-4 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
            <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('salon.login.verify') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="code" value="کد تایید ۶ رقمی" />
            <x-text-input id="code"
                          class="text-center tracking-[0.5em] text-xl font-bold"
                          type="text" name="code" required autofocus
                          maxlength="6" placeholder="— — — — — —"
                          dir="ltr" inputmode="numeric" />
            <x-input-error :messages="$errors->get('code')" />

            <div class="mt-4 flex items-center justify-center gap-2 text-sm">
                <svg class="w-4 h-4 text-[#C9A24B]/70" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/><path stroke-linecap="round" d="M12 6v6l4 2"/>
                </svg>
                <span class="text-[#F8F3E9]/60">زمان باقی‌مانده:</span>
                <span id="countdown-timer" class="font-bold text-[#E6CD8A] tabular-nums">02:00</span>
            </div>
        </div>

        <button type="submit"
                class="w-full py-3 rounded-lg font-semibold text-sm transition-all duration-300
                   bg-gradient-to-l from-[#C9A24B] to-[#E6CD8A] text-[#1A1410]
                   hover:shadow-lg hover:shadow-[#C9A24B]/30 hover:-translate-y-0.5">
            تایید و ورود
        </button>
    </form>

    <div class="mt-5 flex items-center justify-between text-sm">
        <form method="POST" action="{{ route('salon.login.resend') }}" class="inline">
            @csrf
            <button id="resend-btn" type="submit" disabled
                    class="text-[#E6CD8A]/50 underline cursor-not-allowed transition-colors"
                    data-active-class="text-[#E6CD8A] cursor-pointer">
                ارسال مجدد کد
            </button>
        </form>
        <a href="{{ route('salon.login') }}" class="text-[#F8F3E9]/50 hover:text-[#F8F3E9] transition-colors">
            بازگشت به ورود
        </a>
    </div>

    <div class="mt-5 flex items-start gap-2 bg-[#C9A24B]/10 border border-[#C9A24B]/20 rounded-lg px-4 py-3 text-sm text-[#F8F3E9]/70">
        <svg class="w-4 h-4 shrink-0 mt-0.5 text-[#C9A24B]" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
        کد تایید را با هیچ‌کس به اشتراک نگذارید.
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('code').addEventListener('input', function(e) {
                e.target.value = e.target.value.replace(/\D/g, '');
            });

            let timeLeft = 120;
            const timerEl = document.getElementById('countdown-timer');
            const resendBtn = document.getElementById('resend-btn');

            const tick = setInterval(() => {
                timeLeft--;
                const m = String(Math.floor(timeLeft / 60)).padStart(2, '0');
                const s = String(timeLeft % 60).padStart(2, '0');
                timerEl.textContent = `${m}:${s}`;

                if (timeLeft <= 0) {
                    clearInterval(tick);
                    timerEl.textContent = '00:00';
                    timerEl.classList.replace('text-[#E6CD8A]', 'text-red-400');
                    resendBtn.disabled = false;
                    resendBtn.classList.remove('text-[#E6CD8A]/50', 'cursor-not-allowed');
                    resendBtn.classList.add('text-[#E6CD8A]', 'cursor-pointer');
                }
            }, 1000);
        });
    </script>

</x-guest-layout>
