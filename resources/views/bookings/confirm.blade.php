@extends('layouts.app')

@section('title', 'تأیید رزرو')

@section('content')
    <div class="max-w-2xl mx-auto fade-in">
        <div class="mb-8">
            <p class="text-xs font-semibold text-[#C9A24B] tracking-[0.3em] uppercase mb-1">مرحله نهایی</p>
            <h1 class="text-2xl md:text-3xl font-bold text-[#E6CD8A]"
                style="font-family:'Noto Naskh Arabic','Vazirmatn',serif">تأیید و پرداخت نوبت</h1>
        </div>

        <div class="bg-[#2E2117] rounded-2xl border border-[#C9A24B]/10 p-6 md:p-8 space-y-6">

            {{-- Appointment information --}}
            <div>
                <h2 class="text-base font-bold text-[#E6CD8A] mb-4 flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#C9A24B]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    اطلاعات نوبت
                </h2>
                <div class="bg-[#1A1410]/60 rounded-xl border border-[#C9A24B]/8 divide-y divide-[#C9A24B]/8">
                    @foreach([
                        ['label' => 'خدمت', 'value' => $service->name],
                        ['label' => 'متخصص', 'value' => $specialist->name],
                        ['label' => 'تاریخ', 'value' => verta($bookingTime)->format('Y/m/d'), 'persian' => true],
                        ['label' => 'ساعت', 'value' => verta($bookingTime)->format('H:i')],
                        ['label' => 'مدت زمان', 'value' => $service->duration . ' دقیقه', 'persian' => true],
                        ['label' => 'قیمت کل خدمت', 'value' => number_format($service->price) . ' تومان', 'persian' => true],
                    ] as $row)
                        <div class="flex items-center justify-between px-5 py-3.5 text-sm">
                            <span class="text-[#F8F3E9]/55">{{ $row['label'] }}</span>
                            <span class="font-medium text-[#F8F3E9] {{ isset($row['persian']) ? 'persian-number' : '' }}">
                            {{ $row['value'] }}
                        </span>
                        </div>
                    @endforeach
                    <div class="flex items-center justify-between px-5 py-4 text-sm">
                        <span class="font-bold text-[#F8F3E9]">مبلغ پیش‌پرداخت</span>
                        <span class="font-bold text-[#E6CD8A] text-lg persian-number" id="final-price">
                        {{ number_format($prepaymentAmount) }} تومان
                    </span>
                    </div>
                    <div class="flex items-center justify-between px-5 py-3.5 text-sm">
                        <span class="text-[#F8F3E9]/55">
                            باقی‌مانده (موقع نوبت به متخصص پرداخت می‌کنید)
                        </span>
                        <span class="font-medium text-[#F8F3E9]/80 persian-number" id="remaining-amount">
                            {{ number_format(max(0, $service->price - $prepaymentAmount)) }} تومان
                        </span>
                    </div>
                </div>
            </div>

            {{-- Informational note: the remaining-amount split above is purely for the customer's
                 own planning — it is never part of any wallet/commission calculation. Only the
                 prepayment amount is ever actually charged through the platform. --}}
            <div class="flex items-start gap-3 bg-amber-900/15 border border-amber-700/25 rounded-xl px-4 py-3 text-xs text-amber-300/90">
                <svg class="w-4 h-4 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.72-1.36 3.486 0l6.518 11.6c.75 1.334-.213 2.99-1.743 2.99H3.482c-1.53 0-2.493-1.656-1.743-2.99l6.518-11.6zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                فقط مبلغ «پیش‌پرداخت» از طریق درگاه پرداخت می‌شود؛ مابقی مبلغ خدمت را موقع حضور در سالن، طبق توافق با متخصص، به‌صورت مستقیم پرداخت خواهید کرد.
            </div>

            {{-- Explanation --}}
            <div class="flex items-start gap-3 bg-sky-900/20 border border-sky-700/30 rounded-xl px-4 py-3 text-sm text-sky-300">
                <svg class="w-4 h-4 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                با تأیید نهایی و پرداخت، نوبت شما ثبت خواهد شد. لطفاً اطلاعات بالا را با دقت بررسی کنید.
            </div>

            {{-- Discount code --}}
            <div>
                <h3 class="text-sm font-medium text-[#E6CD8A] mb-3 flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-[#C9A24B]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                    کد تخفیف
                </h3>
                <div class="flex gap-2">
                    <input type="text" id="discount-code" placeholder="کد تخفیف خود را وارد کنید"
                           class="flex-1 rounded-xl px-4 py-2.5 text-sm
                              bg-white/5 border border-[#C9A24B]/20 text-[#F8F3E9] placeholder-[#F8F3E9]/30
                              focus:outline-none focus:border-[#C9A24B] focus:ring-2 focus:ring-[#C9A24B]/15 transition-colors">
                    <button type="button" id="apply-discount"
                            class="px-5 py-2.5 rounded-xl text-sm font-semibold transition-all
                               bg-emerald-700/30 border border-emerald-600/40 text-emerald-300
                               hover:bg-emerald-700/50">
                        اعمال
                    </button>
                </div>
                <div id="discount-message" class="mt-2 text-xs hidden"></div>
            </div>

            {{-- Final form --}}
            <form action="{{ route('bookings.store') }}" method="POST">
                @csrf
                <input type="hidden" name="service_id" value="{{ $service->id }}">
                <input type="hidden" name="specialist_id" value="{{ $specialist->id }}">
                <input type="hidden" name="booking_time" value="{{ $bookingTime }}">
                <input type="hidden" name="discount_code" id="hidden-discount-code" value="">

                <div class="flex gap-3 pt-2">
                    <a href="{{ route('bookings.create') }}"
                       class="flex-1 text-center py-3 rounded-xl text-sm border border-[#C9A24B]/25
                          text-[#F8F3E9]/70 hover:bg-[#C9A24B]/10 transition-colors">
                        بازگشت و ویرایش
                    </a>
                    <button type="submit"
                            class="flex-1 py-3 rounded-xl text-sm font-bold transition-all duration-300
                               bg-gradient-to-l from-[#C9A24B] to-[#E6CD8A] text-[#1A1410]
                               hover:shadow-lg hover:shadow-[#C9A24B]/30 hover:-translate-y-0.5">
                        تأیید و رفتن به درگاه پرداخت
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const applyBtn = document.getElementById('apply-discount');
            const codeInput = document.getElementById('discount-code');
            const hiddenCode = document.getElementById('hidden-discount-code');
            const discountMsg = document.getElementById('discount-message');
            const remainingEl = document.getElementById('remaining-amount');
            const servicePrice = {{ (int) $service->price }};
            const prepaymentAmount = {{ (int) $prepaymentAmount }};

            // ⭐ Business decision: a discount code never changes the prepayment charged online
            // (finalPriceEl) — it only reduces the "remaining" amount settled with the specialist in
            // person. Reducing the prepayment instead would have made discounts a wash: remaining =
            // price - prepayment, so shrinking the prepayment would grow remaining by the exact same
            // amount, leaving the customer's total payment (prepayment + remaining) completely
            // unchanged. finalPriceEl is therefore never rewritten below; only remainingEl is.
            function updateRemaining(discountAmount) {
                const remaining = Math.max(0, servicePrice - prepaymentAmount - discountAmount);
                remainingEl.innerHTML = `${remaining.toLocaleString('fa-IR')} تومان`;
            }

            applyBtn.addEventListener('click', async function() {
                const code = codeInput.value.trim();
                if (!code) {
                    discountMsg.innerHTML = 'لطفاً کد تخفیف را وارد کنید';
                    discountMsg.className = 'mt-2 text-xs text-red-400';
                    discountMsg.classList.remove('hidden');
                    return;
                }
                try {
                    discountMsg.innerHTML = 'در حال بررسی...';
                    discountMsg.className = 'mt-2 text-xs text-[#E6CD8A]/70';
                    discountMsg.classList.remove('hidden');

                    const response = await fetch('{{ route('bookings.check-discount') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ code, service_id: '{{ $service->id }}' })
                    });
                    const result = await response.json();

                    if (response.ok && result.valid) {
                        discountMsg.innerHTML = `✓ کد معتبر: ${result.discount_amount.toLocaleString('fa-IR')} تومان تخفیف (از باقی‌مانده کسر می‌شود)`;
                        discountMsg.className = 'mt-2 text-xs text-emerald-400';
                        hiddenCode.value = code;
                        updateRemaining(result.discount_amount);
                    } else {
                        discountMsg.innerHTML = '✗ ' + (result.message || 'کد تخفیف نامعتبر است');
                        discountMsg.className = 'mt-2 text-xs text-red-400';
                        hiddenCode.value = '';
                        updateRemaining(0);
                    }
                } catch (e) {
                    discountMsg.innerHTML = 'خطا در بررسی کد تخفیف';
                    discountMsg.className = 'mt-2 text-xs text-red-400';
                }
            });
        });
    </script>
@endpush
