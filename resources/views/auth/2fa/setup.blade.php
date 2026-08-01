@extends('layouts.app')
@section('title', 'فعال‌سازی احراز هویت دو مرحله‌ای')

@section('content')
    <div class="max-w-md mx-auto fade-in">

        <div class="flex items-center gap-3 mb-8">
            <a href="{{ route('security.2fa') }}"
               class="w-9 h-9 rounded-xl bg-[#2E2117] border border-[#C9A24B]/15 flex items-center justify-center
                  text-[#F8F3E9]/60 hover:text-[#E6CD8A] transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
            <div>
                <p class="text-xs font-semibold text-[#C9A24B] tracking-[0.3em] uppercase mb-0.5">امنیت حساب</p>
                <h1 class="text-xl font-bold text-[#E6CD8A]" style="font-family:'Noto Naskh Arabic','Vazirmatn',serif">فعال‌سازی احراز هویت دو مرحله‌ای</h1>
            </div>
        </div>

        <div class="bg-[#2E2117] rounded-2xl border border-[#C9A24B]/10 p-6">
            <p class="text-sm text-[#F8F3E9]/60 mb-5">
                کد ۶ رقمی به موبایل شما پیامک شد. برای فعال‌سازی، آن را وارد کنید.
            </p>

            <div class="flex justify-center gap-2" dir="ltr">
                @for($i = 0; $i < 6; $i++)
                    <input type="text" maxlength="1" inputmode="numeric" class="otp-digit w-11 h-12 text-center rounded-lg text-xl font-bold bg-white/5 border border-[#C9A24B]/25 text-[#F8F3E9] focus:outline-none focus:border-[#C9A24B] focus:ring-2 focus:ring-[#C9A24B]/25 transition-colors">
                @endfor
            </div>

            <div id="setup-message" class="hidden mt-4 text-sm text-center rounded-xl p-3"></div>

            <button id="enable-btn" onclick="submitEnable()"
                    class="w-full mt-5 py-3 rounded-lg font-semibold text-sm transition-all duration-300
                       bg-gradient-to-l from-[#C9A24B] to-[#E6CD8A] text-[#1A1410]
                       hover:shadow-lg hover:shadow-[#C9A24B]/30 disabled:opacity-50 disabled:cursor-not-allowed">
                <span id="enable-btn-text">تایید و فعال‌سازی</span>
            </button>

            <div class="text-center mt-3">
                <button onclick="resendCode()" id="resend-btn" class="text-sm text-[#E6CD8A]/70 hover:text-[#E6CD8A] transition-colors">
                    ارسال مجدد کد
                </button>
            </div>
        </div>
    </div>

    <script>
        const enableUrl = '{{ route('security.2fa.enable') }}';
        const resendUrl = '{{ route('security.2fa.resend') }}';
        const csrfToken = '{{ csrf_token() }}';

        const digits = document.querySelectorAll('.otp-digit');
        digits.forEach((input, idx) => {
            input.addEventListener('input', function () {
                this.value = this.value.replace(/\D/g, '').slice(-1);
                if (this.value && idx < digits.length - 1) digits[idx + 1].focus();
            });
            input.addEventListener('keydown', function (e) {
                if (e.key === 'Backspace' && !this.value && idx > 0) digits[idx - 1].focus();
                if (e.key === 'Enter') submitEnable();
            });
        });
        if (digits.length) digits[0].focus();

        function currentCode() {
            return [...digits].map(d => d.value).join('');
        }

        function showMessage(text, isError) {
            const el = document.getElementById('setup-message');
            el.textContent = text;
            el.classList.remove('hidden', 'bg-red-500/10', 'text-red-400', 'border-red-500/20', 'bg-emerald-500/10', 'text-emerald-400', 'border-emerald-500/20');
            el.classList.add('border', isError ? 'bg-red-500/10' : 'bg-emerald-500/10', isError ? 'text-red-400' : 'text-emerald-400', isError ? 'border-red-500/20' : 'border-emerald-500/20');
        }

        function submitEnable() {
            const code = currentCode();
            const btn = document.getElementById('enable-btn');
            const btnText = document.getElementById('enable-btn-text');

            if (code.length !== 6) {
                showMessage('لطفاً هر ۶ رقم کد را وارد کنید.', true);
                return;
            }

            btn.disabled = true;
            btnText.textContent = 'در حال بررسی...';

            fetch(enableUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ code })
            })
                .then(async (r) => ({ ok: r.ok, data: await r.json() }))
                .then(({ ok, data }) => {
                    if (ok) {
                        showMessage('احراز هویت دو مرحله‌ای با موفقیت فعال شد. در حال انتقال...', false);
                        setTimeout(() => window.location.href = '{{ route('security.2fa') }}', 1000);
                        return;
                    }
                    showMessage(data.error || 'کد وارد شده نامعتبر است.', true);
                    btn.disabled = false;
                    btnText.textContent = 'تایید و فعال‌سازی';
                    digits.forEach(d => d.value = '');
                    digits[0].focus();
                })
                .catch(() => {
                    showMessage('خطا در ارتباط با سرور.', true);
                    btn.disabled = false;
                    btnText.textContent = 'تایید و فعال‌سازی';
                });
        }

        function resendCode() {
            fetch(resendUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            })
                .then(async (r) => ({ ok: r.ok, data: await r.json() }))
                .then(({ ok, data }) => showMessage(ok ? (data.message || 'کد جدید ارسال شد.') : (data.error || 'خطا در ارسال مجدد کد.'), !ok))
                .catch(() => showMessage('خطا در ارسال مجدد کد.', true));
        }
    </script>
@endsection
