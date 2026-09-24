@extends('layouts.admin')
@section('title', 'درگاه‌های پرداخت')

{{--
    ⭐ درگاه‌های پرداخت سالن (مرحله‌ی ۱ چند درگاه، ۲۰۲۶-۰۹-۲۵) — AdminPaymentGatewayController.
    فرم افزودن: برای هر نوع درگاه یک fieldset؛ فقط fieldset درگاه انتخاب‌شده فعال است (fieldset غیرفعال
    هیچ ورودی‌ای نمی‌فرستد). فیلدهای مخفی (رمز/کلید) هرگز دوباره نمایش داده نمی‌شوند.
--}}
@php
    $input = 'w-full rounded-lg px-3 py-2 text-sm';
    $inputStyle = 'border:1px solid var(--admin-border); background:var(--admin-bg); color:var(--admin-text);';
    $card = 'rounded-xl p-5';
    $cardStyle = 'background:var(--admin-card, var(--admin-bg)); border:1px solid var(--admin-border);';
    $oldDriver = old('driver', collect($catalog)->keys()->first(fn ($k) => ! $gateways->contains('driver', $k)));
    $feeText = function ($g) {
        $parts = [];
        if ((float) $g->fee_percent > 0) { $parts[] = rtrim(rtrim(number_format((float) $g->fee_percent, 2), '0'), '.').'٪'; }
        if ((int) $g->fee_fixed_toman > 0) { $parts[] = number_format($g->fee_fixed_toman).' تومان'; }
        return $parts ? implode(' + ', $parts) : 'بدون کارمزد';
    };
@endphp

@section('content')
    <div class="max-w-4xl mx-auto space-y-6">
        <div>
            <h1 class="text-xl font-bold" style="color: var(--admin-text);">درگاه‌های پرداخت</h1>
            <p class="text-sm mt-1 leading-7" style="color: var(--admin-text-dim);">
                پرداخت‌های مشتری‌ها (پیش‌پرداخت نوبت، پرداخت باقی‌مانده و شارژ کیف پول) مستقیم به حساب همین درگاه‌های سالن شما واریز می‌شود.
                مشتری در صفحه‌ی پرداخت یکی از درگاه‌های فعال را انتخاب می‌کند (پیش‌فرض: اولین درگاه)؛ اگر درگاه انتخابی در دسترس نباشد،
                پرداخت خودکار با درگاه بعدی همین فهرست انجام می‌شود.
            </p>
        </div>

        @if ($errors->any())
            <div class="rounded-lg p-3 text-sm" style="background:#FEE2E2; color:#991B1B;">
                <ul class="list-disc pr-5 space-y-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        @unless ($gateways->where('is_active', true)->count())
            <div class="rounded-lg p-3 text-sm leading-7" style="background:#FEE2E2; color:#991B1B;">
                <b>پرداخت آنلاین سالن غیرفعال است.</b> تا حداقل یک درگاه فعال اضافه نشود، مشتری‌ها نمی‌توانند پیش‌پرداخت نوبت
                یا شارژ کیف پول انجام دهند و رزرو خدماتی که پیش‌پرداخت دارند ممکن نیست.
            </div>
        @endunless

        {{-- فهرست درگاه‌ها به ترتیب --}}
        <section class="space-y-3">
            @forelse ($gateways as $i => $gateway)
                <div class="{{ $card }}" style="{{ $cardStyle }}" id="gateway-{{ $gateway->id }}">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="w-7 h-7 rounded-full flex items-center justify-center text-sm font-bold" style="background:var(--admin-accent); color:#fff;">{{ $i + 1 }}</span>
                            <div>
                                <div class="font-bold" style="color: var(--admin-text);">{{ $gateway->displayName() }}
                                    <span class="text-xs font-normal" style="color: var(--admin-text-light);">({{ \App\Payments\GatewayCatalog::label($gateway->driver) }})</span>
                                </div>
                                <div class="text-xs mt-1" style="color: var(--admin-text-dim);">کارمزد روی مبلغ مشتری: {{ $feeText($gateway) }}</div>
                            </div>
                            @if ($gateway->is_active)
                                <span class="text-xs rounded-full px-2 py-0.5" style="background:#DCFCE7; color:#166534;">فعال</span>
                            @else
                                <span class="text-xs rounded-full px-2 py-0.5" style="background:#F1F5F9; color:#475569;">غیرفعال</span>
                            @endif
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <form method="POST" action="{{ route('admin.payment-gateways.move', $gateway->id) }}">
                                @csrf <input type="hidden" name="direction" value="up">
                                <button type="submit" class="px-2 py-1 rounded text-sm" style="border:1px solid var(--admin-border);" title="بالاتر" @disabled($i === 0)>▲</button>
                            </form>
                            <form method="POST" action="{{ route('admin.payment-gateways.move', $gateway->id) }}">
                                @csrf <input type="hidden" name="direction" value="down">
                                <button type="submit" class="px-2 py-1 rounded text-sm" style="border:1px solid var(--admin-border);" title="پایین‌تر" @disabled($i === $gateways->count() - 1)>▼</button>
                            </form>
                            <form method="POST" action="{{ route('admin.payment-gateways.test', $gateway->id) }}">
                                @csrf
                                <button type="submit" class="px-3 py-1 rounded text-sm" style="border:1px solid var(--admin-border); color: var(--admin-accent);">تست اتصال</button>
                            </form>
                            <form method="POST" action="{{ route('admin.payment-gateways.destroy', $gateway->id) }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="px-3 py-1 rounded text-sm" style="border:1px solid #FCA5A5; color:#B91C1C;"
                                        data-confirm-delete data-confirm-message="درگاه «{{ $gateway->displayName() }}» حذف شود؟">حذف</button>
                            </form>
                        </div>
                    </div>

                    <details class="mt-4">
                        <summary class="text-sm cursor-pointer" style="color: var(--admin-accent);">ویرایش</summary>
                        <form method="POST" action="{{ route('admin.payment-gateways.update', $gateway->id) }}" class="mt-3 space-y-3" novalidate>
                            @csrf @method('PUT')
                            @include('admin.payment-gateways._fields', ['driver' => $gateway->driver, 'gateway' => $gateway])
                            <button type="submit" class="px-4 py-2 rounded-lg text-sm font-bold text-white" style="background: var(--admin-accent);">ذخیره</button>
                        </form>
                    </details>
                </div>
            @empty
                <div class="{{ $card }} text-sm" style="{{ $cardStyle }} color: var(--admin-text-dim);">هنوز هیچ درگاهی اضافه نشده است.</div>
            @endforelse
        </section>

        {{-- افزودن درگاه --}}
        @php $available = collect($catalog)->reject(fn ($d, $k) => $gateways->contains('driver', $k)); @endphp
        @if ($available->isNotEmpty())
            <section class="{{ $card }}" style="{{ $cardStyle }}" id="add-gateway">
                <h2 class="font-bold mb-3" style="color: var(--admin-accent);">افزودن درگاه</h2>
                <form method="POST" action="{{ route('admin.payment-gateways.store') }}" class="space-y-3" novalidate>
                    @csrf
                    <div>
                        <label class="block text-sm mb-1" for="driver">نوع درگاه</label>
                        <select id="driver" name="driver" class="{{ $input }}" style="{{ $inputStyle }}">
                            @foreach ($available as $key => $item)
                                <option value="{{ $key }}" @selected($oldDriver === $key)>{{ $item['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    @foreach ($available as $key => $item)
                        <fieldset data-gateway-driver="{{ $key }}" class="space-y-3" @disabled($oldDriver !== $key) @style(['display:none' => $oldDriver !== $key])>
                            @include('admin.payment-gateways._fields', ['driver' => $key, 'gateway' => null])
                        </fieldset>
                    @endforeach
                    <button type="submit" class="px-4 py-2 rounded-lg text-sm font-bold text-white" style="background: var(--admin-accent);">افزودن درگاه</button>
                </form>
            </section>
        @endif

        <section class="rounded-lg p-4 text-xs leading-6" style="background:#FEF9C3; color:#713F12;">
            <b>نکته‌ها:</b>
            دامنه‌ی سایت سالن (یا ساب‌دامین آن) باید در پنل هر درگاه برای همان درگاه ثبت شده باشد؛ درگاه‌ها آدرس بازگشت را با آن مقایسه می‌کنند.
            کارمزدی که اینجا وارد می‌کنید به مبلغ پرداختی مشتری اضافه می‌شود و در صفحه‌ی پرداخت به او نمایش داده می‌شود —
            اگر گزینه‌ی «کارمزد بر عهده‌ی خریدار» را در پنل خود درگاه هم فعال کرده‌اید، اینجا کارمزد وارد نکنید تا دوبار گرفته نشود.
            «تست اتصال» فقط یک درخواست پرداخت آزمایشی می‌سازد؛ مشتری‌ای به درگاه نمی‌رود و پولی جابه‌جا نمی‌شود.
        </section>
    </div>

    <script>
        (function () {
            const select = document.getElementById('driver');
            if (!select) return;
            const sync = () => document.querySelectorAll('[data-gateway-driver]').forEach(fs => {
                const on = fs.dataset.gatewayDriver === select.value;
                fs.disabled = !on;
                fs.style.display = on ? '' : 'none';
            });
            select.addEventListener('change', sync);
            sync();
        })();
    </script>
@endsection
