@extends('layouts.app')
@section('title', 'احراز هویت دو مرحله‌ای')

@section('content')
    <div class="max-w-2xl mx-auto fade-in">

        {{-- Header --}}
        <div class="flex items-center gap-3 mb-8">
            <a href="{{ route('profile.edit') }}"
               class="w-9 h-9 rounded-xl bg-[#2E2117] border border-[#C9A24B]/15 flex items-center justify-center
                  text-[#F8F3E9]/60 hover:text-[#E6CD8A] transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
            <div>
                <p class="text-xs font-semibold text-[#C9A24B] tracking-[0.3em] uppercase mb-0.5">امنیت حساب</p>
                <h1 class="text-2xl font-bold text-[#E6CD8A]" style="font-family:'Noto Naskh Arabic','Vazirmatn',serif">احراز هویت دو مرحله‌ای</h1>
            </div>
        </div>

        @if(session('error'))
            <div class="mb-5 text-sm rounded-xl p-3 bg-red-500/10 text-red-400 border border-red-500/20">
                {{ session('error') }}
            </div>
        @endif

        <div class="bg-[#2E2117] rounded-2xl border border-[#C9A24B]/10 overflow-hidden">
            <div class="px-6 py-4 border-b border-[#C9A24B]/10 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl {{ $enabled ? 'bg-emerald-500/15' : 'bg-[#C9A24B]/15' }} flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 {{ $enabled ? 'text-emerald-400' : 'text-[#E6CD8A]' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                </div>
                <div>
                    <p class="font-bold text-sm text-[#F8F3E9]">
                        وضعیت: <span class="{{ $enabled ? 'text-emerald-400' : 'text-[#F8F3E9]/50' }}">{{ $enabled ? 'فعال' : 'غیرفعال' }}</span>
                    </p>
                    <p class="text-xs text-[#F8F3E9]/45 mt-0.5">
                        با فعال بودن این گزینه، امکان استفاده از «پرداخت امن» فراهم می‌شود.
                    </p>
                </div>
            </div>

            <div class="p-6">
                @if(!$enabled)
                    <p class="text-sm text-[#F8F3E9]/60 mb-4">
                        با فعال‌سازی احراز هویت دو مرحله‌ای، یک لایه‌ی امنیتی اضافه با کد پیامکی به حساب شما اضافه می‌شود.
                    </p>
                    <a href="{{ route('security.2fa.setup') }}"
                       class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl text-sm font-bold transition-all
                          bg-gradient-to-l from-[#C9A24B] to-[#E6CD8A] text-[#1A1410] hover:shadow-lg hover:shadow-[#C9A24B]/30">
                        فعال‌سازی احراز هویت دو مرحله‌ای
                    </a>
                @else
                    <div id="disable-step-1">
                        <p class="text-sm text-[#F8F3E9]/60 mb-4">
                            برای غیرفعال‌سازی، ابتدا یک کد تایید برای شماره موبایل شما ارسال می‌شود.
                        </p>
                        <button onclick="requestDisableCode()" id="request-disable-btn"
                                class="px-5 py-2.5 rounded-xl text-sm font-semibold border border-red-500/30 text-red-400 hover:bg-red-500/10 transition-colors">
                            <span id="request-disable-text">ارسال کد برای غیرفعال‌سازی</span>
                        </button>
                    </div>

                    <div id="disable-step-2" class="hidden mt-5 pt-5 border-t border-[#C9A24B]/10">
                        <label class="block text-sm text-[#F8F3E9]/70 mb-2">کد ۶ رقمی ارسال‌شده را وارد کنید</label>
                        <div class="flex gap-2">
                            <input type="text" id="disable-code" maxlength="6" inputmode="numeric"
                                   class="flex-1 bg-white/5 border border-[#C9A24B]/25 text-[#F8F3E9] rounded-xl px-4 py-2.5 text-sm tracking-widest text-center"
                                   dir="ltr" placeholder="------">
                            <button onclick="confirmDisable()" id="confirm-disable-btn"
                                    class="px-5 py-2.5 rounded-xl text-sm font-bold bg-red-500/90 text-white hover:bg-red-500 transition-colors">
                                غیرفعال‌سازی
                            </button>
                        </div>
                    </div>

                    <div id="disable-message" class="hidden mt-4 text-sm rounded-xl p-3"></div>
                @endif
            </div>
        </div>
    </div>

    <script>
        const resendUrl = '{{ route('security.2fa.resend') }}';
        const disableUrl = '{{ route('security.2fa.disable') }}';
        const csrfToken = '{{ csrf_token() }}';

        function showDisableMessage(text, isError) {
            const el = document.getElementById('disable-message');
            if (!el) return;
            el.textContent = text;
            el.classList.remove('hidden', 'bg-red-500/10', 'text-red-400', 'border-red-500/20', 'bg-emerald-500/10', 'text-emerald-400', 'border-emerald-500/20');
            el.classList.add('border', isError ? 'bg-red-500/10' : 'bg-emerald-500/10', isError ? 'text-red-400' : 'text-emerald-400', isError ? 'border-red-500/20' : 'border-emerald-500/20');
        }

        function requestDisableCode() {
            const btn = document.getElementById('request-disable-btn');
            btn.disabled = true;

            fetch(resendUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            })
                .then(async (r) => ({ ok: r.ok, data: await r.json() }))
                .then(({ ok, data }) => {
                    if (ok) {
                        document.getElementById('disable-step-2').classList.remove('hidden');
                    }
                    showDisableMessage(ok ? (data.message || 'کد ارسال شد.') : (data.error || 'خطا در ارسال کد.'), !ok);
                })
                .catch(() => showDisableMessage('خطا در ارسال کد.', true))
                .finally(() => { btn.disabled = false; });
        }

        function confirmDisable() {
            const code = document.getElementById('disable-code').value.trim();
            if (code.length !== 6) {
                showDisableMessage('لطفاً کد ۶ رقمی را کامل وارد کنید.', true);
                return;
            }

            fetch(disableUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ code })
            })
                .then(async (r) => ({ ok: r.ok, data: await r.json() }))
                .then(({ ok, data }) => {
                    if (ok) {
                        showDisableMessage('احراز هویت دو مرحله‌ای غیرفعال شد. در حال بارگذاری مجدد...', false);
                        setTimeout(() => window.location.reload(), 1000);
                        return;
                    }
                    showDisableMessage(data.error || 'کد وارد شده نامعتبر است.', true);
                });
        }
    </script>
@endsection
