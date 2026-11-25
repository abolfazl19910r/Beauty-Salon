{{--
    بسته‌ی ۵الف — «طرح پالت» (قاعده‌ی ۳ و نکته‌ی ۴ بوم «۴ قالب ترکیبی»): جای عکس نبود هرگز قاب خاکستری نیست؛ گرادیان رنگ اصلی ← رنگ دوم
    با الگوی خطی کم‌رنگ. اگر عکس هست همان عکس در همان قاب. $variant (۰…۳) زاویه و ترتیب رنگ‌ها را عوض می‌کند تا کنار هم یکسان نباشند.
    محتوای درون (مثلاً برچسب) با عکس یا بی عکس روی قاب می‌آید. استفاده: <x-palette-art :src="$specialist->photo_url" :variant="$loop->index" class="h-56 rounded-t-full" alt="..." />
--}}
@props(['src' => null, 'variant' => 0, 'alt' => ''])
@php
    $artVariants = [
        ['135deg', 'var(--m-gold)', 'var(--m-accent2)', 135],
        ['160deg', 'var(--m-accent2)', 'var(--m-gold)', 160],
        ['200deg', 'var(--m-bg3)', 'var(--m-gold)', 200],
        ['120deg', 'var(--m-accent2)', 'var(--m-bg)', 120],
    ];
    [$artAngle, $artA, $artB, $artRotate] = $artVariants[((int) $variant % 4 + 4) % 4];
    $artId = 'mart'.substr(md5(uniqid('', true)), 0, 8);
@endphp
<div {{ $attributes->class(['m-art'])->merge(['style' => "--m-art-angle:$artAngle;--m-art-a:$artA;--m-art-b:$artB"]) }} data-palette-art>
    @if ($src)
        <img src="{{ $src }}" alt="{{ $alt }}" loading="lazy">
    @else
        <svg aria-hidden="true"><defs><pattern id="{{ $artId }}" width="22" height="22" patternUnits="userSpaceOnUse" patternTransform="rotate({{ $artRotate }})"><path d="M0 11h22" stroke="#FFFFFF" stroke-width="1"/></pattern></defs><rect width="100%" height="100%" fill="url(#{{ $artId }})"/></svg>
    @endif
    {{ $slot }}
</div>
