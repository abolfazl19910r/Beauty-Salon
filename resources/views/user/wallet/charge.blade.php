@extends('layouts.app')

@section('title', 'شارژ کیف پول')

@section('content')
    <div class="max-w-2xl mx-auto fade-in">
        <div class="rounded-xl p-6" style="background-color: var(--rasta-brown); border: 1px solid rgba(201,162,75,0.2);">

            {{-- Header --}}
            <div class="flex items-center justify-between mb-6 pb-4" style="border-bottom: 1px solid rgba(201,162,75,0.15);">
                <div class="flex items-center">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center ml-3"
                         style="background: linear-gradient(135deg, var(--rasta-gold), var(--rasta-gold-light));">
                        <svg class="w-6 h-6" style="color: var(--rasta-dark);" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold" style="color: var(--rasta-gold-light);">شارژ کیف پول</h1>
                        <p class="text-sm" style="color: var(--rasta-cream); opacity: 0.6;">افزایش موجودی کیف پول شما</p>
                    </div>
                </div>
                <a href="{{ route('wallet.index') }}" style="color: var(--rasta-cream); opacity: 0.5;"
                   class="hover:opacity-80 transition">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </a>
            </div>

            {{-- Current inventory --}}
            <div class="rounded-xl p-5 mb-6" style="background-color: var(--rasta-dark); border: 1px solid rgba(201,162,75,0.15);">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm mb-1" style="color: var(--rasta-gold);">موجودی فعلی کیف پول</p>
                        <p class="text-3xl font-bold persian-number" style="color: var(--rasta-gold-light);">
                            {{ number_format($wallet->balance) }}
                        </p>
                        <p class="text-sm mt-1" style="color: var(--rasta-cream); opacity: 0.6;">تومان</p>
                    </div>
                    <div class="w-16 h-16 rounded-full flex items-center justify-center"
                         style="background-color: rgba(201,162,75,0.1);">
                        <svg class="w-8 h-8" style="color: var(--rasta-gold);" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                    </div>
                </div>
            </div>

            <form action="{{ route('wallet.charge.process') }}" method="POST" id="charge-form">
                @csrf

                {{-- Quick amount selection --}}
                <div class="mb-6">
                    <label class="block font-semibold mb-3" style="color: var(--rasta-cream);">انتخاب سریع مبلغ</label>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                        @foreach($suggestedAmounts as $suggested)
                            <button type="button"
                                    onclick="selectAmount({{ $suggested }}, this)"
                                    class="amount-btn p-4 rounded-xl transition-all text-center"
                                    style="border: 1px solid rgba(201,162,75,0.3); background-color: rgba(201,162,75,0.05);">
                                <div class="text-xl font-bold persian-number" style="color: var(--rasta-cream);">
                                    {{ number_format($suggested) }}
                                </div>
                                <div class="text-xs mt-1" style="color: var(--rasta-cream); opacity: 0.5;">تومان</div>
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Desired amount --}}
                <div class="mb-6">
                    <label class="block font-semibold mb-2" style="color: var(--rasta-cream);">
                        یا مبلغ دلخواه خود را وارد کنید
                    </label>
                    <div class="relative">
                        <input type="text"
                               name="amount"
                               id="amount-input"
                               placeholder="مثال: ۱۰۰٬۰۰۰"
                               class="w-full px-4 py-4 rounded-xl text-lg text-center persian-number focus:outline-none"
                               style="background-color: var(--rasta-dark);
                                      border: 1px solid {{ $errors->has('amount') ? '#ef4444' : 'rgba(201,162,75,0.3)' }};
                                      color: var(--rasta-cream);"
                               value="{{ old('amount') }}"
                               oninput="formatAmountInput(this)">
                        <div class="absolute left-4 top-1/2 -translate-y-1/2" style="color: var(--rasta-gold); opacity: 0.6;">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    @error('amount')
                    <div class="mt-2 rounded-lg p-3 flex items-start"
                         style="background-color: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3);">
                        <svg class="w-5 h-5 text-red-400 ml-2 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-red-400 text-sm">{{ $message }}</p>
                    </div>
                    @enderror
                    <p class="text-sm mt-2 flex items-center" style="color: var(--rasta-cream); opacity: 0.5;">
                        <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        حداقل: <span class="font-semibold persian-number mx-1">10,000</span> تومان |
                        حداکثر: <span class="font-semibold persian-number mx-1">50,000,000</span> تومان
                    </p>
                </div>

                {{-- Preview --}}
                <div id="preview-box" class="hidden mb-6 rounded-xl p-5"
                     style="background-color: rgba(201,162,75,0.08); border: 1px solid rgba(201,162,75,0.3);">
                    <h3 class="font-bold mb-3 flex items-center" style="color: var(--rasta-gold-light);">
                        <svg class="w-5 h-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        پیش‌نمایش شارژ
                    </h3>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center pb-2"
                             style="border-bottom: 1px solid rgba(201,162,75,0.2);">
                            <span style="color: var(--rasta-cream); opacity: 0.7;">مبلغ شارژ:</span>
                            <span class="font-bold persian-number" id="preview-amount"
                                  style="color: var(--rasta-gold-light);">0 تومان</span>
                        </div>
                        <div class="flex justify-between items-center pb-2"
                             style="border-bottom: 1px solid rgba(201,162,75,0.2);">
                            <span style="color: var(--rasta-cream); opacity: 0.7;">موجودی فعلی:</span>
                            <span class="persian-number" style="color: var(--rasta-cream);">
                                {{ number_format($wallet->balance) }} تومان
                            </span>
                        </div>
                        <div class="flex justify-between items-center font-bold text-lg pt-2">
                            <span style="color: var(--rasta-cream);">موجودی جدید:</span>
                            <span class="persian-number" id="preview-new-balance"
                                  style="color: var(--rasta-gold);">
                                {{ number_format($wallet->balance) }} تومان
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Important points --}}
                <div class="rounded-xl p-4 mb-6"
                     style="background-color: rgba(201,162,75,0.05); border: 1px solid rgba(201,162,75,0.15);">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 ml-2 mt-0.5 flex-shrink-0" style="color: var(--rasta-gold);"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <div class="text-sm" style="color: var(--rasta-cream); opacity: 0.8;">
                            <p class="font-semibold mb-1" style="color: var(--rasta-gold);">نکات مهم:</p>
                            <ul class="list-disc list-inside space-y-1" style="opacity: 0.8;">
                                <li>پس از پرداخت، موجودی بلافاصله به کیف پول شما اضافه می‌شود</li>
                                <li>از موجودی کیف پول می‌توانید برای پرداخت نوبت‌های بعدی استفاده کنید</li>
                                <li>پرداخت از طریق درگاه امن زرین‌پال انجام می‌شود</li>
                            </ul>
                        </div>
                    </div>
                </div>

                {{-- Buttons --}}
                <div class="flex gap-3">
                    <a href="{{ route('wallet.index') }}"
                       class="flex-1 py-3 px-4 rounded-xl transition text-center font-semibold"
                       style="background-color: rgba(201,162,75,0.1);
                              color: var(--rasta-cream);
                              border: 1px solid rgba(201,162,75,0.2);">
                        انصراف
                    </a>
                    <button type="submit"
                            id="submit-btn"
                            disabled
                            class="flex-1 py-3 px-4 rounded-xl transition font-semibold disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center"
                            style="background: linear-gradient(135deg, var(--rasta-gold), var(--rasta-gold-light));
                                   color: var(--rasta-dark);">
                        <svg class="w-5 h-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        پرداخت و شارژ کیف پول
                    </button>
                </div>
            </form>

            {{-- Security Footer --}}
            <div class="mt-6 pt-4 flex items-start text-sm"
                 style="border-top: 1px solid rgba(201,162,75,0.1); color: var(--rasta-cream); opacity: 0.5;">
                <svg class="w-5 h-5 ml-2 mt-0.5 flex-shrink-0" style="color: var(--rasta-gold);"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                <p>پرداخت شما از طریق درگاه امن زرین‌پال انجام می‌شود و اطلاعات کارت بانکی شما ذخیره نمی‌گردد.</p>
            </div>
        </div>
    </div>

    <script>
        const currentBalance = {{ $wallet->balance }};
        const MIN_AMOUNT = 10000;
        const MAX_AMOUNT = 50000000;

        function persianToEnglish(str) {
            const p = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
            const a = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];
            let result = str;
            for (let i = 0; i < 10; i++) {
                result = result.replace(new RegExp(p[i], 'g'), i).replace(new RegExp(a[i], 'g'), i);
            }
            return result;
        }

        function englishToPersian(str) {
            const p = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
            return str.replace(/\d/g, x => p[parseInt(x)]);
        }

        function selectAmount(amount, btn) {
            const input = document.getElementById('amount-input');
            input.value = englishToPersian(amount.toLocaleString('en-US'));
            input.setAttribute('data-value', amount);
            updatePreview(amount);
            validateAmount(amount);
            document.querySelectorAll('.amount-btn').forEach(b => {
                b.style.borderColor = 'rgba(201,162,75,0.3)';
                b.style.backgroundColor = 'rgba(201,162,75,0.05)';
            });
            btn.style.borderColor = 'var(--rasta-gold)';
            btn.style.backgroundColor = 'rgba(201,162,75,0.15)';
        }

        function formatAmountInput(input) {
            let value = persianToEnglish(input.value).replace(/[^\d]/g, '');
            if (value) {
                const num = parseInt(value);
                input.value = englishToPersian(num.toLocaleString('en-US'));
                input.setAttribute('data-value', num);
                updatePreview(num);
                validateAmount(num);
            } else {
                input.value = '';
                input.removeAttribute('data-value');
                document.getElementById('preview-box').classList.add('hidden');
                document.getElementById('submit-btn').disabled = true;
            }
        }

        function updatePreview(amount) {
            const box = document.getElementById('preview-box');
            if (amount >= MIN_AMOUNT && amount <= MAX_AMOUNT) {
                box.classList.remove('hidden');
                document.getElementById('preview-amount').textContent =
                    englishToPersian(amount.toLocaleString('en-US')) + ' تومان';
                document.getElementById('preview-new-balance').textContent =
                    englishToPersian((currentBalance + amount).toLocaleString('en-US')) + ' تومان';
            } else {
                box.classList.add('hidden');
            }
        }

        function validateAmount(amount) {
            document.getElementById('submit-btn').disabled = !(amount >= MIN_AMOUNT && amount <= MAX_AMOUNT);
        }

        document.getElementById('charge-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const input = document.getElementById('amount-input');
            const realValue = input.getAttribute('data-value');
            if (!realValue) { alert('لطفاً مبلغ را وارد کنید'); return; }
            input.name = '';
            const h = document.createElement('input');
            h.type = 'hidden'; h.name = 'amount'; h.value = realValue;
            this.appendChild(h);
            this.submit();
        });
    </script>
@endsection
