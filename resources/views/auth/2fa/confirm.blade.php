@extends('layouts.app')
@section('title', 'تایید هویت')

@section('content')
    <div class="max-w-md mx-auto fade-in">

        <div class="text-center mb-8">
            <div class="w-14 h-14 rounded-full bg-m-gold/15 flex items-center justify-center mx-auto mb-4">
                <svg class="w-7 h-7 text-m-gold2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-m-gold2" style="font-family:'Mahru','Vazirmatn',sans-serif;font-weight:800;">تایید هویت</h1>
            <p class="text-sm text-m-tx/55 mt-1">برای ادامه، احراز هویت دو مرحله‌ای را تکمیل کنید</p>
        </div>

        <div class="bg-m-card rounded-2xl border border-m-gold/10 p-6">
            <div id="confirm-step-1">
                <p class="text-sm text-m-tx/60 mb-4 text-center">
                    برای دریافت کد تایید، روی دکمه‌ی زیر بزنید.
                </p>
                <button onclick="requestCode()" id="request-code-btn"
                        class="w-full py-3 rounded-lg font-semibold text-sm transition-all duration-300
                           bg-gradient-to-l from-[#C9A24B] to-[#E6CD8A] text-m-ongold
                           hover:shadow-lg hover:shadow-[#C9A24B]/30">
                    <span id="request-code-text">ارسال کد تایید</span>
                </button>
            </div>

            <div id="confirm-step-2" class="hidden">
                <div class="flex justify-center gap-2" dir="ltr">
                    @for($i = 0; $i < 6; $i++)
                        <input type="text" maxlength="1" inputmode="numeric" class="otp-digit w-11 h-12 text-center rounded-lg text-xl font-bold bg-m-tx/5 border border-m-gold/25 text-m-tx focus:outline-none focus:border-m-gold focus:ring-2 focus:ring-m-gold/25 transition-colors">
                    @endfor
                </div>

                <button onclick="submitCode()" id="confirm-btn"
                        class="w-full mt-5 py-3 rounded-lg font-semibold text-sm transition-all duration-300
                           bg-gradient-to-l from-[#C9A24B] to-[#E6CD8A] text-m-ongold
                           hover:shadow-lg hover:shadow-[#C9A24B]/30 disabled:opacity-50 disabled:cursor-not-allowed">
                    <span id="confirm-btn-text">تایید</span>
                </button>
            </div>

            <div id="confirm-message" class="hidden mt-4 text-sm text-center rounded-xl p-3"></div>
        </div>
    </div>

    <script>
        const verifyUrl = '{{ route('security.2fa.verify') }}';
        const resendUrl = '{{ route('security.2fa.resend') }}';
        const csrfToken = '{{ csrf_token() }}';

        function showMessage(text, isError) {
            const el = document.getElementById('confirm-message');
            el.textContent = text;
            el.classList.remove('hidden', 'bg-m-bad/10', 'text-m-bad', 'border-m-bad/20', 'bg-m-ok/10', 'text-m-ok', 'border-m-ok/20');
            el.classList.add('border', isError ? 'bg-m-bad/10' : 'bg-m-ok/10', isError ? 'text-m-bad' : 'text-m-ok', isError ? 'border-m-bad/20' : 'border-m-ok/20');
        }

        function requestCode() {
            const btn = document.getElementById('request-code-btn');
            btn.disabled = true;

            fetch(resendUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            })
                .then(async (r) => ({ ok: r.ok, data: await r.json() }))
                .then(({ ok, data }) => {
                    if (ok) {
                        document.getElementById('confirm-step-1').classList.add('hidden');
                        document.getElementById('confirm-step-2').classList.remove('hidden');
                        showMessage(data.message || 'کد ارسال شد.', false);
                        const digits = document.querySelectorAll('.otp-digit');
                        if (digits.length) digits[0].focus();
                        return;
                    }
                    showMessage(data.error || 'خطا در ارسال کد.', true);
                    btn.disabled = false;
                })
                .catch(() => {
                    showMessage('خطا در ارسال کد.', true);
                    btn.disabled = false;
                });
        }

        document.addEventListener('input', function (e) {
            if (!e.target.classList.contains('otp-digit')) return;
            e.target.value = e.target.value.replace(/\D/g, '').slice(-1);
            const digits = [...document.querySelectorAll('.otp-digit')];
            const idx = digits.indexOf(e.target);
            if (e.target.value && idx < digits.length - 1) digits[idx + 1].focus();
        });

        function currentCode() {
            return [...document.querySelectorAll('.otp-digit')].map(d => d.value).join('');
        }

        function submitCode() {
            const code = currentCode();
            const btn = document.getElementById('confirm-btn');
            const btnText = document.getElementById('confirm-btn-text');

            if (code.length !== 6) {
                showMessage('لطفاً هر ۶ رقم کد را وارد کنید.', true);
                return;
            }

            btn.disabled = true;
            btnText.textContent = 'در حال بررسی...';

            fetch(verifyUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ code })
            })
                .then(async (r) => ({ ok: r.ok, data: await r.json() }))
                .then(({ ok, data }) => {
                    if (ok) {
                        showMessage('کد تایید شد.', false);
                        setTimeout(() => window.location.href = '{{ route('security.2fa') }}', 800);
                        return;
                    }
                    showMessage(data.error || 'کد وارد شده نامعتبر است.', true);
                    btn.disabled = false;
                    btnText.textContent = 'تایید';
                })
                .catch(() => {
                    showMessage('خطا در ارتباط با سرور.', true);
                    btn.disabled = false;
                    btnText.textContent = 'تایید';
                });
        }
    </script>
@endsection
