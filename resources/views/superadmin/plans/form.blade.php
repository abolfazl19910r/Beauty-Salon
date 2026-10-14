@extends('layouts.superadmin')

@section('title', $plan->exists ? 'ویرایش پلن «'.$plan->name.'»' : 'پلن جدید')

@section('content')
    @php
        $enabled = old('features', $plan->exists ? $plan->featureKeys() : []);
        $field = fn (string $name, $value) => old($name, $value);
        $priceLabels = ['1m' => 'یک‌ماهه', '3m' => 'سه‌ماهه', '6m' => 'شش‌ماهه', '12m' => 'دوازده‌ماهه'];
        $monthly = $plan->priceFor('1m');
    @endphp

    <form method="POST" action="{{ $plan->exists ? route('superadmin.plans.update', $plan) : route('superadmin.plans.store') }}" class="grid gap-5" novalidate
          data-plan-form data-salon-count="{{ $salonCount }}" data-original-features="{{ implode(',', $plan->exists ? $plan->featureKeys() : []) }}">
        @csrf
        @if ($plan->exists)
            @method('PUT')
        @endif

        <div class="sa-card p-5 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="sa-label" for="name">نام</label>
                <input id="name" name="name" value="{{ $field('name', $plan->name) }}" class="sa-input" required maxlength="100">
            </div>
            <div>
                <label class="sa-label" for="key">شناسه (انگلیسی، یکتا)</label>
                <input id="key" name="key" value="{{ $field('key', $plan->key) }}" class="sa-input" dir="ltr" required maxlength="40">
            </div>
            <div class="md:col-span-2">
                <label class="sa-label" for="description">توضیح کوتاه (در صفحه‌ی صورتحساب سالن)</label>
                <input id="description" name="description" value="{{ $field('description', $plan->description) }}" class="sa-input" maxlength="500">
            </div>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_active" value="1" @checked($field('is_active', $plan->is_active))>
                فعال <span style="color: var(--sa-text-dim);">{{ $plan->is_trial ? '(غیرفعال: سالن تازه بدون دوره‌ی آزمایشی ساخته می‌شود)' : '(غیرفعال: به سالن تازه فروخته نمی‌شود؛ سالن‌های فعلی‌اش تمدیدش می‌کنند)' }}</span>
            </label>
            @unless ($plan->is_trial)
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_public" value="1" @checked($field('is_public', $plan->is_public))>
                    عمومی <span style="color: var(--sa-text-dim);">(در صفحه‌ی فروش و صورتحساب سالن‌ها)</span>
                </label>
            @endunless
            <div>
                <label class="sa-label" for="sort">ترتیب نمایش</label>
                <input id="sort" name="sort" value="{{ $field('sort', $plan->sort ?? 0) }}" class="sa-input" inputmode="numeric" dir="ltr">
            </div>
            @if ($plan->exists)
                <div class="text-sm self-end" style="color: var(--sa-text-dim);">
                    {{ to_persian_num((string) $salonCount) }} سالن روی این پلن‌اند.
                </div>
            @endif
        </div>

        <div class="sa-card p-5 grid grid-cols-1 md:grid-cols-4 gap-4">
            @if ($plan->is_trial)
                <div>
                    <label class="sa-label" for="trial_days">روزهای آزمایشی</label>
                    <input id="trial_days" name="trial_days" value="{{ $field('trial_days', $plan->trial_days) }}" class="sa-input" inputmode="numeric" dir="ltr">
                    <p class="text-xs mt-1" style="color: var(--sa-text-dim);">۰ یا غیرفعال = سالن تازه بدون آزمایشی ساخته می‌شود.</p>
                </div>
            @else
                @foreach ($priceLabels as $period => $label)
                    <div>
                        <label class="sa-label" for="price_{{ $period }}">قیمت {{ $label }} (تومان)</label>
                        <input id="price_{{ $period }}" name="price_{{ $period }}" value="{{ $field('price_'.$period, $plan->priceFor($period)) }}"
                               class="sa-input persian-number" inputmode="numeric" dir="ltr" placeholder="خالی = فروخته نمی‌شود">
                        @if ($monthly && $period !== '1m' && $plan->priceFor($period))
                            @php $months = \App\Support\Billing\SubscriptionPricing::PLAN_MONTHS[$period]; $saving = $monthly * $months - $plan->priceFor($period); @endphp
                            <p class="text-xs mt-1 persian-number" style="color: var(--sa-text-dim);">
                                صرفه‌جویی نسبت به ماه‌به‌ماه: {{ number_format(max(0, $saving)) }} تومان ({{ to_persian_num((string) round($saving / ($monthly * $months) * 100)) }}٪)
                            </p>
                        @endif
                    </div>
                @endforeach
            @endif
            <div>
                <label class="sa-label" for="included_specialists">متخصص شامل قیمت</label>
                <input id="included_specialists" name="included_specialists" value="{{ $field('included_specialists', $plan->included_specialists) }}" class="sa-input" inputmode="numeric" dir="ltr">
            </div>
            @unless ($plan->is_trial)
                <div>
                    <label class="sa-label" for="extra_specialist_price_per_month">هر متخصص بیشتر، ماهانه (تومان)</label>
                    <input id="extra_specialist_price_per_month" name="extra_specialist_price_per_month" value="{{ $field('extra_specialist_price_per_month', $plan->extra_specialist_price_per_month) }}" class="sa-input persian-number" inputmode="numeric" dir="ltr">
                </div>
            @else
                <input type="hidden" name="extra_specialist_price_per_month" value="0">
            @endunless
            <div>
                <label class="sa-label" for="sms_quota_per_month">سهمیه‌ی پیامک ماهانه (قطعه)</label>
                <input id="sms_quota_per_month" name="sms_quota_per_month" value="{{ $field('sms_quota_per_month', $plan->sms_quota_per_month) }}" class="sa-input persian-number" inputmode="numeric" dir="ltr">
            </div>
        </div>

        <div class="sa-card p-5">
            <h3 class="font-bold mb-3">امکانات</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                @foreach (\App\Support\Features\Feature::grouped() as $group => $features)
                    <div>
                        <div class="text-sm font-bold mb-2" style="color: var(--sa-accent);">{{ $group }}</div>
                        @foreach ($features as $feature)
                            <label class="flex items-start gap-2 text-sm mb-2">
                                <input type="checkbox" name="features[]" value="{{ $feature->value }}" class="mt-1" @checked(in_array($feature->value, $enabled, true))>
                                <span>
                                    {{ $feature->label() }}
                                    @if ($feature->isComingSoon())
                                        <span class="text-xs px-1.5 rounded" style="background: var(--sa-bg); color: var(--sa-text-dim);">به‌زودی</span>
                                    @endif
                                    <span class="block text-xs" style="color: var(--sa-text-dim);">{{ $feature->description() }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                @endforeach
            </div>
            <p class="text-xs mt-3" style="color: var(--sa-text-dim);">
                همیشه روشن و بیرون از پلن: کد تأیید ورود/رمز، هشدارهای سهمیه و «نیاز به بررسی»، پیامک برگشت پول.
                خاموش کردن یک امکان داده‌ای را پاک نمی‌کند؛ فقط استفاده‌ی تازه بسته می‌شود.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="sa-btn">ذخیره</button>
            <a href="{{ route('superadmin.plans.index') }}" style="color: var(--sa-text-dim);">بازگشت</a>
        </div>
    </form>

    @if ($plan->exists && $salonCount === 0 && ! $plan->is_trial)
        <form method="POST" action="{{ route('superadmin.plans.destroy', $plan) }}" class="mt-6" onsubmit="return confirm('این پلن حذف شود؟');">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-sm" style="color: var(--sa-danger);">حذف پلن</button>
        </form>
    @endif

    <script>
        // هشدار پیش از برداشتن امکانی که سالن‌ها الان دارند
        (function () {
            const form = document.querySelector('[data-plan-form]');
            const count = Number(form.dataset.salonCount || 0);
            const original = (form.dataset.originalFeatures || '').split(',').filter(Boolean);
            form.addEventListener('submit', function (e) {
                if (count === 0) return;
                const checked = Array.from(form.querySelectorAll('input[name="features[]"]:checked')).map(i => i.value);
                const removed = original.filter(f => !checked.includes(f));
                if (removed.length && !confirm(count + ' سالن ' + removed.length + ' امکان را از دست می‌دهند. ادامه می‌دهید؟')) {
                    e.preventDefault();
                }
            });
        })();
    </script>
@endsection
