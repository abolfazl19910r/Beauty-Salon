{{--
    ⭐ نشان سالن / پلتفرم (۲۰۲۶-۰۹-۲۵) — جایگزین آیکون قلب قدیمی.
    سالنی که لوگو آپلود کرده → لوگوی خودش؛ بدون لوگو (یا بیرون از هر سالن) → نشان ماهرو.
    استفاده: <x-brand-emblem class="h-12 w-auto" />   (ورودی اختیاری: light=true برای زمینه‌ی روشن)
--}}
@props(['light' => false])
@php
    $emblemLogo = $currentSalonLogoUrl ?? null;
    $emblemName = $currentSalonName ?? config('brand.name');
@endphp
@if ($emblemLogo)
    <img src="{{ $emblemLogo }}" alt="لوگوی {{ $emblemName }}" {{ $attributes->merge(['class' => 'object-contain']) }}>
@else
    <img src="{{ asset($light ? 'brand/mahru-mark-on-light.svg' : 'brand/mahru-mark.svg') }}" alt="{{ config('brand.name') }}" {{ $attributes->merge(['class' => 'object-contain']) }}>
@endif
