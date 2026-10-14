<x-guest-layout>
    <div class="text-center mb-8">
        <div class="w-14 h-14 rounded-full bg-m-gold/15 flex items-center justify-center mx-auto mb-4">
            <svg class="w-7 h-7 text-m-gold2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <h2 class="text-2xl font-bold" style="font-family:'Mahru','Vazirmatn',sans-serif;font-weight:800;color:rgb(var(--m-gold2))">تایید شماره موبایل</h2>
        <p class="text-sm text-m-tx/60 mt-1">کد تایید به شماره موبایل شما ارسال شد</p>
        @if(isset($user))
            <p class="text-m-gold2 font-medium mt-2 tracking-widest persian-number" dir="ltr">{{ $user->phone }}</p>
        @endif
    </div>

    @if (session('success'))
        <div class="mb-5 flex items-center gap-2 bg-m-ok/30 border border-m-ok/40 rounded-lg px-4 py-3 text-sm text-m-ok">
            <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-5 flex items-start gap-2 bg-m-bad/30 border border-m-bad/40 rounded-lg px-4 py-3 text-sm text-m-bad">
            <svg class="w-4 h-4 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
            <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('salon.register.verify') }}" class="space-y-5">
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
                <svg class="w-4 h-4 text-m-gold/70" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/><path stroke-linecap="round" d="M12 6v6l4 2"/>
                </svg>
                <span class="text-m-tx/60">زمان باقی‌مانده:</span>
                <span id="countdown-timer" class="font-bold text-m-gold2 tabular-nums">02:00</span>
            </div>
        </div>

        <button type="submit"
                class="w-full py-3 rounded-lg font-semibold text-sm transition-all duration-300
                   bg-gradient-to-l from-[#C9A24B] to-[#E6CD8A] text-m-ongold
                   hover:shadow-lg hover:shadow-[#C9A24B]/30 hover:-translate-y-0.5">
            تایید و ورود به حساب
        </button>
    </form>

    <div class="mt-5 flex items-center justify-between text-sm">
        <form method="POST" action="{{ route('salon.register.resend') }}" class="inline">
            @csrf
            <button id="resend-btn" type="submit" disabled
                    class="text-m-gold2/50 underline cursor-not-allowed transition-colors">
                ارسال مجدد کد
            </button>
        </form>
        <a href="{{ route('salon.register') }}" class="text-m-tx/50 hover:text-m-tx transition-colors">
            بازگشت به ثبت نام
        </a>
    </div>

    <div class="mt-5 flex items-start gap-2 bg-m-gold/10 border border-m-gold/20 rounded-lg px-4 py-3 text-sm text-m-tx/70">
        <svg class="w-4 h-4 shrink-0 mt-0.5 text-m-gold" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm-1-4a1 1 0 112 0 1 1 0 01-2 0zm1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
        خوش آمدید! با تایید شماره موبایل می‌توانید از تمام خدمات {{ $salon->name }} استفاده کنید.
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
                    timerEl.classList.replace('text-m-gold2', 'text-m-bad');
                    resendBtn.disabled = false;
                    resendBtn.classList.remove('text-m-gold2/50', 'cursor-not-allowed');
                    resendBtn.classList.add('text-m-gold2', 'cursor-pointer');
                }
            }, 1000);
        });
    </script>

</x-guest-layout>
