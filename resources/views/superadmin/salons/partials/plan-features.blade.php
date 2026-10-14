{{-- پلن سالن و استثنای امکانات (بسته‌ی پلن‌ها، ۲۰۲۶-۱۰-۱۴). تغییر دستی پلن بدون پرداخت و بدون تغییر تاریخ اشتراک است. --}}
@php
    $current = $salon->effectivePlan();
    $scheduled = app(\App\Services\Plans\PlanBilling::class)->scheduledPlan($salon);
@endphp
<div class="mb-5 p-4 rounded-lg text-sm" style="border:1px solid var(--sa-border);" data-testid="salon-plan">
    <div class="font-bold mb-2">پلن و امکانات</div>
    <div class="mb-3">
        پلن فعلی: <strong>{{ $current?->name ?? 'بدون پلن (همه‌ی امکانات، قیمت از فایل تنظیمات)' }}</strong>
        @if ($scheduled)
            <span style="color: var(--sa-text-dim);">— از {{ jalali_date($salon->next_plan_starts_at) }} «{{ $scheduled->name }}» (خریده‌شده)</span>
        @endif
    </div>

    <form method="POST" action="{{ route('superadmin.salons.plan', $salon) }}" class="flex flex-wrap items-end gap-3 mb-4">
        @csrf
        @method('PUT')
        <div class="flex-1 min-w-[12rem]">
            <label class="sa-label" for="plan_id">تغییر دستی پلن (بدون پرداخت؛ تغییر زمان‌بندی‌شده لغو می‌شود)</label>
            <select id="plan_id" name="plan_id" class="sa-input">
                @foreach ($plans as $plan)
                    <option value="{{ $plan->id }}" @selected($current?->id === $plan->id)>
                        {{ $plan->name }}{{ $plan->is_active ? '' : ' (غیرفعال)' }}{{ $plan->is_trial ? ' — آزمایشی' : '' }}
                    </option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="sa-btn">ذخیره‌ی پلن</button>
    </form>

    <div class="flex flex-wrap gap-2 mb-4">
        @foreach (\App\Support\Features\Feature::cases() as $feature)
            @php $on = in_array($feature->value, $enabledFeatures, true); @endphp
            <span class="px-2 py-1 rounded-full text-xs" style="{{ $on ? 'background: rgba(34,197,94,0.15); color:#86EFAC;' : 'background: var(--sa-bg); color: var(--sa-text-dim);' }}">
                {{ $on ? '✓' : '✗' }} {{ $feature->label() }}
            </span>
        @endforeach
    </div>

    <div class="font-bold mb-2">استثنای دستی (بالاتر از پلن)</div>
    @if ($overrides->isNotEmpty())
        <table class="w-full text-xs mb-3">
            <thead><tr style="color: var(--sa-text-dim);"><th class="text-right p-1">امکان</th><th class="text-right p-1">حالت</th><th class="text-right p-1">تا</th><th class="text-right p-1">یادداشت</th><th></th></tr></thead>
            <tbody>
                @foreach ($overrides as $override)
                    @php $feature = \App\Support\Features\Feature::tryFrom($override->feature); @endphp
                    <tr style="border-top:1px solid var(--sa-border); {{ $override->isInEffect() ? '' : 'opacity:.5;' }}">
                        <td class="p-1">{{ $feature?->label() ?? $override->feature }}</td>
                        <td class="p-1">{{ $override->enabled ? 'روشن اجباری' : 'خاموش اجباری' }}</td>
                        <td class="p-1">{{ $override->expires_at ? jalali_date($override->expires_at, 'Y/m/d H:i') : 'همیشه' }}{{ $override->isInEffect() ? '' : ' (منقضی)' }}</td>
                        <td class="p-1">{{ $override->note }} @if ($override->setBy)<span style="color: var(--sa-text-dim);">— {{ $override->setBy->name }}</span>@endif</td>
                        <td class="p-1">
                            <form method="POST" action="{{ route('superadmin.salons.feature-overrides.destroy', [$salon, $override]) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" style="color: var(--sa-danger);">برداشتن</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <form method="POST" action="{{ route('superadmin.salons.feature-overrides.store', $salon) }}" class="grid grid-cols-1 md:grid-cols-4 gap-2 items-end">
        @csrf
        <div>
            <label class="sa-label" for="override_feature">امکان</label>
            <select id="override_feature" name="feature" class="sa-input">
                @foreach (\App\Support\Features\Feature::cases() as $feature)
                    <option value="{{ $feature->value }}">{{ $feature->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="sa-label" for="override_enabled">حالت</label>
            <select id="override_enabled" name="enabled" class="sa-input">
                <option value="1">روشن اجباری</option>
                <option value="0">خاموش اجباری</option>
            </select>
        </div>
        <div>
            <label class="sa-label" for="override_expires">تا تاریخ شمسی (اختیاری)</label>
            <input id="override_expires" name="expires_at" class="sa-input" dir="ltr" placeholder="۱۴۰۵/۰۸/۳۰" inputmode="numeric">
        </div>
        <div>
            <label class="sa-label" for="override_note">یادداشت</label>
            <input id="override_note" name="note" maxlength="255" class="sa-input" placeholder="مثلاً یک ماه رایگان برای امتحان">
        </div>
        <div class="md:col-span-4"><button type="submit" class="sa-btn">ثبت استثنا</button></div>
    </form>
</div>
