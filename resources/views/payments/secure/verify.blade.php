@extends('layouts.app')
@section('title', 'تایید نهایی پرداخت')

@section('content')
    <div class="max-w-md mx-auto fade-in">

        <div class="text-center mb-8">
            <div class="w-14 h-14 rounded-full bg-[#C9A24B]/15 flex items-center justify-center mx-auto mb-4">
                <svg class="w-7 h-7 text-[#E6CD8A]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-[#E6CD8A]" style="font-family:'Noto Naskh Arabic','Vazirmatn',serif">تایید نهایی پرداخت</h1>
            <p class="text-sm text-[#F8F3E9]/55 mt-1">جزئیات را بررسی کنید و تراکنش را نهایی کنید</p>
        </div>

        <div class="bg-[#2E2117] rounded-2xl border border-[#C9A24B]/10 overflow-hidden mb-5">
            <div class="divide-y divide-[#C9A24B]/8">
                <div class="flex justify-between items-center px-5 py-3.5 text-sm">
                    <span class="text-[#F8F3E9]/55">خدمت</span>
                    <span class="font-medium text-[#F8F3E9]">{{ $booking->service?->name ?? 'خدمت نامشخص' }}</span>
                </div>
                <div class="flex justify-between items-center px-5 py-3.5 text-sm">
                    <span class="text-[#F8F3E9]/55">شماره نوبت</span>
                    <span class="font-medium text-[#F8F3E9] persian-number">#{{ $booking->id }}</span>
                </div>
                <div class="flex justify-between items-center px-5 py-3.5 text-sm">
                    <span class="text-[#F8F3E9]/55">مبلغ قابل پرداخت</span>
                    <span class="font-bold text-[#E6CD8A] persian-number">{{ number_format($payment->amount) }} تومان</span>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-center gap-2 text-sm mb-5">
            <svg class="w-4 h-4 text-[#C9A24B]/70" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/><path stroke-linecap="round" d="M12 6v6l4 2"/>
            </svg>
            <span class="text-[#F8F3E9]/55">مهلت باقی‌مانده:</span>
            <span id="countdown-timer" class="font-bold text-[#E6CD8A] tabular-nums">--:--</span>
        </div>

        <form method="POST" action="{{ route('payments.secure.verify.submit', $payment->reference_id) }}" id="verify-form">
            @csrf
            <button type="submit" id="verify-submit-btn"
                    class="w-full py-3.5 rounded-xl text-sm font-bold transition-all
                       bg-gradient-to-l from-[#C9A24B] to-[#E6CD8A] text-[#1A1410]
                       hover:shadow-lg hover:shadow-[#C9A24B]/30 disabled:opacity-50 disabled:cursor-not-allowed">
                <span id="verify-submit-text">تایید و تکمیل پرداخت</span>
            </button>
        </form>

        <div class="mt-4 text-center">
            <a href="{{ route('bookings.show', $booking) }}" class="text-sm text-[#F8F3E9]/50 hover:text-[#E6CD8A] transition-colors">
                انصراف
            </a>
        </div>
    </div>

    <script>
        let remaining = {{ $payment->getRemainingTime() ?? 0 }};
        const timerEl = document.getElementById('countdown-timer');
        const submitBtn = document.getElementById('verify-submit-btn');
        const submitText = document.getElementById('verify-submit-text');
        const form = document.getElementById('verify-form');

        form.addEventListener('submit', function () {
            submitBtn.disabled = true;
            submitText.textContent = 'در حال تایید...';
        });

        function tick() {
            if (remaining <= 0) {
                timerEl.textContent = '00:00';
                timerEl.classList.replace('text-[#E6CD8A]', 'text-red-400');
                submitBtn.disabled = true;
                submitText.textContent = 'مهلت به پایان رسید';
                clearInterval(interval);
                return;
            }
            const m = String(Math.floor(remaining / 60)).padStart(2, '0');
            const s = String(remaining % 60).padStart(2, '0');
            timerEl.textContent = `${m}:${s}`;
            remaining--;
        }

        tick();
        const interval = setInterval(tick, 1000);
    </script>
@endsection
