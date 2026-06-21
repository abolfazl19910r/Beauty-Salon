@extends('layouts.specialist')

@section('title', 'درخواست برداشت')

@section('content')
    <div class="fade-in max-w-3xl mx-auto space-y-6">
        <div class="specialist-card p-6">
            <h2 class="text-xl font-bold text-[var(--specialist-text)] font-serif-fa mb-6">درخواست برداشت وجه</h2>

            <div class="specialist-cta rounded-lg p-6 mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm opacity-80 mb-1">موجودی قابل برداشت</p>
                        <p class="text-3xl font-bold persian-number">{{ number_format($wallet->balance) }}</p>
                        <p class="text-xs opacity-70 mt-1">تومان</p>
                    </div>
                    <div class="bg-white/15 rounded-full p-4">
                        <svg class="w-9 h-9" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <form action="{{ route('specialist.wallet.store-withdrawal') }}" method="POST" id="withdrawal-form">
                @csrf

                <div class="mb-6">
                    <label for="amount" class="block text-xs text-[var(--specialist-plum-muted)] mb-2">
                        مبلغ درخواستی (تومان)
                    </label>
                    <input
                        type="number"
                        name="amount"
                        id="amount"
                        class="w-full px-4 py-3 rounded-lg persian-number text-[var(--specialist-text)] focus:outline-none focus:ring-2 focus:ring-[var(--specialist-plum-mid)]"
                        style="background-color: var(--specialist-bg); border: 1px solid var(--specialist-border);"
                        value="{{ old('amount') }}"
                        min="{{ $settings->minimum_withdrawal_amount }}"
                        max="{{ min($wallet->balance, $settings->maximum_withdrawal_amount) }}"
                        step="1000"
                        required
                    >
                    @error('amount')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-[var(--specialist-text-dim)] mt-1 persian-number">
                        حداقل: {{ number_format($settings->minimum_withdrawal_amount) }} تومان
                        | حداکثر: {{ number_format(min($wallet->balance, $settings->maximum_withdrawal_amount)) }} تومان
                    </p>
                </div>

                <div class="mb-6">
                    <label class="block text-xs text-[var(--specialist-plum-muted)] mb-3">روش برداشت</label>

                    <div class="space-y-3">
                        <label class="block cursor-pointer">
                            <input type="radio" name="method" value="iban" class="peer hidden" checked required>
                            <div class="p-4 rounded-lg transition-colors peer-checked:border-[var(--specialist-plum-mid)]"
                                 style="border: 2px solid var(--specialist-border); background-color: var(--specialist-bg);">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="font-semibold text-[var(--specialist-text)]">انتقال به شبا</span>
                                    <span class="text-xs px-2 py-1 rounded-full bg-sky-400/10 text-sky-300">پیشنهادی</span>
                                </div>
                                <p class="text-sm text-[var(--specialist-text-dim)] mb-1">انتقال به حساب بانکی شما</p>
                                <p class="text-xs text-[var(--specialist-plum-muted)] persian-number">کارمزد: {{ $settings->withdrawal_fee_percentage }}٪</p>
                                <p class="text-xs text-[var(--specialist-plum-muted)]">مدت زمان: 2-3 روز کاری</p>
                            </div>
                        </label>
                        @if($settings->instant_withdrawal_enabled)
                            <label class="block cursor-pointer">
                                <input type="radio" name="method" value="instant" class="peer hidden">
                                <div class="p-4 rounded-lg transition-colors peer-checked:border-[var(--specialist-plum-mid)]"
                                     style="border: 2px solid var(--specialist-border); background-color: var(--specialist-bg);">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="font-semibold text-[var(--specialist-text)]">برداشت فوری</span>
                                        <span class="text-xs px-2 py-1 rounded-full bg-amber-400/10 text-amber-300">سریع</span>
                                    </div>
                                    <p class="text-sm text-[var(--specialist-text-dim)] mb-1">واریز فوری به حساب شما</p>
                                    <p class="text-xs text-[var(--specialist-plum-muted)] persian-number">کارمزد ثابت: {{ number_format($settings->instant_withdrawal_fee) }} تومان</p>
                                    <p class="text-xs text-[var(--specialist-plum-muted)]">مدت زمان: کمتر از 1 ساعت</p>
                                </div>
                            </label>
                        @endif
                    </div>
                </div>

                <div class="mb-6 p-4 rounded-lg" style="background-color: var(--specialist-bg); border: 1px solid var(--specialist-border);">
                    <h4 class="text-sm font-semibold text-[var(--specialist-plum-light)] mb-3">واریز به حساب:</h4>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-[var(--specialist-text-dim)]">شماره شبا:</span>
                            <span class="font-semibold persian-number font-mono text-[var(--specialist-text)]" dir="ltr">{{ $wallet->formatted_iban ?? $wallet->iban }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-[var(--specialist-text-dim)]">نام صاحب حساب:</span>
                            <span class="font-semibold text-[var(--specialist-text)]">{{ $wallet->account_holder_name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-[var(--specialist-text-dim)]">بانک:</span>
                            <span class="font-semibold text-[var(--specialist-text)]">{{ $wallet->bank_name ?? 'ثبت نشده' }}</span>
                        </div>
                    </div>
                    <div class="mt-3 pt-3 border-t" style="border-color: var(--specialist-border);">
                        <a href="{{ route('specialist.wallet.edit-iban') }}" class="text-[var(--specialist-plum-mid)] hover:text-[var(--specialist-plum-light)] text-sm">
                            ویرایش اطلاعات حساب
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </a>
                    </div>
                </div>

                <div id="fee-summary" class="mb-6 p-4 rounded-lg hidden" style="background-color: rgba(216, 174, 224, 0.08); border: 1px solid var(--specialist-border);">
                    <h4 class="text-sm font-semibold text-[var(--specialist-plum-light)] mb-3">خلاصه محاسبات:</h4>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-[var(--specialist-text-dim)]">مبلغ درخواستی:</span>
                            <span id="gross-amount" class="font-semibold text-[var(--specialist-text)] persian-number">0</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-[var(--specialist-text-dim)]">کارمزد برداشت:</span>
                            <span id="fee-amount" class="font-semibold text-red-300 persian-number">0</span>
                        </div>
                        <div class="flex justify-between pt-2 border-t" style="border-color: var(--specialist-border);">
                            <span class="text-[var(--specialist-text)] font-semibold">مبلغ قابل واریز:</span>
                            <span id="net-amount" class="font-bold text-emerald-300 text-lg persian-number">0</span>
                        </div>
                    </div>
                </div>

                <div class="mb-6 rounded-lg p-4" style="background-color: rgba(251, 191, 36, 0.07); border: 1px solid var(--specialist-border);">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-amber-400 ml-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <div class="text-sm text-[var(--specialist-text-dim)]">
                            <p class="font-semibold text-amber-300 mb-1">نکات مهم:</p>
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
                    <button type="submit" class="specialist-cta flex-1 font-bold py-3 px-6 rounded-lg transition-opacity hover:opacity-90 flex items-center justify-center">
                        <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        ثبت درخواست برداشت
                    </button>
                    <a href="{{ route('specialist.wallet.index') }}"
                       class="px-6 py-3 rounded-lg font-semibold transition-colors text-[var(--specialist-text-dim)] hover:bg-white/5 hover:text-[var(--specialist-text)]"
                       style="border: 1px solid var(--specialist-border);">
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
