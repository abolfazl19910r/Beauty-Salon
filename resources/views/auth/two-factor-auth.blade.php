<x-guest-layout>
    <div class="text-center mb-8">
        <div class="w-14 h-14 rounded-full bg-[#C9A24B]/15 flex items-center justify-center mx-auto mb-4">
            <svg class="w-7 h-7 text-[#E6CD8A]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
        </div>
        <h2 class="text-2xl font-bold" style="font-family:'Noto Naskh Arabic','Vazirmatn',serif; color:#E6CD8A">تایید دو مرحله‌ای</h2>
        <p class="text-sm text-[#F8F3E9]/60 mt-1">کد تایید ۶ رقمی ارسال‌شده به موبایل را وارد کنید</p>
    </div>

    <div id="two-factor-auth" class="space-y-6">
        {{-- Single digit inputs --}}
        <div class="flex justify-center gap-2 rtl:gap-2" dir="ltr">
            @for($i = 0; $i < 6; $i++)
                <input type="text" maxlength="1" inputmode="numeric"
                       class="otp-digit w-11 h-12 text-center rounded-lg text-xl font-bold
                           bg-white/5 border border-[#C9A24B]/25 text-[#F8F3E9]
                           focus:outline-none focus:border-[#C9A24B] focus:ring-2 focus:ring-[#C9A24B]/25
                           transition-colors" />
            @endfor
        </div>

        <input type="hidden" id="combined-code" name="code">

        <div class="flex items-center justify-center gap-2 text-sm">
            <svg class="w-4 h-4 text-[#C9A24B]/70" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/><path stroke-linecap="round" d="M12 6v6l4 2"/>
            </svg>
            <span class="text-[#F8F3E9]/60">زمان باقی‌مانده:</span>
            <span id="countdown-timer" class="font-bold text-[#E6CD8A] tabular-nums">02:00</span>
        </div>

        <button id="confirm-btn"
                class="w-full py-3 rounded-lg font-semibold text-sm transition-all duration-300
                   bg-gradient-to-l from-[#C9A24B] to-[#E6CD8A] text-[#1A1410]
                   hover:shadow-lg hover:shadow-[#C9A24B]/30 hover:-translate-y-0.5">
            تایید
        </button>

        <div class="flex items-center justify-center">
            <button id="resend-btn" disabled
                    class="text-sm text-[#E6CD8A]/50 underline cursor-not-allowed transition-colors flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                ارسال مجدد کد
            </button>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const digits = document.querySelectorAll('.otp-digit');
            const combined = document.getElementById('combined-code');
            const confirmBtn = document.getElementById('confirm-btn');
            const resendBtn = document.getElementById('resend-btn');
            const timerEl = document.getElementById('countdown-timer');

            // Navigation between OTP boxes
            digits.forEach((input, idx) => {
                input.addEventListener('input', function() {
                    this.value = this.value.replace(/\D/g, '').slice(-1);
                    combined.value = [...digits].map(d => d.value).join('');
                    if (this.value && idx < digits.length - 1) {
                        digits[idx + 1].focus();
                    }
                });
                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Backspace' && !this.value && idx > 0) {
                        digits[idx - 1].focus();
                    }
                });
            });

            // Timer
            let timeLeft = 120;
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

            confirmBtn.addEventListener('click', function() {
                combined.value = [...digits].map(d => d.value).join('');

            });
        });
    </script>
</x-guest-layout>
