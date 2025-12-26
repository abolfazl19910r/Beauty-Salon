@extends('layouts.specialist')

@section('title', 'درخواست برداشت')

@section('content')
    <div class="max-w-3xl mx-auto">
        <div class="bg-white rounded-xl shadow-md p-6 mb-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">درخواست برداشت وجه</h2>

            <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-6 mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-green-700 mb-1">موجودی قابل برداشت</p>
                        <p class="text-3xl font-bold text-green-800 persian-number">{{ number_format($wallet->balance) }}</p>
                        <p class="text-xs text-green-600 mt-1">تومان</p>
                    </div>
                    <div class="bg-white rounded-full p-4">
                        <svg class="w-10 h-10 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <form action="{{ route('specialist.wallet.store-withdrawal') }}" method="POST" id="withdrawal-form">
                @csrf

                <div class="mb-6">
                    <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">
                        مبلغ درخواستی (تومان)
                    </label>
                    <input
                        type="number"
                        name="amount"
                        id="amount"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent persian-number"
                        value="{{ old('amount') }}"
                        min="{{ $settings->minimum_withdrawal_amount }}"
                        max="{{ min($wallet->balance, $settings->maximum_withdrawal_amount) }}"
                        step="1000"
                        required
                    >
                    @error('amount')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-gray-500 mt-1 persian-number">
                        حداقل: {{ number_format($settings->minimum_withdrawal_amount) }} تومان
                        | حداکثر: {{ number_format(min($wallet->balance, $settings->maximum_withdrawal_amount)) }} تومان
                    </p>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-3">روش برداشت</label>

                    <div class="space-y-3">
                        <label class="flex items-start p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-pink-300 transition-colors">
                            <input
                                type="radio"
                                name="method"
                                value="iban"
                                class="mt-1 ml-3 text-pink-600 focus:ring-pink-500"
                                checked
                                required
                            >
                            <div class="flex-1">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="font-semibold text-gray-800">انتقال به شبا</span>
                                    <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">پیشنهادی</span>
                                </div>
                                <p class="text-sm text-gray-600 mb-1">انتقال به حساب بانکی شما</p>
                                <p class="text-xs text-gray-500 persian-number">کارمزد: {{ $settings->withdrawal_fee_percentage }}٪</p>
                                <p class="text-xs text-gray-500">مدت زمان: 2-3 روز کاری</p>
                            </div>
                        </label>
                        @if($settings->instant_withdrawal_enabled)
                            <label class="flex items-start p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-pink-300 transition-colors">
                                <input
                                    type="radio"
                                    name="method"
                                    value="instant"
                                    class="mt-1 ml-3 text-pink-600 focus:ring-pink-500"
                                >
                                <div class="flex-1">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="font-semibold text-gray-800">برداشت فوری</span>
                                        <span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded">سریع</span>
                                    </div>
                                    <p class="text-sm text-gray-600 mb-1">واریز فوری به حساب شما</p>
                                    <p class="text-xs text-gray-500 persian-number">کارمزد ثابت: {{ number_format($settings->instant_withdrawal_fee) }} تومان</p>
                                    <p class="text-xs text-gray-500">مدت زمان: کمتر از 1 ساعت</p>
                                </div>
                            </label>
                        @endif
                    </div>
                </div>

                <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                    <h4 class="text-sm font-semibold text-gray-700 mb-3">واریز به حساب:</h4>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">شماره شبا:</span>
                            <span class="font-semibold persian-number font-mono">{{ $wallet->iban }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">نام صاحب حساب:</span>
                            <span class="font-semibold">{{ $wallet->account_holder_name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">بانک:</span>
                            <span class="font-semibold">{{ $wallet->bank_name ?? 'ثبت نشده' }}</span>
                        </div>
                    </div>
                    <div class="mt-3 pt-3 border-t border-gray-200">
                        <a href="{{ route('specialist.wallet.edit-iban') }}" class="text-pink-600 hover:text-pink-700 text-sm">
                            ویرایش اطلاعات حساب
                            <svg class="w-4 h-4 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </a>
                    </div>
                </div>

                <div id="fee-summary" class="mb-6 p-4 bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg hidden">
                    <h4 class="text-sm font-semibold text-blue-800 mb-3">خلاصه محاسبات:</h4>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-blue-700">مبلغ درخواستی:</span>
                            <span id="gross-amount" class="font-semibold text-blue-900 persian-number">0</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-blue-700">کارمزد برداشت:</span>
                            <span id="fee-amount" class="font-semibold text-red-600 persian-number">0</span>
                        </div>
                        <div class="flex justify-between pt-2 border-t border-blue-200">
                            <span class="text-blue-800 font-semibold">مبلغ قابل واریز:</span>
                            <span id="net-amount" class="font-bold text-green-700 text-lg persian-number">0</span>
                        </div>
                    </div>
                </div>

                <div class="mb-6 bg-yellow-50 border-r-4 border-yellow-400 p-4 rounded-lg">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-yellow-600 ml-2 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <div class="text-sm text-yellow-800">
                            <p class="font-semibold mb-1">نکات مهم:</p>
                            <ul class="list-disc list-inside space-y-1 mr-2 text-xs">
                                <li>پس از ثبت درخواست، مبلغ از موجودی شما کسر می‌شود</li>
                                <li>درخواست‌های برداشت توسط تیم پشتیبانی بررسی و پردازش می‌شوند</li>
                                <li>در صورت رد درخواست، مبلغ به کیف پول شما بازگردانده می‌شود</li>
                                <li>لطفاً از صحت اطلاعات حساب بانکی خود اطمینان حاصل کنید</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="flex gap-3">
                    <button
                        type="submit"
                        class="flex-1 bg-pink-500 hover:bg-pink-600 text-white font-semibold py-3 px-6 rounded-lg transition-colors flex items-center justify-center"
                    >
                        <svg class="w-5 h-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        ثبت درخواست برداشت
                    </button>
                    <a
                        href="{{ route('specialist.wallet.index') }}"
                        class="px-6 py-3 border-2 border-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-50 transition-colors"
                    >
                        انصراف
                    </a>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const amountInput = document.getElementById('amount');
                const methodInputs = document.querySelectorAll('input[name="method"]');
                const feeSummary = document.getElementById('fee-summary');
                const grossAmountSpan = document.getElementById('gross-amount');
                const feeAmountSpan = document.getElementById('fee-amount');
                const netAmountSpan = document.getElementById('net-amount');

                function calculateFee() {
                    const amount = parseFloat(amountInput.value) || 0;
                    const method = document.querySelector('input[name="method"]:checked')?.value || 'iban';

                    if (amount > 0) {
                        fetch('{{ route("specialist.wallet.calculate-fee") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ amount, method })
                        })
                            .then(response => response.json())
                            .then(data => {
                                grossAmountSpan.textContent = data.gross_amount + ' تومان';
                                feeAmountSpan.textContent = data.fee + ' تومان';
                                netAmountSpan.textContent = data.net_amount + ' تومان';
                                feeSummary.classList.remove('hidden');
                            })
                            .catch(error => {
                                console.error('خطا در محاسبه کارمزد:', error);
                            });
                    } else {
                        feeSummary.classList.add('hidden');
                    }
                }

                amountInput.addEventListener('input', calculateFee);
                methodInputs.forEach(input => {
                    input.addEventListener('change', calculateFee);
                });

                if (amountInput.value) {
                    calculateFee();
                }
            });
        </script>
    @endpush
@endsection
