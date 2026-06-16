@extends('layouts.app')
@section('title', 'پرداخت نوبت')

@section('content')
    <style>
        .gold-input {
            background:rgba(248,243,233,0.04); border:1px solid rgba(201,162,75,0.25);
            color:#F8F3E9; border-radius:0.75rem; padding:0.75rem 1rem; font-size:0.9rem;
        }
        .gold-input::placeholder { color:rgba(248,243,233,0.3); }
        .gold-input:focus { outline:none; border-color:#C9A24B; box-shadow:0 0 0 3px rgba(201,162,75,0.15); }
        .gold-input:disabled { opacity:0.5; cursor:not-allowed; }

        .toggle-switch {
            width:3.25rem; height:1.65rem; background:rgba(248,243,233,0.1); border-radius:999px;
            position:relative; transition:background .25s; cursor:pointer;
        }
        .toggle-switch.active { background:#34D399; }
        .toggle-knob {
            position:absolute; top:2px; right:2px; width:1.4rem; height:1.4rem; border-radius:999px;
            background:#fff; transition:transform .25s;
        }
        .toggle-switch.active .toggle-knob { transform:translateX(-1.55rem); }

        .wallet-option {
            border:2px solid transparent; transition:all .2s; cursor:pointer;
        }
        .wallet-option:hover { border-color:rgba(52,211,153,0.3); }
        .wallet-option input:checked ~ .opt-indicator { border-color:#34D399; background:#34D399; }
    </style>

    <div class="max-w-2xl mx-auto fade-in">

        {{-- Header --}}
        <div class="flex items-center gap-3 mb-8">
            <div class="w-10 h-10 rounded-xl bg-[#C9A24B]/15 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-[#E6CD8A]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/>
                </svg>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-[#E6CD8A]" style="font-family:'Noto Naskh Arabic','Vazirmatn',serif">پرداخت نوبت</h1>
                <p class="text-[#F8F3E9]/50 text-sm persian-number">شماره نوبت: {{ $booking->id }}</p>
            </div>
        </div>

        {{-- Appointment details --}}
        <div class="bg-[#2E2117] rounded-2xl border border-[#C9A24B]/10 overflow-hidden mb-5">
            <div class="px-5 py-3.5 border-b border-[#C9A24B]/10">
                <h2 class="font-bold text-sm text-[#E6CD8A]">جزئیات نوبت</h2>
            </div>
            <div class="divide-y divide-[#C9A24B]/8">
                <div class="flex justify-between items-center px-5 py-3.5 text-sm">
                    <span class="text-[#F8F3E9]/55">خدمت</span>
                    <span class="font-medium text-[#F8F3E9]">{{ $booking->service ? $booking->service->name : 'خدمت نامشخص' }}</span>
                </div>
                <div class="flex justify-between items-center px-5 py-3.5 text-sm">
                    <span class="text-[#F8F3E9]/55">متخصص</span>
                    <span class="font-medium text-[#F8F3E9]">{{ $booking->specialist ? $booking->specialist->name : 'متخصص نامشخص' }}</span>
                </div>
                <div class="flex justify-between items-center px-5 py-3.5 text-sm">
                    <span class="text-[#F8F3E9]/55">تاریخ و ساعت</span>
                    <span class="font-medium text-[#F8F3E9] persian-number" dir="ltr">{{ verta($booking->booking_time)->format('Y/m/d H:i') }}</span>
                </div>
            </div>
        </div>

        {{-- Discount code --}}
        <div class="bg-[#C9A24B]/8 border border-[#C9A24B]/20 rounded-2xl p-5 mb-5">
            <h3 class="font-bold text-sm text-[#E6CD8A] mb-3 flex items-center gap-1.5">
                <svg class="w-4 h-4 text-[#C9A24B]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                </svg>
                کد تخفیف دارید؟
            </h3>
            <div class="flex gap-2">
                <input type="text" id="discount_code" placeholder="کد تخفیف را وارد کنید"
                       class="flex-1 gold-input"
                       @if($booking->discount_code) value="{{ $booking->discount_code }}" disabled @endif>
                <button onclick="applyDiscount()" id="apply-discount-btn"
                        @if($booking->discount_code) disabled @endif
                        class="px-5 py-2.5 rounded-xl text-sm font-semibold transition-all whitespace-nowrap
                           bg-gradient-to-l from-[#C9A24B] to-[#E6CD8A] text-[#1A1410]
                           disabled:opacity-40 disabled:cursor-not-allowed
                           hover:shadow-md hover:shadow-[#C9A24B]/20">
                    اعمال
                </button>
            </div>
            <div id="discount-message" class="mt-3 text-sm hidden"></div>

            @if(!$booking->discount_code)
                <p class="mt-3 text-xs text-[#F8F3E9]/55">
                    از پنل امتیازات می‌توانید امتیازات خود را به کد تخفیف تبدیل کنید!
                    <a href="{{ route('loyalty.index') }}" class="underline text-[#E6CD8A]">مشاهده امتیازات</a>
                </p>
            @endif
        </div>

        {{-- Wallet --}}
        @if($wallet->balance > 0)
            <div class="bg-emerald-900/12 border border-emerald-500/20 rounded-2xl p-5 mb-5">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 rounded-xl bg-emerald-500/20 flex items-center justify-center">
                            <svg class="w-5 h-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="font-bold text-[#F8F3E9] text-sm">موجودی کیف پول شما</p>
                            <p class="text-xl font-bold text-emerald-400 persian-number">{{ number_format($wallet->balance) }} تومان</p>
                        </div>
                    </div>
                    <div id="wallet-toggle" class="toggle-switch" onclick="toggleWalletPayment()">
                        <div class="toggle-knob"></div>
                    </div>
                </div>

                <div id="wallet-options" class="hidden mt-4 space-y-2 border-t border-emerald-500/15 pt-4">
                    <label class="wallet-option flex items-center p-3 bg-[#1A1410]/50 rounded-xl">
                        <input type="radio" name="wallet_option" value="full" class="hidden" onchange="updatePaymentAmount()"
                            {{ $wallet->balance >= $booking->prepayment_amount ? '' : 'disabled' }}>
                        <span class="opt-indicator w-4 h-4 rounded-full border-2 border-[#F8F3E9]/30 ml-3 shrink-0"></span>
                        <div class="flex-1">
                            <p class="font-medium text-[#F8F3E9] text-sm">پرداخت کامل از کیف پول</p>
                            <p class="text-xs mt-0.5">
                                @if($wallet->balance >= $booking->prepayment_amount)
                                    <span class="text-emerald-400">موجودی شما کافی است</span>
                                @else
                                    <span class="text-red-400">موجودی کافی نیست (کمبود: {{ number_format($booking->prepayment_amount - $wallet->balance) }})</span>
                                @endif
                            </p>
                        </div>
                        <p class="font-bold text-emerald-400 text-sm persian-number">{{ number_format(min($wallet->balance, $booking->prepayment_amount)) }}</p>
                    </label>

                    @if($wallet->balance < $booking->prepayment_amount)
                        <label class="wallet-option flex items-center p-3 bg-[#1A1410]/50 rounded-xl">
                            <input type="radio" name="wallet_option" value="partial" class="hidden" onchange="updatePaymentAmount()">
                            <span class="opt-indicator w-4 h-4 rounded-full border-2 border-[#F8F3E9]/30 ml-3 shrink-0"></span>
                            <div class="flex-1">
                                <p class="font-medium text-[#F8F3E9] text-sm">پرداخت ترکیبی</p>
                                <p class="text-xs text-blue-300 mt-0.5">
                                    {{ number_format($wallet->balance) }} از کیف پول + {{ number_format($booking->prepayment_amount - $wallet->balance) }} از درگاه
                                </p>
                            </div>
                            <p class="font-bold text-blue-400 text-sm persian-number">{{ number_format($wallet->balance) }}</p>
                        </label>
                    @endif
                </div>
            </div>
        @endif

        {{-- Payment Summary --}}
        <div class="bg-[#2E2117] rounded-2xl border border-[#C9A24B]/10 p-5 mb-6">
            <h3 class="font-bold text-sm text-[#E6CD8A] mb-4 flex items-center gap-1.5">
                <svg class="w-4 h-4 text-[#C9A24B]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                خلاصه پرداخت
            </h3>
            <div class="space-y-3 text-sm">
                @if($booking->service)
                    <div class="flex justify-between border-b border-[#C9A24B]/8 pb-3">
                        <span class="text-[#F8F3E9]/55">مبلغ کل خدمت</span>
                        <span class="text-[#F8F3E9]/80 persian-number">{{ number_format($booking->service->price) }} تومان</span>
                    </div>
                @endif
                <div class="flex justify-between border-b border-[#C9A24B]/8 pb-3">
                    <span class="text-[#F8F3E9]/55">مبلغ پیش‌پرداخت</span>
                    <span class="font-bold text-[#F8F3E9] persian-number" id="original-amount">{{ number_format($booking->prepayment_amount) }} تومان</span>
                </div>

                @if($booking->discount_amount > 0)
                    <div class="flex justify-between border-b border-[#C9A24B]/8 pb-3 text-emerald-400">
                        <span>تخفیف اعمال‌شده ({{ $booking->discount_code }})</span>
                        <span class="persian-number">- {{ number_format($booking->discount_amount) }} تومان</span>
                    </div>
                @endif

                <div id="discount-summary" class="hidden flex justify-between border-b border-[#C9A24B]/8 pb-3 text-emerald-400">
                    <span>تخفیف</span>
                    <span class="font-semibold persian-number" id="discount-amount">0 تومان</span>
                </div>

                <div id="wallet-payment-summary" class="hidden">
                    <div class="flex justify-between border-b border-[#C9A24B]/8 pb-3 text-emerald-400">
                        <span>پرداخت از کیف پول</span>
                        <span class="font-semibold persian-number" id="wallet-amount">0 تومان</span>
                    </div>
                    <div class="flex justify-between border-b border-[#C9A24B]/8 pb-3 pt-3 text-[#C9A24B]">
                        <span>پرداخت از درگاه</span>
                        <span class="font-semibold persian-number" id="gateway-amount">{{ number_format($booking->prepayment_amount) }} تومان</span>
                    </div>
                </div>

                <div class="flex justify-between items-center text-lg font-bold pt-2 border-t-2 border-[#C9A24B]/20">
                    <span class="text-[#E6CD8A]">مبلغ قابل پرداخت</span>
                    <span class="text-[#E6CD8A] persian-number" id="final-amount">{{ number_format($booking->prepayment_amount) }} تومان</span>
                </div>
            </div>
        </div>

        <form id="payment-form" method="POST" action="{{ route('payment.process', $booking) }}">
            @csrf
            <input type="hidden" name="use_wallet" id="use_wallet" value="0">
            <input type="hidden" name="wallet_amount" id="wallet_amount" value="0">

            <button type="submit"
                    class="w-full py-3.5 rounded-xl text-sm font-bold transition-all
                       bg-gradient-to-l from-[#C9A24B] to-[#E6CD8A] text-[#1A1410]
                       hover:shadow-lg hover:shadow-[#C9A24B]/30">
                <span id="payment-button-text">پرداخت و تایید نوبت</span>
            </button>
        </form>

        <div class="mt-4 text-center">
            <a href="{{ route('bookings.index') }}" class="text-sm text-[#F8F3E9]/50 hover:text-[#E6CD8A] transition-colors">
                انصراف و بازگشت به لیست نوبت‌ها
            </a>
        </div>

        <div class="mt-6 pt-5 border-t border-[#C9A24B]/10 flex items-start gap-2 text-xs text-[#F8F3E9]/50">
            <svg class="w-4 h-4 text-emerald-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
            پرداخت شما از طریق درگاه امن زرین‌پال انجام می‌شود و اطلاعات کارت بانکی شما ذخیره نمی‌گردد.
        </div>
    </div>

    <script>
        const bookingId = {{ $booking->id }};
        let bookingAmount = {{ $booking->prepayment_amount }};
        const walletBalance = {{ $wallet->balance }};
        let currentDiscount = {{ $booking->discount_amount ?? 0 }};

        function applyDiscount() {
            const codeInput = document.getElementById('discount_code');
            const messageDiv = document.getElementById('discount-message');
            const applyBtn = document.getElementById('apply-discount-btn');
            const code = codeInput.value.trim();

            messageDiv.classList.add('hidden');

            if (!code) {
                showMessage(messageDiv, 'لطفاً کد تخفیف را وارد کنید.', 'error');
                return;
            }
            const originalBtnText = applyBtn.innerHTML;
            applyBtn.disabled = true;
            applyBtn.innerHTML = '...';

            fetch('{{ route("bookings.check-discount") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ code: code, booking_id: {{ $booking->id }} })
            })
                .then(r => r.json())
                .then(data => {
                    if (data.valid) {
                        currentDiscount = data.discount_amount;
                        bookingAmount = data.final_price;

                        const discountSummary = document.getElementById('discount-summary');
                        const discountAmountEl = document.getElementById('discount-amount');
                        discountSummary.classList.remove('hidden');
                        discountAmountEl.textContent = '- ' + new Intl.NumberFormat('fa-IR').format(currentDiscount) + ' تومان';

                        showMessage(messageDiv, '✓ ' + data.message, 'success');

                        const paymentBtnText = document.getElementById('payment-button-text');
                        const walletOptions = document.getElementById('wallet-options');
                        const walletToggle = document.getElementById('wallet-toggle');

                        if (bookingAmount === 0) {
                            paymentBtnText.textContent = 'تایید نهایی نوبت (رایگان)';
                            walletOptions.classList.add('hidden');
                            walletToggle.classList.remove('active');
                            document.getElementById('payment-form').action = '{{ route("payment.process", $booking) }}';
                        } else {
                            paymentBtnText.textContent = 'پرداخت و نهایی کردن رزرو';
                        }
                        updatePaymentAmount();
                    } else {
                        showMessage(messageDiv, '✗ ' + (data.message || 'کد تخفیف نامعتبر است.'), 'error');
                    }
                })
                .catch(() => showMessage(messageDiv, 'خطا در ارتباط با سرور.', 'error'))
                .finally(() => {
                    applyBtn.disabled = false;
                    applyBtn.innerHTML = originalBtnText;
                });
        }

        function showMessage(el, text, type) {
            el.textContent = text;
            el.classList.remove('hidden', 'text-emerald-400', 'text-red-400');
            el.classList.add(type === 'success' ? 'text-emerald-400' : 'text-red-400');
        }

        function toggleWalletPayment() {
            const toggle = document.getElementById('wallet-toggle');
            const walletOptions = document.getElementById('wallet-options');
            const paymentForm = document.getElementById('payment-form');
            const isActive = toggle.classList.contains('active');

            if (!isActive) {
                toggle.classList.add('active');
                walletOptions.classList.remove('hidden');
                const fullOpt = document.querySelector('input[name="wallet_option"][value="full"]');
                const partialOpt = document.querySelector('input[name="wallet_option"][value="partial"]');
                if (walletBalance >= bookingAmount && fullOpt) fullOpt.checked = true;
                else if (partialOpt) partialOpt.checked = true;
                paymentForm.action = '{{ route("payment.wallet", $booking) }}';
                updatePaymentAmount();
            } else {
                toggle.classList.remove('active');
                walletOptions.classList.add('hidden');
                document.getElementById('use_wallet').value = '0';
                document.getElementById('wallet_amount').value = '0';
                document.getElementById('wallet-payment-summary').classList.add('hidden');
                document.getElementById('final-amount').textContent = bookingAmount.toLocaleString('fa-IR') + ' تومان';
                document.getElementById('payment-button-text').textContent = 'پرداخت و تایید نوبت';
                paymentForm.action = '{{ route("payment.process", $booking) }}';
            }
        }

        function updatePaymentAmount() {
            const toggle = document.getElementById('wallet-toggle');
            const paymentForm = document.getElementById('payment-form');
            if (!toggle.classList.contains('active')) {
                paymentForm.action = '{{ route("payment.process", $booking) }}';
                document.getElementById('final-amount').textContent = bookingAmount.toLocaleString('fa-IR') + ' تومان';
                return;
            }

            const walletOption = document.querySelector('input[name="wallet_option"]:checked');
            if (!walletOption) return;

            const useWalletFull = walletOption.value === 'full';
            const walletAmount = useWalletFull ? Math.min(walletBalance, bookingAmount) : walletBalance;
            const gatewayAmount = Math.max(0, bookingAmount - walletAmount);

            document.getElementById('use_wallet').value = '1';
            document.getElementById('wallet_amount').value = walletAmount;

            document.getElementById('wallet-payment-summary').classList.remove('hidden');
            document.getElementById('wallet-amount').textContent = walletAmount.toLocaleString('fa-IR') + ' تومان';
            document.getElementById('gateway-amount').textContent = gatewayAmount.toLocaleString('fa-IR') + ' تومان';
            document.getElementById('final-amount').textContent = gatewayAmount.toLocaleString('fa-IR') + ' تومان';

            if (gatewayAmount === 0) {
                document.getElementById('payment-button-text').textContent = '✓ پرداخت کامل از کیف پول';
            } else if (walletAmount > 0) {
                document.getElementById('payment-button-text').textContent = 'پرداخت ترکیبی (کیف پول + درگاه)';
            }
            paymentForm.action = '{{ route("payment.wallet", $booking) }}';
        }

        @if($booking->discount_code)
        document.getElementById('discount-summary').classList.remove('hidden');
        document.getElementById('discount-amount').textContent = '- ' + Number({{ $booking->discount_amount }}).toLocaleString('fa-IR') + ' تومان';
        bookingAmount = {{ $booking->prepayment_amount }};
        @endif
    </script>
@endsection
