@extends('layouts.app')

@section('title', 'پرداخت نوبت')

@section('content')
    <div class="max-w-2xl mx-auto fade-in">
        <div class="bg-white rounded-lg shadow-lg p-6 hover-shadow">
            <div class="border-b pb-4 mb-6 flex items-center">
                <svg class="w-6 h-6 ml-2 text-pink-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                    <line x1="1" y1="10" x2="23" y2="10"></line>
                </svg>
                <div>
                    <h1 class="text-2xl font-bold">پرداخت نوبت</h1>
                    <p class="text-gray-500 persian-number">شماره نوبت: {{ $booking->id }}</p>
                </div>
            </div>

            <div class="mb-6">
                <h2 class="text-lg font-bold mb-4 flex items-center">
                    <svg class="w-5 h-5 ml-1 text-pink-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    جزئیات نوبت
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-gray-50 p-4 rounded-lg">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 ml-2 text-gray-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M18 18l2-1v-2.5" />
                        </svg>
                        <div>
                            <span class="text-gray-600 block mb-1">خدمت:</span>
                            <span class="font-medium">{{ $booking->service ? $booking->service->name : 'خدمت نامشخص' }}</span>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <svg class="w-5 h-5 ml-2 text-gray-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <div>
                            <span class="text-gray-600 block mb-1">متخصص:</span>
                            <span class="font-medium">{{ $booking->specialist ? $booking->specialist->name : 'متخصص نامشخص' }}</span>
                        </div>
                    </div>
                    <div class="flex items-start md:col-span-2">
                        <svg class="w-5 h-5 ml-2 text-gray-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <div>
                            <span class="text-gray-600 block mb-1">تاریخ و ساعت:</span>
                            <span class="font-medium persian-number">{{ verta($booking->booking_time)->format('Y/m/d H:i') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mb-6 bg-gradient-to-r from-purple-50 to-pink-50 border-2 border-purple-200 rounded-xl p-5">
                <h3 class="font-bold mb-3 flex items-center text-purple-700">
                    <svg class="w-5 h-5 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                    </svg>
                    کد تخفیف دارید؟
                </h3>
                <div class="flex gap-2">
                    <input type="text"
                           id="discount-code"
                           placeholder="کد تخفیف را وارد کنید"
                           class="flex-1 px-4 py-3 border-2 border-purple-200 rounded-lg focus:border-purple-500 focus:outline-none transition"
                           @if($booking->discount_code) value="{{ $booking->discount_code }}" disabled @endif>
                    <button onclick="applyDiscount()"
                            id="apply-discount-btn"
                            @if($booking->discount_code) disabled @endif
                            class="bg-gradient-to-r from-purple-500 to-pink-600 text-white px-6 py-3 rounded-lg font-bold hover:opacity-90 transition flex items-center disabled:opacity-50 disabled:cursor-not-allowed whitespace-nowrap">
                        <svg class="w-5 h-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        اعمال
                    </button>
                </div>
                <div id="discount-message" class="mt-3 hidden"></div>

                @if(!$booking->discount_code)
                    <div class="mt-3 text-sm text-purple-600">
                        💡 نکته: از پنل امتیازات می‌توانید امتیازات خود را به کد تخفیف تبدیل کنید!
                        <a href="{{ route('loyalty.index') }}" class="underline font-bold">مشاهده امتیازات</a>
                    </div>
                @endif
            </div>

            @if($wallet->balance > 0)
                <div class="mb-6 bg-gradient-to-r from-green-50 to-emerald-50 border-2 border-green-200 rounded-xl p-5">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center ml-3">
                                <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-800 text-lg">موجودی کیف پول شما</h3>
                                <p class="text-2xl font-bold text-green-600 persian-number">{{ number_format($wallet->balance) }} تومان</p>
                            </div>
                        </div>

                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" id="use-wallet-toggle" class="sr-only peer" onchange="toggleWalletPayment()">
                            <div class="w-14 h-7 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:right-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-green-500"></div>
                            <span class="mr-3 text-sm font-medium text-gray-700">استفاده از کیف پول</span>
                        </label>
                    </div>

                    <div id="wallet-options" class="hidden mt-4 space-y-3 border-t border-green-200 pt-4">
                        <label class="flex items-center p-3 bg-white rounded-lg cursor-pointer hover:bg-green-50 transition border-2 border-transparent hover:border-green-300">
                            <input type="radio" name="wallet_option" value="full" class="w-5 h-5 text-green-600" onchange="updatePaymentAmount()"
                                {{ $wallet->balance >= $booking->prepayment_amount ? '' : 'disabled' }}>
                            <div class="mr-3 flex-1">
                                <div class="font-semibold text-gray-800">پرداخت کامل از کیف پول</div>
                                <div class="text-sm text-gray-600">
                                    @if($wallet->balance >= $booking->prepayment_amount)
                                        <span class="text-green-600">✓ موجودی شما کافی است</span>
                                    @else
                                        <span class="text-red-600">✗ موجودی کافی نیست (کمبود: {{ number_format($booking->prepayment_amount - $wallet->balance) }} تومان)</span>
                                    @endif
                                </div>
                            </div>
                            <div class="text-left">
                                <div class="font-bold text-green-600 persian-number">{{ number_format(min($wallet->balance, $booking->prepayment_amount)) }}</div>
                                <div class="text-xs text-gray-500">تومان</div>
                            </div>
                        </label>

                        @if($wallet->balance < $booking->prepayment_amount)
                            <label class="flex items-center p-3 bg-white rounded-lg cursor-pointer hover:bg-blue-50 transition border-2 border-transparent hover:border-blue-300">
                                <input type="radio" name="wallet_option" value="partial" class="w-5 h-5 text-blue-600" onchange="updatePaymentAmount()">
                                <div class="mr-3 flex-1">
                                    <div class="font-semibold text-gray-800">پرداخت ترکیبی</div>
                                    <div class="text-sm text-gray-600">
                                        <span class="text-blue-600">{{ number_format($wallet->balance) }} تومان از کیف پول + {{ number_format($booking->prepayment_amount - $wallet->balance) }} تومان از درگاه</span>
                                    </div>
                                </div>
                                <div class="text-left">
                                    <div class="font-bold text-blue-600 persian-number">{{ number_format($wallet->balance) }}</div>
                                    <div class="text-xs text-gray-500">از کیف پول</div>
                                </div>
                            </label>
                        @endif
                    </div>
                </div>
            @endif

            <div class="bg-blue-50 border border-blue-200 p-5 rounded-lg mb-6">
                <h3 class="font-bold mb-4 flex items-center text-blue-700">
                    <svg class="w-5 h-5 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    خلاصه پرداخت
                </h3>
                <div class="space-y-3">
                    @if($booking->service)
                        <div class="flex justify-between items-center border-b border-blue-100 pb-2">
                            <span class="text-blue-700">مبلغ کل خدمت:</span>
                            <span class="persian-number">{{ number_format($booking->service->price) }} تومان</span>
                        </div>
                    @endif
                    <div class="flex justify-between items-center border-b border-blue-100 pb-2">
                        <span class="text-blue-700">مبلغ پیش پرداخت:</span>
                        <span class="font-bold persian-number" id="original-amount">{{ number_format($booking->prepayment_amount) }} تومان</span>
                    </div>

                    @if($booking->discount_amount > 0)
                        <div class="flex justify-between items-center text-green-600 border-b border-blue-100 pb-2">
                            <span>تخفیف اعمال شده ({{ $booking->discount_code }}):</span>
                            <span class="persian-number">- {{ number_format($booking->discount_amount) }} تومان</span>
                        </div>
                    @endif

                    <div id="discount-summary" class="hidden">
                        <div class="flex justify-between items-center text-green-600 border-b border-blue-100 pb-2">
                            <span>تخفیف:</span>
                            <span class="font-semibold persian-number" id="discount-amount">0 تومان</span>
                        </div>
                    </div>

                    <div id="wallet-payment-summary" class="hidden">
                        <div class="flex justify-between items-center text-green-600 border-b border-blue-100 pb-2">
                            <span>پرداخت از کیف پول:</span>
                            <span class="font-semibold persian-number" id="wallet-amount">0 تومان</span>
                        </div>
                        <div class="flex justify-between items-center text-purple-600 border-b border-blue-100 pb-2">
                            <span>پرداخت از درگاه:</span>
                            <span class="font-semibold persian-number" id="gateway-amount">{{ number_format($booking->prepayment_amount) }} تومان</span>
                        </div>
                    </div>

                    <div class="flex justify-between items-center text-lg font-bold pt-2 border-t-2 border-blue-300">
                        <span class="text-blue-700">مبلغ قابل پرداخت:</span>
                        <span class="text-blue-700 persian-number" id="final-amount">{{ number_format($booking->prepayment_amount) }} تومان</span>
                    </div>
                </div>
            </div>

            <form id="payment-form" method="POST" action="{{ route('payment.process', $booking) }}">
                @csrf
                <input type="hidden" name="use_wallet" id="use_wallet" value="0">
                <input type="hidden" name="wallet_amount" id="wallet_amount" value="0">

                <button type="submit" class="w-full bg-gradient-to-r from-pink-500 to-purple-600 text-white py-3 rounded-lg font-bold hover:opacity-90 transition-opacity flex items-center justify-center">
                    <svg class="w-5 h-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                    <span id="payment-button-text">پرداخت و تایید نوبت</span>
                </button>
            </form>

            <div class="mt-4 text-center">
                <a href="{{ route('bookings.index') }}" class="text-gray-600 hover:text-pink-600 inline-flex items-center transition-colors">
                    <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    انصراف و بازگشت به لیست نوبت‌ها
                </a>
            </div>

            <div class="mt-6 pt-6 border-t">
                <div class="flex items-start text-sm text-gray-600">
                    <svg class="w-5 h-5 text-green-500 ml-2 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    <p>پرداخت شما از طریق درگاه امن زرین‌پال انجام می‌شود و اطلاعات کارت بانکی شما ذخیره نمی‌گردد.</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        const bookingId = {{ $booking->id }};
        let bookingAmount = {{ $booking->prepayment_amount }};
        const walletBalance = {{ $wallet->balance }};
        let currentDiscount = {{ $booking->discount_amount ?? 0 }};
        const baseAmount = {{ 50000 }};

        function applyDiscount() {
            const codeInput = document.getElementById('discount_code');
            const messageDiv = document.getElementById('discount-message');
            const applyBtn = document.getElementById('apply-discount-btn');
            const code = codeInput.value.trim();

            messageDiv.classList.add('hidden');
            messageDiv.className = 'text-sm mt-2 hidden';

            if (!code) {
                showMessage(messageDiv, 'لطفاً کد تخفیف را وارد کنید.', 'error');
                return;
            }
            const originalBtnText = applyBtn.innerHTML;
            applyBtn.disabled = true;
            applyBtn.innerHTML = '<span class="animate-pulse">⏳...</span>';

            fetch('{{ route("bookings.check-discount") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    code: code,
                    booking_id: {{ $booking->id }}
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.valid) {
                        currentDiscount = data.discount_amount;
                        bookingAmount = data.final_price;

                        const discountSummary = document.getElementById('discount-summary');
                        const discountAmountEl = document.getElementById('discount-amount');

                        if (discountSummary) discountSummary.classList.remove('hidden');
                        if (discountAmountEl) {
                            discountAmountEl.textContent = '- ' + new Intl.NumberFormat('fa-IR').format(currentDiscount) + ' تومان';
                        }

                        showMessage(messageDiv, '✔ ' + data.message, 'success');
                        const paymentBtnText = document.getElementById('payment-button-text');
                        const walletOptions = document.getElementById('wallet-options');
                        const walletToggle = document.getElementById('use-wallet-toggle');

                        if (bookingAmount === 0) {
                            if (paymentBtnText) paymentBtnText.textContent = 'تایید نهایی نوبت (رایگان)';
                            if (walletOptions) walletOptions.classList.add('hidden');
                            if (walletToggle) {
                                walletToggle.checked = false;
                                walletToggle.disabled = true;
                            }
                            document.getElementById('payment-form').action = '{{ route("payment.process", $booking) }}';

                        } else {
                            if (paymentBtnText) paymentBtnText.textContent = 'پرداخت و نهایی کردن رزرو';
                            if (walletOptions) walletOptions.classList.remove('hidden');
                            if (walletToggle) walletToggle.disabled = false;
                        }
                        updatePaymentAmount();

                    } else {
                        showMessage(messageDiv, '❌ ' + (data.message || 'کد تخفیف نامعتبر است.'), 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showMessage(messageDiv, 'خطا در ارتباط با سرور.', 'error');
                })
                .finally(() => {
                    applyBtn.disabled = false;
                    applyBtn.innerHTML = originalBtnText;
                });
        }

        function showMessage(element, text, type) {
            element.textContent = text;
            element.classList.remove('hidden', 'text-green-600', 'text-red-600');

            if (type === 'success') {
                element.classList.add('text-green-600');
            } else {
                element.classList.add('text-red-600');
            }
        }

        function toggleWalletPayment() {
            const toggle = document.getElementById('use-wallet-toggle');
            const walletOptions = document.getElementById('wallet-options');
            const paymentForm = document.getElementById('payment-form');

            if (toggle.checked) {
                walletOptions.classList.remove('hidden');
                if (walletBalance >= bookingAmount) {
                    document.querySelector('input[name="wallet_option"][value="full"]').checked = true;
                } else {
                    document.querySelector('input[name="wallet_option"][value="partial"]').checked = true;
                }
                paymentForm.action = '{{ route("payment.wallet", $booking) }}';
                updatePaymentAmount();
            } else {
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
            const toggle = document.getElementById('use-wallet-toggle');
            const paymentForm = document.getElementById('payment-form');
            if (!toggle.checked) {
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
                document.getElementById('payment-button-text').textContent = '✔ پرداخت کامل از کیف پول';
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
