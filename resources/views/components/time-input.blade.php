{{--
    ساعت ۲۴ساعته‌ی فارسی به‌جای <input type="time"> که مرورگر با زبان سیستم «AM/PM» انگلیسی نشان می‌دهد (بسته‌ی ۰، ۲۰۲۶-۱۱-۲۴).
    دو select (ساعت ۰۰–۲۳، دقیقه با گام $step) و یک input مخفی با همان name/id و مقدار لاتین «HH:MM» که فرم و JS صفحه
    (مثل initJcal) همان را می‌خوانند؛ partials/ui-handlers (data-ui-time) با هر تغییر مقدار را می‌سازد و change می‌فرستد.
    nullable ← گزینه‌ی «—» و مقدار خالی. مقدار موجودی که روی گام نیست (مثلاً ۱۰:۰۷) هم گزینه‌ی خودش را دارد.
--}}
@props(['name' => null, 'value' => null, 'id' => null, 'nullable' => false, 'step' => 5, 'disabled' => false, 'label' => 'ساعت', 'selectClass' => '', 'selectStyle' => ''])
@php
    $raw = trim((string) $value);
    [$h, $m] = preg_match('/^(\d{1,2}):(\d{2})/', $raw, $mm) ? [str_pad($mm[1], 2, '0', STR_PAD_LEFT), $mm[2]] : ['', ''];
    $minutes = range(0, 59, max(1, (int) $step));
    if ($m !== '' && ! in_array((int) $m, $minutes, true)) {
        $minutes[] = (int) $m;
        sort($minutes);
    }
    $hidden = $h !== '' ? $h.':'.$m : ($nullable ? '' : '');
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1']) }} data-ui-time dir="ltr" style="direction:ltr;display:inline-flex;align-items:center;gap:.25rem">
    <select data-ui-time-hour aria-label="{{ $label }} — ساعت" class="{{ $selectClass }}" style="{{ $selectStyle }}" @disabled($disabled)>
        @if ($nullable || $h === '')<option value="" @selected($h === '')>—</option>@endif
        @foreach (range(0, 23) as $hour)
            @php $hh = str_pad((string) $hour, 2, '0', STR_PAD_LEFT); @endphp
            <option value="{{ $hh }}" @selected($hh === $h)>{{ \App\Support\Fa::digits($hh) }}</option>
        @endforeach
    </select>
    <span aria-hidden="true">:</span>
    <select data-ui-time-minute aria-label="{{ $label }} — دقیقه" class="{{ $selectClass }}" style="{{ $selectStyle }}" @disabled($disabled)>
        @if ($nullable || $m === '')<option value="" @selected($m === '')>—</option>@endif
        @foreach ($minutes as $minute)
            @php $mi = str_pad((string) $minute, 2, '0', STR_PAD_LEFT); @endphp
            <option value="{{ $mi }}" @selected($mi === $m)>{{ \App\Support\Fa::digits($mi) }}</option>
        @endforeach
    </select>
    <input type="hidden" @if ($name) name="{{ $name }}" @endif @if ($id) id="{{ $id }}" @endif value="{{ $hidden }}" @disabled($disabled)>
</span>
