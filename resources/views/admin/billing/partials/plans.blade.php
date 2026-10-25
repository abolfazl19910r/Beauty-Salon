{{-- خرید/تمدید/ارتقای پلن (بسته‌ی پلن‌ها، ۲۰۲۶-۱۰-۱۴). هر پلن یک کارت با امکانات و قیمت هر بازه برای تعداد متخصص همین سالن. --}}
@php
    $periodLabels = ['1m' => 'یک ماهه', '3m' => 'سه ماهه', '6m' => 'شش ماهه', '12m' => 'دوازده ماهه'];
    $features = collect(\App\Support\Features\Feature::cases())->reject(fn ($f) => $f->isComingSoon() && ! collect($planOptions)->contains(fn ($o) => in_array($f->value, $o['features'], true)));
@endphp
<div class="rounded-xl overflow-hidden mb-6" style="background:var(--admin-surface); border:1px solid var(--admin-border);" data-testid="plan-purchase">
    <div class="px-4 py-3 text-sm font-bold" style="background:var(--admin-accent-light); border-bottom:1px solid var(--admin-border); color:var(--admin-text);">
        تمدید / خرید آنلاین اشتراک
    </div>

    <form method="POST" action="{{ route('admin.billing.purchase') }}" class="p-5">
        @csrf
        <div class="grid grid-cols-1 {{ count($planOptions) > 1 ? 'lg:grid-cols-2' : '' }} gap-4">
            @foreach ($planOptions as $option)
                @php $plan = $option['plan']; @endphp
                <div class="rounded-lg p-4" style="border:1px solid {{ $currentPlan && $currentPlan->id === $plan->id ? 'var(--admin-accent)' : 'var(--admin-border)' }};">
                    <div class="flex items-center justify-between gap-2">
                        <div class="font-bold" style="color:var(--admin-text);">{{ $plan->name }}</div>
                        @if ($currentPlan && $currentPlan->id === $plan->id)
                            <span class="text-xs px-2 py-0.5 rounded-full" style="background:var(--admin-accent-light); color:var(--admin-accent);">پلن فعلی</span>
                        @elseif ($option['recommended'] ?? false)
                            <span class="text-xs px-2 py-0.5 rounded-full" style="background:var(--admin-accent-light); color:var(--admin-accent);" data-plan-recommended>پیشنهاد ما</span>
                        @endif
                    </div>
                    @if ($plan->description)
                        <p class="text-xs mt-1" style="color:var(--admin-text-dim);">{{ $plan->description }}</p>
                    @endif

                    <ul class="text-xs mt-3 space-y-1">
                        @foreach ($features as $feature)
                            @php $on = in_array($feature->value, $option['features'], true); @endphp
                            <li style="color:{{ $on ? 'var(--admin-text)' : 'var(--admin-text-dim)' }};">
                                {{ $on ? '✓' : '—' }} {{ $feature->label() }}{{ $feature->isComingSoon() ? ' (به‌زودی)' : '' }}
                            </li>
                        @endforeach
                        <li class="persian-number" style="color:var(--admin-text);">✓ سهمیه‌ی پیامک ماهانه {{ to_persian_num(number_format($plan->sms_quota_per_month)) }} قطعه</li>
                        <li class="persian-number" style="color:var(--admin-text);">✓ {{ to_persian_num((string) $option['included']) }} متخصص در قیمت؛ هر متخصص بیشتر ماهانه {{ to_persian_num(number_format($option['extra_price'])) }} تومان</li>
                        <li class="persian-number" style="color:var(--admin-text);" data-plan-admin-themes>✓ قالب پنل مدیر: پیش‌فرض{{ ($option['admin_themes'] ?? 0) > 0 ? ' + '.to_persian_num((string) $option['admin_themes']) : '' }}</li>
                    </ul>

                    <div class="grid grid-cols-2 gap-2 mt-4">
                        @foreach ($option['prices'] as $type => $price)
                            <label class="rounded-lg p-3 cursor-pointer text-center text-sm" style="border:1px solid var(--admin-border);">
                                <input type="radio" name="choice" value="{{ $plan->id }}:{{ $type }}" class="ml-1" required
                                       {{ $plan->id === $defaultPlanId && $salon->subscription_type === $type ? 'checked' : '' }}>
                                <div class="font-bold mt-1" style="color:var(--admin-text);">{{ $periodLabels[$type] }}</div>
                                @php $detail = $option['periods'][$type] ?? null; @endphp
                                <div class="mt-1 persian-number" style="color:var(--admin-text-dim);">{{ number_format($price) }} تومان</div>
                                @if ($detail)
                                    <div class="mt-1 text-xs persian-number" style="color:var(--admin-text-dim);" data-period-detail="{{ $type }}">
                                        @if ($type !== '1m')ماهانه {{ to_persian_num(number_format($detail['per_month'])) }}@endif
                                        @if ($detail['saving_percent'] > 0)<span style="color:rgb(var(--m-ok));"> · {{ to_persian_num((string) $detail['saving_percent']) }}٪ تخفیف</span>@endif
                                        <div>{{ $detail['themes'] > 0 ? to_persian_num((string) $detail['themes']).' قالب سایت' : 'فقط قالب پیش‌فرض' }}</div>
                                    </div>
                                    @if ($detail['cheaper'])
                                        <div class="mt-1 text-xs" style="color:rgb(var(--m-warn));" data-cheaper-plan>«{{ $detail['cheaper'] }}» با امکانات بیشتر ارزان‌تر است</div>
                                    @endif
                                @endif
                            </label>
                        @endforeach
                    </div>
                    @if ($option['purchase_error'])
                        <p class="text-xs mt-2" style="color:rgb(var(--m-warn));">{{ $option['purchase_error'] }}</p>
                    @endif

                    @if ($option['upgrade_price'] !== null)
                        <div class="mt-3 p-3 rounded-lg text-xs persian-number" style="background:var(--admin-accent-light); color:var(--admin-text);">
                            ارتقای فوری: {{ to_persian_num(number_format($option['upgrade_price'])) }} تومان برای {{ to_persian_num((string) $remainingDays) }} روز مانده‌ی اشتراک؛
                            تاریخ پایان عوض نمی‌شود.
                            <button type="submit" form="upgrade-{{ $plan->id }}" class="mr-2 px-3 py-1 rounded-lg text-m-onsolid font-bold" style="background:var(--admin-accent);">ارتقا</button>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <p class="text-xs mt-3 leading-6" style="color:var(--admin-text-dim);">
            قیمت‌ها برای {{ to_persian_num((string) $specialists) }} متخصص (سقف متخصص سالن شما) است؛ قیمت پایه‌ی صفحه‌ی معرفی ماهرو برای تعداد
            متخصصِ شامل‌شده‌ی هر پلن است و هر متخصص بیشتر جدا حساب می‌شود. برای تغییر تعداد متخصص با پشتیبانی تماس بگیرید.
            خرید پلن دیگر در دوره‌ی آزمایشی یا بعد از پایان اشتراک همان لحظه اثر می‌کند؛ وسط اشتراک فعال، از پایان دوره‌ی فعلی (مبلغی برنمی‌گردد).
            برای پلن گران‌تر، «ارتقای فوری» همین حالا پلن را عوض می‌کند.
        </p>
        {{-- بسته‌ی مجوز 5د (ماده‌ی ۳۳): شرایط پیش از خرید؛ فاکتور هر پرداخت در «تاریخچه‌ی فاکتورها» همین صفحه می‌ماند --}}
        <p class="mt-2 text-xs" style="color:var(--admin-text-dim);" data-billing-terms>
            خرید طبق
            <a href="{{ \App\Support\CentralUrl::route('legal.terms') }}#salons" target="_blank" class="underline">قوانین و شرایط سالن‌ها</a>؛
            فاکتور در «تاریخچه‌ی فاکتورها» پایین همین صفحه می‌ماند.
        </p>
        <button type="submit" class="mt-4 px-5 py-2.5 rounded-lg text-sm font-bold text-m-onsolid" style="background:var(--admin-accent);">
            پرداخت و تمدید
        </button>
    </form>

    @foreach ($planOptions as $option)
        @if ($option['upgrade_price'] !== null)
            <form method="POST" action="{{ route('admin.billing.upgrade') }}" id="upgrade-{{ $option['plan']->id }}" class="hidden">
                @csrf
                <input type="hidden" name="plan_id" value="{{ $option['plan']->id }}">
            </form>
        @endif
    @endforeach
</div>
