{{-- بسته‌ی 5ه، تصمیم ۱: «بدون پیش‌پرداخت» پیش‌فرض؛ «با پیش‌پرداخت» فقط با امکان پرداخت آنلاین پلن + درگاه فعال --}}
@php
    $mode = old('payment_mode', $policy->payment_mode);
@endphp
<form action="{{ route('admin.booking-policy.mode') }}" method="POST" class="{{ $card }}" style="{{ $cardStyle }}" id="booking-mode" novalidate>
    @csrf @method('PUT')
    <h2 class="font-bold" style="color: var(--admin-accent);">پرداخت نوبت</h2>

    @if ($policy->payment_mode_fallback_at && $policy->payment_mode === \App\Models\BookingPolicy::MODE_NONE)
        <div class="rounded-lg p-3 text-sm" style="background:rgb(var(--m-warn) / .14); color:rgb(var(--m-warn));" role="alert">
            رزرو با پیش‌پرداخت در {{ verta($policy->payment_mode_fallback_at)->format('Y/m/d H:i') }} خودکار متوقف شد، چون
            {{ $hasOnlinePaymentFeature ? 'سالن درگاه پرداخت فعالی نداشت' : 'پلن سالن امکان پرداخت آنلاین مشتری را نداشت' }}.
            رزرو مشتری‌ها قطع نشد و بدون پیش‌پرداخت ادامه دارد.
        </div>
    @endif

    <label class="flex items-start gap-3 rounded-lg p-3 cursor-pointer" style="border:1px solid var(--admin-border);">
        <input type="radio" name="payment_mode" value="none" class="mt-1" @checked($mode === 'none')>
        <span class="text-sm leading-7" style="color: var(--admin-text);">
            <b>بدون پیش‌پرداخت (پرداخت کامل در سالن)</b> — پیش‌فرض
            <span class="block" style="color: var(--admin-text-dim);">
                رزرو برای مشتری ساده‌تر است و درگاه پرداخت لازم ندارد. احتمال رزرو فیک و نیامدن بیشتر است (برای همین سقف نوبت و محافظ‌های
                مشتری بدقول پایین‌تر هست). کل مبلغ در سالن گرفته می‌شود، کیف پول متخصص از این نوبت‌ها شارژ نمی‌شود و جریمه‌ی لغو معنا ندارد.
                نوبت متخصص با تأیید خودکار همان لحظه تأیید می‌شود و بقیه منتظر تأیید شما می‌مانند.
            </span>
        </span>
    </label>

    <label class="flex items-start gap-3 rounded-lg p-3 {{ $canPrepay ? 'cursor-pointer' : 'opacity-70' }}" style="border:1px solid var(--admin-border);">
        <input type="radio" name="payment_mode" value="prepay" class="mt-1" @checked($mode === 'prepay') @disabled(! $canPrepay && $mode !== 'prepay')>
        <span class="text-sm leading-7" style="color: var(--admin-text);">
            <b>با پیش‌پرداخت آنلاین</b>
            <span class="block" style="color: var(--admin-text-dim);">
                مشتری بخشی از مبلغ را هنگام رزرو آنلاین می‌پردازد، پس تعهد بیشتری دارد. پیش‌نیاز: امکان «پرداخت آنلاین مشتری» در پلن و حداقل
                یک درگاه فعال. نوبتی که تا {{ to_persian_num((string) \App\Support\Booking\PurchaseTerms::UNPAID_HOLD_MINUTES) }} دقیقه پرداخت
                نشود خودکار لغو می‌شود؛ قوانین بازگشت پول و جریمه‌ی لغو («تنظیمات مالی») اعمال می‌شوند.
                اگر بعداً درگاه غیرفعال شود یا پلن این امکان را نداشته باشد، سالن خودکار به «بدون پیش‌پرداخت» برمی‌گردد و به شما خبر می‌دهیم.
            </span>
            @unless ($canPrepay)
                <span class="block mt-1 font-bold" style="color: rgb(var(--m-bad));">
                    @if (! $hasOnlinePaymentFeature)
                        پلن سالن شما امکان پرداخت آنلاین مشتری را ندارد. <a href="{{ route('admin.billing.index') }}" class="underline">ارتقای پلن</a>
                    @else
                        اول یک درگاه پرداخت فعال اضافه کنید. <a href="{{ route('admin.payment-gateways.index') }}" class="underline">درگاه‌های پرداخت</a>
                    @endif
                </span>
            @endunless
        </span>
    </label>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" id="prepayment-amounts" @if ($mode !== 'prepay') hidden @endif>
        <div>
            <label class="block text-sm mb-1" for="prepayment_percentage" style="color: var(--admin-text-dim);">درصد پیش‌پرداخت از قیمت خدمت</label>
            <input type="number" step="0.01" min="0" max="100" id="prepayment_percentage" name="prepayment_percentage"
                   value="{{ old('prepayment_percentage', (float) $wallet->prepayment_percentage) }}" class="{{ $input }}" style="{{ $inputStyle }}">
        </div>
        <div>
            <label class="block text-sm mb-1" for="minimum_prepayment_amount" style="color: var(--admin-text-dim);">حداقل پیش‌پرداخت (تومان)</label>
            <input type="number" step="1000" min="0" max="100000000" id="minimum_prepayment_amount" name="minimum_prepayment_amount"
                   value="{{ old('minimum_prepayment_amount', (float) $wallet->minimum_prepayment_amount) }}" class="{{ $input }}" style="{{ $inputStyle }}">
        </div>
        <p class="sm:col-span-2 text-xs" style="color: var(--admin-text-light);">
            پیش‌پرداخت هیچ‌وقت از قیمت خدمت بیشتر نمی‌شود. حداقل پیش‌پرداخت دست‌کم ۱٬۰۰۰ تومان است (کمترین مبلغ درگاه زرین‌پال). کد تخفیف در این حالت از باقی‌مانده‌ای که در سالن پرداخت می‌شود کم می‌شود.
            دوره‌های درمانی پیش‌پرداخت جدای خودشان را دارند و از این تنظیم پیروی نمی‌کنند.
        </p>
    </div>

    <p class="text-xs" style="color: var(--admin-text-light);">
        با رفتن از «با پیش‌پرداخت» به «بدون پیش‌پرداخت»، نوبت‌هایی که هنوز پرداخت نشده‌اند منتظر تأیید شما می‌شوند و به مشتری و متخصص خبر
        داده می‌شود (هیچ پیامی درخواست پرداخت نمی‌کند). با رفتن به «با پیش‌پرداخت»، نوبت‌های قبلی دست نمی‌خورند و فقط نوبت‌های تازه پیش‌پرداخت لازم دارند.
    </p>

    <button type="submit" class="px-4 py-2 rounded-lg text-sm font-bold text-m-onsolid" style="background: var(--admin-accent);">ذخیره‌ی پرداخت نوبت</button>
</form>

<script>
    document.querySelectorAll('#booking-mode input[name="payment_mode"]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            document.getElementById('prepayment-amounts').hidden = this.value !== 'prepay' || !this.checked;
        });
    });
</script>
