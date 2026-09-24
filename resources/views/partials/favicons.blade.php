{{--
    ⭐ آیکون تب مرورگر (۲۰۲۶-۰۹-۲۵) — همه‌ی لایوت‌های سالن (مشتری، ورود/ثبت‌نام، پنل مدیر، پنل متخصص).
    سالنی که لوگو آپلود کرده → لوگوی خودش (تب مرورگر و آیکون صفحه‌ی اصلی موبایل)؛ وگرنه آیکون ماهرو.
--}}
@if (! empty($currentSalonLogoUrl))
    @php $faviconType = ['png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'webp' => 'image/webp'][strtolower(pathinfo(parse_url($currentSalonLogoUrl, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION))] ?? null; @endphp
    <link rel="icon" href="{{ $currentSalonLogoUrl }}" type="{{ $faviconType ?? 'image/png' }}">
    <link rel="apple-touch-icon" href="{{ $currentSalonLogoUrl }}">
@else
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
@endif
