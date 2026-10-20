{{--
    ⭐ آیکون تب مرورگر (۲۰۲۶-۰۹-۲۵) — همه‌ی لایوت‌های سالن (مشتری، ورود/ثبت‌نام، پنل مدیر، پنل متخصص).
    سالنی که لوگو آپلود کرده → لوگوی خودش (تب مرورگر)؛ وگرنه آیکون ماهرو.

    بسته‌ی ۶ (PWA، آیفون: «افزودن به صفحه‌ی اصلی»): صفحه‌های سالن (مسیرهای salon.resolve؛ پارامتر salon_slug را middleware برمی‌دارد) manifest همان سالن و آیکون مربع ساخته‌شده از لوگو را می‌گیرند
    (لوگوی خام ممکن است مربع نباشد)؛ پنل متخصص manifest عمومی پنل. پنل مدیر PWA ندارد.
--}}
@php
    $__pwaRoute = request()->route();
    $__pwaMode = request()->routeIs('specialist.*') ? 'specialist'
        : (($__pwaRoute && in_array('salon.resolve', $__pwaRoute->middleware(), true) && app(\App\Support\CurrentSalon::class)->get()) ? 'salon' : null);
@endphp
@if (! empty($currentSalonLogoUrl))
    @php $faviconType = ['png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'webp' => 'image/webp'][strtolower(pathinfo(parse_url($currentSalonLogoUrl, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION))] ?? null; @endphp
    <link rel="icon" href="{{ $currentSalonLogoUrl }}" type="{{ $faviconType ?? 'image/png' }}">
    @if ($__pwaMode === null)
        <link rel="apple-touch-icon" href="{{ $currentSalonLogoUrl }}">
    @endif
@else
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="48x48">
    @if ($__pwaMode === null)
        <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    @endif
@endif
@if ($__pwaMode === 'salon')
    @php($__pwaSalon = app(\App\Support\CurrentSalon::class)->get())
    <link rel="manifest" href="{{ route('salon.pwa.manifest', ['salon_slug' => $__pwaSalon->slug]) }}" data-pwa-manifest="salon">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ route('salon.pwa.icon', ['salon_slug' => $__pwaSalon->slug, 'variant' => '180']) }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="{{ mb_substr($__pwaSalon->name, 0, 12) }}">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="theme-color" content="{{ \App\Http\Controllers\Pwa\PwaController::colors($__pwaSalon)['background'] }}">
@elseif ($__pwaMode === 'specialist')
    @php($__pwaSalon = app(\App\Support\CurrentSalon::class)->get())
    <link rel="manifest" href="{{ route('pwa.specialist-manifest') }}" data-pwa-manifest="specialist">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ $__pwaSalon ? route('salon.pwa.icon', ['salon_slug' => $__pwaSalon->slug, 'variant' => '180']) : asset('apple-touch-icon.png') }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="پنل متخصص">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="theme-color" content="#1A1410">
@endif
