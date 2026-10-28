{{-- بسته‌ی 5ه، تصمیم ۱۶: برچسب «بدقول: N بار نیامد در M روز» برای کادر. ورودی: label (از NoShowRecord) --}}
@props(['label' => null])
@if ($label)
    <span {{ $attributes->merge(['class' => 'inline-block text-xs font-bold px-2 py-0.5 rounded-md']) }}
          style="background: rgb(var(--m-bad) / .14); color: rgb(var(--m-bad));" data-no-show-badge>{{ $label }}</span>
@endif
