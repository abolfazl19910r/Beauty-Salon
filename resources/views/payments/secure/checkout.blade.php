@extends('layouts.app')
@section('title', 'پرداخت امن نوبت')

@section('content')
    <style>
        .secure-btn {
            background: var(--m-btn); color: var(--m-on-gold);
        }
        .secure-btn:disabled { opacity: 0.5; cursor: not-allowed; }
    </style>

    <div class="max-w-2xl mx-auto fade-in">

        {{-- Header --}}
        <div class="flex items-center gap-3 mb-8">
            <div class="w-10 h-10 rounded-xl bg-m-gold/15 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-m-gold2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
            </div>
            <div>
                <p class="text-xs font-semibold text-m-gold tracking-[0.3em] uppercase mb-0.5">پرداخت امن</p>
                <h1 class="text-2xl font-bold text-m-gold2" style="font-family:'Mahru','Vazirmatn',sans-serif;font-weight:800;">پرداخت با تایید دو مرحله‌ای</h1>
                <p class="text-m-tx/50 text-sm persian-number">شماره نوبت: {{ $booking->id }}</p>
            </div>
        </div>

        {{-- Appointment details --}}
        <div class="bg-m-card rounded-2xl border border-m-gold/10 overflow-hidden mb-5">
            <div class="px-5 py-3.5 border-b border-m-gold/10">
                <h2 class="font-bold text-sm text-m-gold2">جزئیات نوبت</h2>
            </div>
            <div class="divide-y divide-m-gold/8">
                <div class="flex justify-between items-center px-5 py-3.5 text-sm">
                    <span class="text-m-tx/55">خدمت</span>
                    <span class="font-medium text-m-tx">{{ $booking->service?->name ?? 'خدمت نامشخص' }}</span>
                </div>
                <div class="flex justify-between items-center px-5 py-3.5 text-sm">
                    <span class="text-m-tx/55">متخصص</span>
                    <span class="font-medium text-m-tx">{{ $booking->specialist?->name ?? 'متخصص نامشخص' }}</span>
                </div>
                <div class="flex justify-between items-center px-5 py-3.5 text-sm">
                    <span class="text-m-tx/55">تاریخ و ساعت</span>
                    <span class="font-medium text-m-tx persian-number" dir="ltr">{{ verta($booking->booking_time)->format('Y/m/d H:i') }}</span>
                </div>
                <div class="flex justify-between items-center px-5 py-3.5 text-sm">
                    <span class="text-m-tx/55">مبلغ قابل پرداخت</span>
                    <span class="font-bold text-m-gold2 persian-number">{{ number_format($booking->prepayment_amount) }} تومان</span>
                </div>
            </div>
        </div>

        <div class="bg-m-ok/12 border border-m-ok/20 rounded-2xl p-5 mb-5 flex items-start gap-3">
            <svg class="w-5 h-5 text-m-ok shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
            <p class="text-sm text-m-ok/90">
                این مسیر پرداخت با یک لایه‌ی امنیتی اضافه (تایید دو مرحله‌ای) محافظت می‌شود. هویت شما پیش‌تر در همین نشست تایید شده و پس از تایید تراکنش، نوبت شما بلافاصله نهایی می‌شود.
            </p>
        </div>

        <div id="checkout-message" class="hidden mb-4 text-sm rounded-xl p-3"></div>

        <button id="start-payment-btn" onclick="startSecurePayment()"
                class="secure-btn w-full py-3.5 rounded-xl text-sm font-bold transition-all hover:shadow-lg hover:shadow-[#C9A24B]/30">
            <span id="start-payment-text">شروع پرداخت امن</span>
        </button>

        <div class="mt-4 text-center">
            <a href="{{ route('payment.show', $booking) }}" class="text-sm text-m-tx/50 hover:text-m-gold2 transition-colors">
                بازگشت به پرداخت معمولی
            </a>
        </div>
    </div>

    <script>
        function startSecurePayment() {
            const btn = document.getElementById('start-payment-btn');
            const text = document.getElementById('start-payment-text');
            const messageEl = document.getElementById('checkout-message');
            const originalText = text.textContent;

            messageEl.classList.add('hidden');
            btn.disabled = true;
            text.textContent = 'در حال آماده‌سازی...';

            fetch('{{ route("payments.secure.initiate", $booking) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.redirect_url) {
                        window.location.href = data.redirect_url;
                        return;
                    }
                    showMessage(data.message || 'خطا در شروع پرداخت.');
                    btn.disabled = false;
                    text.textContent = originalText;
                })
                .catch(() => {
                    showMessage('خطا در ارتباط با سرور.');
                    btn.disabled = false;
                    text.textContent = originalText;
                });
        }

        function showMessage(msg) {
            const el = document.getElementById('checkout-message');
            el.textContent = msg;
            el.classList.remove('hidden');
            el.classList.add('bg-m-bad/10', 'text-m-bad', 'border', 'border-m-bad/20');
        }
    </script>
@endsection
