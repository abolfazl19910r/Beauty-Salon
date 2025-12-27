@extends('layouts.app')

@section('title', 'شارژ کیف پول')

@section('content')
    <div class="max-w-2xl mx-auto fade-in">
        <div class="bg-white rounded-xl shadow-lg p-6 hover-shadow">
            <div class="border-b pb-4 mb-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-gradient-to-r from-green-500 to-emerald-600 rounded-full flex items-center justify-center ml-3">
                            <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">شارژ کیف پول</h1>
                            <p class="text-gray-600 text-sm">افزایش موجودی کیف پول شما</p>
                        </div>
                    </div>
                    <a href="{{ route('wallet.index') }}"
                       class="text-gray-500 hover:text-gray-700 transition">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </a>
                </div>
            </div>

            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border-2 border-blue-200 rounded-xl p-5 mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-blue-700 mb-1">موجودی فعلی کیف پول</p>
                        <p class="text-3xl font-bold text-blue-900 persian-number">{{ number_format($wallet->balance) }}</p>
                        <p class="text-sm text-blue-600 mt-1">تومان</p>
                    </div>
                    <div class="w-16 h-16 bg-blue-200 rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-blue-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                    </div>
                </div>
            </div>

            <form action="{{ route('wallet.charge.process') }}" method="POST" id="charge-form">
                @csrf

                <div class="mb-6">
                    <label class="block text-gray-700 font-semibold mb-3">انتخاب سریع مبلغ</label>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                        @foreach($suggestedAmounts as $suggested)
                            <button type="button"
                                    onclick="selectAmount({{ $suggested }})"
                                    class="amount-btn p-4 border-2 border-gray-300 rounded-xl hover:border-green-500 hover:bg-green-50 transition-all text-center group">
                                <div class="text-xl font-bold text-gray-700 group-hover:text-green-600 persian-number">
                                    {{ number_format($suggested) }}
                                </div>
                                <div class="text-xs text-gray-500 mt-1">تومان</div>
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="mb-6">
                    <label class="block text-gray-700 font-semibold mb-2">یا مبلغ دلخواه خود را وارد کنید</label>
                    <div class="relative">
                        <input type="text"
                               name="amount"
                               id="amount-input"
                               placeholder="مثال: 100000"
                               class="w-full px-4 py-4 border-2 @error('amount') border-red-500 @else border-gray-300 @enderror rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent text-lg text-center persian-number"
                               value="{{ old('amount') }}"
                               oninput="formatAmountInput(this)">
                        <div class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    @error('amount')
                    <div class="mt-2 bg-red-50 border border-red-200 rounded-lg p-3 flex items-start">
                        <svg class="w-5 h-5 text-red-600 ml-2 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-red-600 text-sm">{{ $message }}</p>
                    </div>
                    @enderror
                    <p class="text-gray-500 text-sm mt-2 flex items-center">
                        <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        حداقل: <span class="font-semibold persian-number">10,000</span> تومان | حداکثر: <span class="font-semibold persian-number">50,000,000</span> تومان
                    </p>
                </div>

                <div id="preview-box" class="hidden mb-6 bg-gradient-to-r from-green-50 to-emerald-50 border-2 border-green-200 rounded-xl p-5">
                    <h3 class="font-bold text-green-800 mb-3 flex items-center">
                        <svg class="w-5 h-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        پیش‌نمایش شارژ
                    </h3>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center border-b border-green-200 pb-2">
                            <span class="text-green-700">مبلغ شارژ:</span>
                            <span class="font-bold text-green-900 persian-number" id="preview-amount">0 تومان</span>
                        </div>
                        <div class="flex justify-between items-center border-b border-green-200 pb-2">
                            <span class="text-green-700">موجودی فعلی:</span>
                            <span class="persian-number">{{ number_format($wallet->balance) }} تومان</span>
                        </div>
                        <div class="flex justify-between items-center font-bold text-lg pt-2">
                            <span class="text-green-800">موجودی جدید:</span>
                            <span class="text-green-900 persian-number" id="preview-new-balance">{{ number_format($wallet->balance) }} تومان</span>
                        </div>
                    </div>
                </div>

                <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 mb-6">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-yellow-600 ml-2 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <div class="text-sm text-yellow-800">
                            <p class="font-semibold mb-1">نکات مهم:</p>
                            <ul class="list-disc list-inside space-y-1 text-yellow-700">
                                <li>پس از پرداخت، موجودی بلافاصله به کیف پول شما اضافه می‌شود</li>
                                <li>از موجودی کیف پول می‌توانید برای پرداخت نوبت‌های بعدی استفاده کنید</li>
                                <li>پرداخت از طریق درگاه امن زرین‌پال انجام می‌شود</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="flex gap-3">
                    <a href="{{ route('wallet.index') }}"
                       class="flex-1 bg-gray-200 text-gray-700 py-3 px-4 rounded-xl hover:bg-gray-300 transition text-center font-semibold">
                        انصراف
                    </a>
                    <button type="submit"
                            id="submit-btn"
                            disabled
                            class="flex-1 bg-gradient-to-r from-green-500 to-emerald-600 text-white py-3 px-4 rounded-xl hover:opacity-90 transition font-semibold disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center">
                        <svg class="w-5 h-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        پرداخت و شارژ کیف پول
                    </button>
                </div>
            </form>

            <div class="mt-6 pt-6 border-t">
                <div class="flex items-start text-sm text-gray-600">
                    <svg class="w-5 h-5 text-green-500 ml-2 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    <p>
                        پرداخت شما از طریق درگاه امن زرین‌پال انجام می‌شود و اطلاعات کارت بانکی شما ذخیره نمی‌گردد.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        const currentBalance = {{ $wallet->balance }};
        const MIN_AMOUNT = 10000;
        const MAX_AMOUNT = 50000000;

        function persianToEnglish(str) {
            const persianNumbers = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
            const arabicNumbers  = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];

            let result = str;
            for (let i = 0; i < 10; i++) {
                const regex = new RegExp(persianNumbers[i], 'g');
                result = result.replace(regex, i.toString());
            }

            for (let i = 0; i < 10; i++) {
                const regex = new RegExp(arabicNumbers[i], 'g');
                result = result.replace(regex, i.toString());
            }

            return result;
        }

        function englishToPersian(str) {
            const persianNumbers = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
            return str.replace(/\d/g, x => persianNumbers[parseInt(x)]);
        }

        function selectAmount(amount) {
            const input = document.getElementById('amount-input');
            input.value = englishToPersian(amount.toLocaleString('en-US'));
            input.setAttribute('data-value', amount);
            updatePreview(amount);
            validateAmount(amount);
            document.querySelectorAll('.amount-btn').forEach(btn => {
                btn.classList.remove('border-green-500', 'bg-green-50');
                btn.classList.add('border-gray-300');
            });
            event.target.closest('.amount-btn').classList.add('border-green-500', 'bg-green-50');
            event.target.closest('.amount-btn').classList.remove('border-gray-300');
        }

        function formatAmountInput(input) {
            let value = persianToEnglish(input.value);
            value = value.replace(/[^\d]/g, '');

            if (value) {
                const numericValue = parseInt(value);
                input.value = englishToPersian(numericValue.toLocaleString('en-US'));
                input.setAttribute('data-value', numericValue);
                updatePreview(numericValue);
                validateAmount(numericValue);
            } else {
                input.value = '';
                input.removeAttribute('data-value');
                hidePreview();
                disableSubmit();
            }
        }

        function updatePreview(amount) {
            const previewBox = document.getElementById('preview-box');
            const previewAmount = document.getElementById('preview-amount');
            const previewNewBalance = document.getElementById('preview-new-balance');

            if (amount >= MIN_AMOUNT && amount <= MAX_AMOUNT) {
                previewBox.classList.remove('hidden');
                previewAmount.textContent = englishToPersian(amount.toLocaleString('en-US')) + ' تومان';
                previewNewBalance.textContent = englishToPersian((currentBalance + amount).toLocaleString('en-US')) + ' تومان';
            } else {
                hidePreview();
            }
        }

        function hidePreview() {
            document.getElementById('preview-box').classList.add('hidden');
        }

        function validateAmount(amount) {
            if (amount >= MIN_AMOUNT && amount <= MAX_AMOUNT) {
                enableSubmit();
            } else {
                disableSubmit();
            }
        }

        function enableSubmit() {
            const submitBtn = document.getElementById('submit-btn');
            submitBtn.disabled = false;
        }

        function disableSubmit() {
            const submitBtn = document.getElementById('submit-btn');
            submitBtn.disabled = true;
        }

        document.getElementById('charge-form').addEventListener('submit', function(e) {
            e.preventDefault();

            const input = document.getElementById('amount-input');
            const realValue = input.getAttribute('data-value');

            if (!realValue) {
                alert('لطفاً مبلغ را وارد کنید');
                return false;
            }

            input.name = '';
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'amount';
            hiddenInput.value = realValue;
            this.appendChild(hiddenInput);
            this.submit();
        });
    </script>
@endsection
