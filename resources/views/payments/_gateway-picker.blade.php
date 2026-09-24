{{--
    ⭐ انتخاب درگاه پرداخت توسط مشتری (مرحله‌ی ۱ چند درگاه، ۲۰۲۶-۰۹-۲۵) — صفحه‌ی پرداخت نوبت و شارژ کیف پول.
    ورودی: $gatewayOptions (PaymentService::gatewayOptions)، $baseAmount (تومان، برای نمایش اولیه).
    باید داخل <form> پرداخت قرار بگیرد (رادیوی gateway_id). اولین درگاه (بالاترین اولویت) پیش‌فرض است.
    window.RastaGatewayFee.fee(base) همان فرمول SalonPaymentGateway::feeRialFor را در مرورگر حساب می‌کند
    تا مبلغ نهایی قبل از رفتن به درگاه نمایش داده شود؛ مبلغ واقعی همیشه سمت سرور حساب می‌شود.
    رویداد 'gateway-changed' روی document بعد از هر تغییر انتخاب dispatch می‌شود.
--}}
@if (! empty($gatewayOptions))
    <div class="mb-5" id="gateway-picker">
        <p class="font-bold text-sm mb-3" style="color:#E6CD8A;">انتخاب درگاه پرداخت</p>
        <div class="space-y-2">
            @foreach ($gatewayOptions as $i => $option)
                <label class="flex items-center gap-3 p-3 rounded-xl cursor-pointer"
                       style="background:rgba(26,20,16,0.5); border:1px solid rgba(201,162,75,0.2);">
                    <input type="radio" name="gateway_id" value="{{ $option['id'] }}" @checked($i === 0)
                           data-fee-percent="{{ $option['fee_percent'] }}" data-fee-fixed="{{ $option['fee_fixed'] }}"
                           style="accent-color:#C9A24B;">
                    <span class="flex-1 text-sm" style="color:#F8F3E9;">{{ $option['name'] }}</span>
                    <span class="text-xs persian-number" style="color:rgba(248,243,233,0.6);" data-gateway-fee-label>
                        @if ($option['fee'] > 0)
                            کارمزد: {{ number_format($option['fee']) }} تومان
                        @elseif ($option['fee_percent'] > 0 || $option['fee_fixed'] > 0)
                            دارای کارمزد
                        @else
                            بدون کارمزد
                        @endif
                    </span>
                </label>
            @endforeach
        </div>
        @if (count($gatewayOptions) > 1)
            <p class="text-xs mt-2 leading-6" style="color:rgba(248,243,233,0.5);">
                اگر درگاه انتخابی در دسترس نباشد، پرداخت خودکار با درگاه بعدی انجام می‌شود و مبلغ نهایی در صفحه‌ی درگاه نمایش داده می‌شود.
            </p>
        @endif
    </div>
    <script>
        window.RastaGatewayFee = {
            selected() { return document.querySelector('#gateway-picker input[name="gateway_id"]:checked'); },
            feeFor(input, base) {
                if (!input || !(base > 0)) return 0;
                const percent = parseFloat(input.dataset.feePercent || '0');
                const fixed = parseInt(input.dataset.feeFixed || '0', 10);
                return Math.ceil(Math.round(percent * base / 100 * 10000) / 10000) + fixed;
            },
            fee(base) { return this.feeFor(this.selected(), base); },
            refreshLabels(base) {
                document.querySelectorAll('#gateway-picker input[name="gateway_id"]').forEach(input => {
                    const label = input.closest('label').querySelector('[data-gateway-fee-label]');
                    const hasFee = parseFloat(input.dataset.feePercent || '0') > 0 || parseInt(input.dataset.feeFixed || '0', 10) > 0;
                    const fee = this.feeFor(input, base);
                    label.textContent = !hasFee ? 'بدون کارمزد' : (fee > 0 ? 'کارمزد: ' + fee.toLocaleString('fa-IR') + ' تومان' : 'دارای کارمزد');
                });
            },
        };
        document.querySelectorAll('#gateway-picker input[name="gateway_id"]').forEach(input =>
            input.addEventListener('change', () => document.dispatchEvent(new Event('gateway-changed'))));
    </script>
@endif
