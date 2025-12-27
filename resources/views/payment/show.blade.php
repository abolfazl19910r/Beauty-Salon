@extends('layouts.app')

@section('title', 'پرداخت نوبت')

@section('content')
    <div class="max-w-2xl mx-auto fade-in">
        <div class="bg-white rounded-lg shadow-lg p-6 hover-shadow">
            <div class="border-b pb-4 mb-6 flex items-center">
                <svg class="w-6 h-6 ml-2 text-pink-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                    <line x1="1" y1="10" x2="23" y2="10"></line>
                </svg>
                <div>
                    <h1 class="text-2xl font-bold">پرداخت نوبت</h1>
                    <p class="text-gray-500 persian-number">شماره نوبت: {{ $booking->id }}</p>
                </div>
            </div>

            <div class="mb-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-yellow-600 mt-0.5 ml-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <div>
                        <p class="text-yellow-800 font-medium mb-1">توجه مهم:</p>
                        <p class="text-yellow-700 text-sm">
                            در صورت عدم پرداخت طی 30 دقیقه، نوبت شما به صورت خودکار لغو خواهد شد.
                        </p>
                    </div>
                </div>
            </div>

            <div class="mb-6">
                <h2 class="text-lg font-bold mb-4 flex items-center">
                    <svg class="w-5 h-5 ml-1 text-pink-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                    جزئیات نوبت
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-gray-50 p-4 rounded-lg">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 ml-2 text-gray-400 mt-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                        </svg>
                        <div>
                            <span class="text-gray-600 block mb-1">خدمت:</span>
                            <span class="font-medium">
                                {{ $booking->service ? $booking->service->name : 'خدمت نامشخص' }}
                            </span>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <svg class="w-5 h-5 ml-2 text-gray-400 mt-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="8.5" cy="7" r="4"></circle>
                            <polyline points="17 11 19 13 23 9"></polyline>
                        </svg>
                        <div>
                            <span class="text-gray-600 block mb-1">متخصص:</span>
                            <span class="font-medium">
                                {{ $booking->specialist ? $booking->specialist->name : 'متخصص نامشخص' }}
                            </span>
                        </div>
                    </div>
                    <div class="flex items-start md:col-span-2">
                        <svg class="w-5 h-5 ml-2 text-gray-400 mt-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        <div>
                            <span class="text-gray-600 block mb-1">تاریخ و ساعت:</span>
                            <span class="font-medium persian-number" dir="ltr">
                                {{ verta($booking->booking_time)->format('Y/m/d H:i') }}
                            </span>
                        </div>
                    </div>
                </div>
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
                    <svg class="w-5 h-5 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="1" x2="12" y2="23"></line>
                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
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
                        <span class="font-bold persian-number">{{ number_format($booking->prepayment_amount) }} تومان</span>
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

                    @if($booking->discount_amount > 0)
                        <div class="flex justify-between items-center text-green-600 text-sm">
                            <span>تخفیف اعمال شده:</span>
                            <span class="persian-number">{{ number_format($booking->discount_amount) }} تومان</span>
                        </div>
                    @endif

                    <div class="flex justify-between items-center text-lg font-bold pt-2 border-t-2 border-blue-300">
                        <span class="text-blue-700">مبلغ قابل پرداخت:</span>
                        <span class="text-blue-700 persian-number" id="final-amount">{{ number_format($booking->prepayment_amount) }} تومان</span>
                    </div>
                </div>
            </div>

            <form id="payment-form" method="POST">
                @csrf
                <input type="hidden" name="use_wallet" id="use_wallet" value="0">
                <input type="hidden" name="wallet_amount" id="wallet_amount" value="0">

                <button type="submit" class="w-full bg-gradient-to-r from-pink-500 to-purple-600 text-white py-3 rounded-lg font-bold hover:opacity-90 transition-opacity flex items-center justify-center">
                    <svg class="w-5 h-5 ml-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                        <line x1="1" y1="10" x2="23" y2="10"></line>
                    </svg>
                    <span id="payment-button-text">پرداخت و تایید نوبت</span>
                </button>
            </form>

            <div class="mt-4 text-center">
                <a href="{{ route('bookings.index') }}" class="text-gray-600 hover:text-pink-600 inline-flex items-center transition-colors">
                    <svg class="w-4 h-4 ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    انصراف و بازگشت به لیست نوبت‌ها
                </a>
            </div>

            <div class="mt-6 pt-6 border-t">
                <div class="flex items-start text-sm text-gray-600">
                    <svg class="w-5 h-5 text-green-500 ml-2 mt-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                    <p>
                        پرداخت شما از طریق درگاه امن زرین‌پال انجام می‌شود و اطلاعات کارت بانکی شما ذخیره نمی‌گردد.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        const bookingAmount = {{ $booking->prepayment_amount }};
        const walletBalance = {{ $wallet->balance }};

        function toggleWalletPayment() {
            const toggle = document.getElementById('use-wallet-toggle');
            const walletOptions = document.getElementById('wallet-options');

            if (toggle.checked) {
                walletOptions.classList.remove('hidden');
                if (walletBalance >= bookingAmount) {
                    document.querySelector('input[name="wallet_option"][value="full"]').checked = true;
                } else {
                    document.querySelector('input[name="wallet_option"][value="partial"]').checked = true;
                }
                updatePaymentAmount();
            } else {
                walletOptions.classList.add('hidden');
                document.getElementById('use_wallet').value = '0';
                document.getElementById('wallet_amount').value = '0';
                document.getElementById('wallet-payment-summary').classList.add('hidden');
                document.getElementById('final-amount').textContent = bookingAmount.toLocaleString('fa-IR') + ' تومان';
                document.getElementById('payment-button-text').textContent = 'پرداخت و تایید نوبت';
                document.getElementById('payment-form').action = '{{ route("payment.process", $booking) }}';
            }
        }

        function updatePaymentAmount() {
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

            document.getElementById('payment-form').action = '{{ route("payment.wallet", $booking) }}';
        }
    </script>
@endsection
