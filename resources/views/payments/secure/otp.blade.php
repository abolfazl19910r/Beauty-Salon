@extends('layouts.app')
@section('title', 'تایید دو مرحله‌ای')

@section('content')
    <div class="max-w-md mx-auto fade-in">

        <div class="text-center mb-8">
            <div class="w-14 h-14 rounded-full bg-[#C9A24B]/15 flex items-center justify-center mx-auto mb-4">
                <svg class="w-7 h-7 text-[#E6CD8A]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-[#E6CD8A]" style="font-family:'Noto Naskh Arabic','Vazirmatn',serif">تایید دو مرحله‌ای</h1>
            <p class="text-sm text-[#F8F3E9]/55 mt-1">برای ادامه‌ی پرداخت امن، کد ۶ رقمی ارسال‌شده به موبایل خود را وارد کنید</p>
        </div>

        <div class="bg-[#2E2117] rounded-2xl border border-[#C9A24B]/10 p-6">
            <div class="flex justify-center gap-2" dir="ltr">
                @for($i = 0; $i < 6; $i++)
                    <input type="text" maxlength="1" inputmode="numeric" class="otp-digit w-11 h-12 text-center rounded-lg text-xl font-bold bg-white/5 border border-[#C9A24B]/25 text-[#F8F3E9] focus:outline-none focus:border-[#C9A24B] focus:ring-2 focus:ring-[#C9A24B]/25 transition-colors">
                @endfor
            </div>

            <div id="otp-message" class="hidden mt-4 text-sm text-center rounded-xl p-3"></div>

            <div class="flex items-center justify-center gap-2 text-sm mt-4">
                <svg class="w-4 h-4 text-[#C9A24B]/70" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/><path stroke-linecap="round" d="M12 6v6l4 2"/>
                </svg>
                <span class="text-[#F8F3E9]/55">زمان باقی‌مانده:</span>
                <span id="countdown-timer" class="font-bold text-[#E6CD8A] tabular-nums">02:00</span>
            </div>

            <button id="confirm-btn" onclick="submitOtp()"
                    class="w-full mt-5 py-3 rounded-lg font-semibold text-sm transition-all duration-300
                       bg-gradient-to-l from-[#C9A24B] to-[#E6CD8A] text-[#1A1410]
                       hover:shadow-lg hover:shadow-[#C9A24B]/30 disabled:opacity-50 disabled:cursor-not-allowed">
                <span id="confirm-btn-text">تایید</span>
            </button>

            <div class="flex items-center justify-center mt-3">
                <button id="resend-btn" onclick="resendOtp()" disabled
                        class="text-sm text-[#E6CD8A]/40 cursor-not-allowed transition-colors flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    ارسال مجدد کد
                </button>
            </div>
        </div>

        <div class="mt-4 text-center">
            <a href="{{ route('bookings.index') }}" class="text-sm text-[#F8F3E9]/50 hover:text-[#E6CD8A] transition-colors">
                انصراف
            </a>
        </div>
    </div>

    <script>
        const intendedUrl = @json(session('secure_payment_intended_url', route('bookings.index')));
        const verifyUrl = '{{ route('security.2fa.verify') }}';
        const resendUrl = '{{ route('security.2fa.resend') }}';
        const csrfToken = '{{ csrf_token() }}';

        const digits = document.querySelectorAll('.otp-digit');
        const messageEl = document.getElementById('otp-message');
        const confirmBtn = document.getElementById('confirm-btn');
        const confirmBtnText = document.getElementById('confirm-btn-text');
        const resendBtn = document.getElementById('resend-btn');
        const timerEl = document.getElementById('countdown-timer');

        digits.forEach((input, idx) => {
            input.addEventListener('input', function () {
                this.value = this.value.replace(/\D/g, '').slice(-1);
                if (this.value && idx < digits.length - 1) digits[idx + 1].focus();
            });
            input.addEventListener('keydown', function (e) {
                if (e.key === 'Backspace' && !this.value && idx > 0) digits[idx - 1].focus();
                if (e.key === 'Enter') submitOtp();
            });
        });

        if (digits.length) digits[0].focus();

        function currentCode() {
            return [...digits].map(d => d.value).join('');
        }

        function showMessage(text, isError) {
            messageEl.textContent = text;
            messageEl.classList.remove('hidden', 'bg-red-500/10', 'text-red-400', 'border-red-500/20', 'bg-emerald-500/10', 'text-emerald-400', 'border-emerald-500/20');
            if (isError) {
                messageEl.classList.add('bg-red-500/10', 'text-red-400', 'border', 'border-red-500/20');
            } else {
                messageEl.classList.add('bg-emerald-500/10', 'text-emerald-400', 'border', 'border-emerald-500/20');
            }
        }

        function submitOtp() {
            const code = currentCode();
            if (code.length !== 6) {
                showMessage('لطفاً هر ۶ رقم کد را وارد کنید.', true);
                return;
            }

            confirmBtn.disabled = true;
            confirmBtnText.textContent = 'در حال بررسی...';

            fetch(verifyUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ code })
            })
                .then(async (r) => ({ ok: r.ok, data: await r.json() }))
                .then(({ ok, data }) => {
                    if (ok) {
                        showMessage('کد تایید شد. در حال انتقال...', false);
                        window.location.href = intendedUrl;
                        return;
                    }
                    showMessage(data.error || 'کد وارد شده نامعتبر است.', true);
                    confirmBtn.disabled = false;
                    confirmBtnText.textContent = 'تایید';
                    digits.forEach(d => d.value = '');
                    digits[0].focus();
                })
                .catch(() => {
                    showMessage('خطا در ارتباط با سرور.', true);
                    confirmBtn.disabled = false;
                    confirmBtnText.textContent = 'تایید';
                });
        }

        function resendOtp() {
            resendBtn.disabled = true;

            fetch(resendUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            })
                .then(async (r) => ({ ok: r.ok, data: await r.json() }))
                .then(({ ok, data }) => {
                    if (ok) {
                        showMessage(data.message || 'کد جدید ارسال شد.', false);
                        startCountdown();
                        return;
                    }
                    showMessage(data.error || 'خطا در ارسال مجدد کد.', true);
                    resendBtn.disabled = false;
                })
                .catch(() => {
                    showMessage('خطا در ارسال مجدد کد.', true);
                    resendBtn.disabled = false;
                });
        }

        let countdownInterval;
        function startCountdown() {
            clearInterval(countdownInterval);
            let timeLeft = 120;
            resendBtn.disabled = true;
            resendBtn.classList.remove('text-[#E6CD8A]', 'cursor-pointer');
            resendBtn.classList.add('text-[#E6CD8A]/40', 'cursor-not-allowed');
            timerEl.classList.remove('text-red-400');
            timerEl.classList.add('text-[#E6CD8A]');

            countdownInterval = setInterval(() => {
                timeLeft--;
                const m = String(Math.floor(timeLeft / 60)).padStart(2, '0');
                const s = String(timeLeft % 60).padStart(2, '0');
                timerEl.textContent = `${m}:${s}`;
                if (timeLeft <= 0) {
                    clearInterval(countdownInterval);
                    timerEl.textContent = '00:00';
                    timerEl.classList.replace('text-[#E6CD8A]', 'text-red-400');
                    resendBtn.disabled = false;
                    resendBtn.classList.remove('text-[#E6CD8A]/40', 'cursor-not-allowed');
                    resendBtn.classList.add('text-[#E6CD8A]', 'cursor-pointer');
                }
            }, 1000);
        }

        startCountdown();
    </script>
@endsection
