{{--
    ⭐ نشان سالن / پلتفرم (۲۰۲۶-۰۹-۲۵) — جایگزین آیکون قلب قدیمی.
    سالنی که لوگو آپلود کرده → لوگوی خودش؛ بدون لوگو (یا بیرون از هر سالن) → نشان ماهرو.

    ⭐ بسته‌ی د — خوانایی لوگو (۲۰۲۶-۱۰-۱۵): رنگ لوگو هرگز عوض نمی‌شود؛ اگر روی پس‌زمینه‌ی یک تم کنتراست کم دارد
    (salons.logo_tone)، روی همان تم داخل پلاک گرد با پس‌زمینه‌ی مخالف می‌نشیند. لوگوی دوم سالن (پس‌زمینه‌ی تیره) روی تم تیره.
    پلاک با padding روی خود <img> کشیده می‌شود، پس اندازه‌ی جعبه و چیدمان هیچ صفحه‌ای عوض نمی‌شود.

    surface:
      theme (پیش‌فرض) — صفحه‌هایی که partials/mahru-theme دارند و با html[data-theme] تیره/روشن می‌شوند (دو تصویر، CSS یکی را نشان می‌دهد)؛
                        از بسته‌ی ه پنل‌های مدیر و متخصص هم.
      light / dark    — سطح ثابت (مثلاً پنل ابرمدیر). :light="true" همان surface="light" قدیمی است.
    استفاده: <x-brand-emblem class="h-12 w-auto" />   <x-brand-emblem class="w-9 h-9 rounded" pad="3px" />
--}}
@props(['light' => false, 'surface' => null, 'pad' => '6px'])
@php
    $emblemSurface = $surface ?: ($light ? 'light' : 'theme');
    $emblemName = $currentSalonName ?? config('brand.name');
    $emblemLogo = $currentSalonLogo ?? null;
    $emblemAlt = $emblemLogo ? 'لوگوی '.$emblemName : config('brand.name');

    // هر تم: [نشانی تصویر، پلاک؟]. بدون لوگوی سالن: نشان ماهروی همان تم، بدون پلاک.
    $emblemVariants = $emblemLogo
        ? ['light' => [$emblemLogo['light']['url'], $emblemLogo['light']['plaque']], 'dark' => [$emblemLogo['dark']['url'], $emblemLogo['dark']['plaque']]]
        : ['light' => [asset('brand/mahru-mark-on-light.svg'), false], 'dark' => [asset('brand/mahru-mark.svg'), false]];

    // پلاک تم روشن تیره است و پلاک تم تیره روشن (همان پس‌زمینه‌های --m-bg دو تم).
    $emblemPlaque = [
        'light' => 'background-color:#1C1712;padding:'.$pad.';border-radius:12px;',
        'dark' => 'background-color:#FBF8F2;padding:'.$pad.';border-radius:12px;',
    ];
    $emblemStyle = fn (string $tone) => $emblemVariants[$tone][1] ? $emblemPlaque[$tone] : '';

    $emblemShown = $emblemSurface === 'theme' ? ['light', 'dark'] : [$emblemSurface];
    // اگر هر دو تم دقیقاً همان تصویر و همان پلاک را می‌خواهند، یک <img> کافی است.
    if (count($emblemShown) === 2 && $emblemVariants['light'] === $emblemVariants['dark']) {
        $emblemShown = ['both'];
    }
@endphp
@foreach ($emblemShown as $emblemTone)
    @php $emblemKey = $emblemTone === 'both' ? 'light' : $emblemTone; @endphp
    <img src="{{ $emblemVariants[$emblemKey][0] }}" alt="{{ $emblemAlt }}"
         @if ($emblemStyle($emblemKey)) style="{{ $emblemStyle($emblemKey) }}" data-logo-plaque="{{ $emblemKey }}" @endif
         {{ $attributes->class(['object-contain', 'm-only-light' => $emblemTone === 'light' && count($emblemShown) === 2, 'm-only-dark' => $emblemTone === 'dark' && count($emblemShown) === 2]) }}>
@endforeach
