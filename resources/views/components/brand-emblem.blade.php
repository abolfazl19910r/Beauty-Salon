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

    ⭐ بسته‌ی ۵الف (۲۰۲۶-۱۱-۲۵): سالنی که لوگو ندارد و قالب پولی دارد، نشان پیش‌فرض را به رنگ اصلی پالت می‌گیرد (SVG درون‌خطی
    با currentColor روی --m-gold)، نه طلایی؛ «طلایی ماهرو» رایگان و سطح ثابت (ابرمدیر) همان تصویرها.
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

    $emblemTinted = ! $emblemLogo && $emblemSurface === 'theme' && isset($currentSalonName)
        && ! app(\App\Support\Themes\CurrentAppearance::class)->get()->isDefaultSite();

    $emblemShown = $emblemSurface === 'theme' ? ['light', 'dark'] : [$emblemSurface];
    // اگر هر دو تم دقیقاً همان تصویر و همان پلاک را می‌خواهند، یک <img> کافی است.
    if (count($emblemShown) === 2 && $emblemVariants['light'] === $emblemVariants['dark']) {
        $emblemShown = ['both'];
    }
@endphp
@if ($emblemTinted)
    <svg viewBox="140 140 232 232" role="img" aria-label="{{ $emblemAlt }}" data-emblem-tinted
         {{ $attributes->class(['text-m-gold'])->merge(['style' => 'aspect-ratio:1/1']) }}>
        <g fill="currentColor">
            <path d="M308.1 158A111 111 0 1 0 366.9 252.1A107.3 107.3 0 1 1 308.1 158Z"/><path d="M247.2 162.4A94 94 0 1 0 320.7 324.1A89 89 0 0 1 247.2 162.4Z"/>
            <circle cx="241" cy="259" r="13"/><circle cx="283" cy="259" r="13"/><circle cx="325" cy="259" r="13"/>
            <path d="M212 308A29 28 0 0 1 270 308V349A94 94 0 0 1 212 339.1Z"/><path d="M263.2 287.5A29 28 0 0 1 312 308V331.5A94 94 0 0 1 254 350V342.3A28 28 0 1 0 263.2 287.5Z"/>
            <path d="M305.2 287.5A29 28 0 0 1 344.6 287.4A94 94 0 0 1 331.7 311.7A28 28 0 0 0 305.2 287.5Z"/>
            <circle cx="249" cy="196" r="8"/><circle cx="321" cy="221" r="8"/><circle cx="314" cy="183" r="7"/>
        </g>
        <line x1="249" y1="196" x2="321" y2="221" stroke="currentColor" stroke-width="5" stroke-linecap="round"/>
    </svg>
@else
@foreach ($emblemShown as $emblemTone)
    @php $emblemKey = $emblemTone === 'both' ? 'light' : $emblemTone; @endphp
    <img src="{{ $emblemVariants[$emblemKey][0] }}" alt="{{ $emblemAlt }}"
         @if ($emblemStyle($emblemKey)) style="{{ $emblemStyle($emblemKey) }}" data-logo-plaque="{{ $emblemKey }}" @endif
         {{ $attributes->class(['object-contain', 'm-only-light' => $emblemTone === 'light' && count($emblemShown) === 2, 'm-only-dark' => $emblemTone === 'dark' && count($emblemShown) === 2]) }}>
@endforeach
@endif
