{{-- ⭐ آیکون‌ها و manifest برند پلتفرم «ماهرو» — فقط صفحه‌های خودِ پلتفرم (فروش، ثبت‌نام، سوپرادمین). نشان تازه ۲۰۲۶-۱۰-۱۴ (docs/brand/kit/head-snippet.html). --}}
<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="48x48">
<link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
<link rel="manifest" href="{{ asset('site.webmanifest') }}">
<meta name="theme-color" content="{{ config('brand.theme_color') }}">
<meta name="application-name" content="{{ config('brand.name') }}">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="{{ config('brand.name') }}">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="msapplication-TileColor" content="{{ config('brand.theme_color') }}">
