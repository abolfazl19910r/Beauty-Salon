@extends('layouts.app')
@section('title', 'فعال‌سازی احراز هویت دو مرحله‌ای')

@section('content')
    <div class="max-w-md mx-auto fade-in">

        <div class="flex items-center gap-3 mb-8">
            <a href="{{ route('security.2fa') }}"
               class="w-9 h-9 rounded-xl bg-m-card border border-m-gold/15 flex items-center justify-center
                  text-m-tx/60 hover:text-m-gold2 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
            <div>
                <p class="text-xs font-semibold text-m-gold tracking-[0.3em] uppercase mb-0.5">امنیت حساب</p>
                <h1 class="text-xl font-bold text-m-gold2" style="font-family:'Mahru','Vazirmatn',sans-serif;font-weight:800;">فعال‌سازی احراز هویت دو مرحله‌ای</h1>
            </div>
        </div>

        <div class="bg-m-card rounded-2xl border border-m-gold/10 p-6">
            <p class="text-sm text-m-tx/60 mb-5">
                کد ۶ رقمی به موبایل شما پیامک شد. برای فعال‌سازی، آن را وارد کنید.
            </p>

            <div class="flex justify-center gap-2" dir="ltr">
                @for($i = 0; $i < 6; $i++)
                    <input type="text" maxlength="1" inputmode="numeric" class="otp-digit w-11 h-12 text-center rounded-lg text-xl font-bold bg-m-tx/5 border border-m-gold/25 text-m-tx focus:outline-none focus:border-m-gold focus:ring-2 focus:ring-m-gold/25 transition-colors">
                @endfor
            </div>

            <div id="setup-message" class="hidden mt-4 text-sm text-center rounded-xl p-3"></div>

            <button id="enable-btn" onclick="submitEnable()"
                    class="w-full mt-5 py-3 rounded-lg font-semibold text-sm transition-all duration-300
                       bg-gradient-to-l from-[#C9A24B] to-[#E6CD8A] text-m-ongold
                       hover:shadow-lg hover:shadow-[#C9A24B]/30 disabled:opacity-50 disabled:cursor-not-allowed">
                <span id="enable-btn-text">تایید و فعال‌سازی</span>
            </button>

            <div class="text-center mt-3">
                <button onclick="resendCode()" id="resend-btn" class="text-sm text-m-gold2/70 hover:text-m-gold2 transition-colors">
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
            el.classList.remove('hidden', 'bg-m-bad/10', 'text-m-bad', 'border-m-bad/20', 'bg-m-ok/10', 'text-m-ok', 'border-m-ok/20');
            el.classList.add('border', isError ? 'bg-m-bad/10' : 'bg-m-ok/10', isError ? 'text-m-bad' : 'text-m-ok', isError ? 'border-m-bad/20' : 'border-m-ok/20');
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
