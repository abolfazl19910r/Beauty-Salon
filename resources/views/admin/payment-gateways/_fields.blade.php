{{-- ⭐ فیلدهای یک درگاه (افزودن/ویرایش) — ورودی: $driver، $gateway (null هنگام افزودن)، $input، $inputStyle. --}}
@php
    $item = \App\Payments\GatewayCatalog::DRIVERS[$driver];
    $isNew = $gateway === null;
    $prefix = $isNew ? '' : 'g'.$gateway->id.'-';
    $useOld = $isNew ? old('driver') === $driver : false;
@endphp
<p class="text-xs leading-6" style="color: var(--admin-text-light);">
    {{ $item['note'] }}
    <a href="{{ $item['website'] }}" target="_blank" rel="noopener" class="underline" dir="ltr">{{ parse_url($item['website'], PHP_URL_HOST) }}</a>
</p>
@foreach ($item['fields'] as $name => $field)
    <div>
        <label class="block text-sm mb-1" for="{{ $prefix.$driver.'-'.$name }}">{{ $field['label'] }}</label>
        @if (! empty($field['options']))
            {{-- ⭐ فیلد انتخابی (مثل حالت بلوپی سامان) — اولین گزینه پیش‌فرض است --}}
            @php $selected = (string) ($useOld ? old('credentials.'.$name) : ($gateway?->credentials[$name] ?? '')); @endphp
            <select id="{{ $prefix.$driver.'-'.$name }}" name="credentials[{{ $name }}]" class="{{ $input }}" style="{{ $inputStyle }}">
                @foreach ($field['options'] as $value => $optionLabel)
                    <option value="{{ $value }}" @selected($selected === (string) $value)>{{ $optionLabel }}</option>
                @endforeach
            </select>
        @else
        <input id="{{ $prefix.$driver.'-'.$name }}" name="credentials[{{ $name }}]" dir="ltr" autocomplete="off"
               type="{{ $field['secret'] ? 'password' : 'text' }}"
               value="{{ $field['secret'] ? '' : ($useOld ? old('credentials.'.$name) : ($gateway?->credentials[$name] ?? '')) }}"
               placeholder="{{ $field['secret'] && ! $isNew ? '•••••••• (ذخیره شده — برای تغییر مقدار جدید وارد کنید)' : $field['placeholder'] }}"
               class="{{ $input }}" style="{{ $inputStyle }}">
        @endif
        @if (! empty($field['optional']) && $field['secret'] && ! $isNew && filled($gateway?->credentials[$name] ?? null))
            <label class="flex items-center gap-2 mt-1 text-xs" style="color: var(--admin-text-dim);">
                <input type="checkbox" name="clear[{{ $name }}]" value="1"> حذف مقدار ذخیره‌شده
            </label>
        @endif
    </div>
@endforeach
<div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
    <div>
        <label class="block text-sm mb-1" for="{{ $prefix.$driver }}-label">نام نمایشی برای مشتری (اختیاری)</label>
        <input id="{{ $prefix.$driver }}-label" name="label" maxlength="60" class="{{ $input }}" style="{{ $inputStyle }}"
               value="{{ $useOld ? old('label') : ($gateway?->label ?? '') }}" placeholder="{{ $item['label'] }}">
    </div>
    <div>
        <label class="block text-sm mb-1" for="{{ $prefix.$driver }}-fee-percent">کارمزد درصدی (٪)</label>
        <input id="{{ $prefix.$driver }}-fee-percent" name="fee_percent" type="text" inputmode="decimal" dir="ltr" class="{{ $input }}" style="{{ $inputStyle }}"
               value="{{ $useOld ? old('fee_percent') : ($gateway ? (rtrim(rtrim((string) $gateway->fee_percent, '0'), '.') ?: '0') : '0') }}">
    </div>
    <div>
        <label class="block text-sm mb-1" for="{{ $prefix.$driver }}-fee-fixed">کارمزد ثابت (تومان)</label>
        <input id="{{ $prefix.$driver }}-fee-fixed" name="fee_fixed_toman" type="text" inputmode="numeric" dir="ltr" class="{{ $input }}" style="{{ $inputStyle }}"
               value="{{ $useOld ? old('fee_fixed_toman') : ($gateway?->fee_fixed_toman ?? 0) }}">
    </div>
</div>
<label class="flex items-center gap-2 text-sm">
    <input type="checkbox" name="is_active" value="1" @checked($useOld ? old('is_active') : ($gateway?->is_active ?? true))> فعال (در صفحه‌ی پرداخت مشتری نمایش داده شود)
</label>
