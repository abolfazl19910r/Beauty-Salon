{{--
    متای سئو (۲۰۲۶-۱۱-۲۳) — canonical، description، Open Graph، Twitter و JSON-LD فقط روی صفحه‌های ایندکس‌پذیر (config/seo.php)؛
    بقیه «robots: noindex» (کنار هدر X-Robots-Tag میان‌افزار AddRobotsTag).
    ورودی‌ها (همه اختیاری): $seoTitle، $seoDescription، $seoImage، $seoType (پیش‌فرض website)، $seoJsonLd (آرایه‌ای از آرایه‌ها).
--}}
@if (\App\Support\Seo\Seo::indexable(request()))
    @php
        $seoUrl = \App\Support\Seo\Seo::canonical(request());
        $seoDesc = filled($seoDescription ?? null) ? \App\Support\Seo\StructuredData::clean($seoDescription) : null;
        $seoImg = $seoImage ?? asset('brand/mahru-og.png');
    @endphp
    <link rel="canonical" href="{{ $seoUrl }}">
    @if ($seoDesc)
        <meta name="description" content="{{ $seoDesc }}">
    @endif
    <meta property="og:locale" content="fa_IR">
    <meta property="og:type" content="{{ $seoType ?? 'website' }}">
    <meta property="og:site_name" content="{{ config('brand.name') }}">
    @if (filled($seoTitle ?? null))
        <meta property="og:title" content="{{ $seoTitle }}">
    @endif
    @if ($seoDesc)
        <meta property="og:description" content="{{ $seoDesc }}">
    @endif
    <meta property="og:url" content="{{ $seoUrl }}">
    <meta property="og:image" content="{{ $seoImg }}">
    @unless ($seoImage ?? null)
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
    @endunless
    <meta name="twitter:card" content="{{ ($seoImage ?? null) ? 'summary' : 'summary_large_image' }}">
    @foreach (($seoJsonLd ?? []) as $seoLd)
        <script type="application/ld+json" @nonce>{!! json_encode($seoLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
    @endforeach
@else
    <meta name="robots" content="noindex, nofollow">
@endif
