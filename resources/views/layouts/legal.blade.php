{{--
    صفحه‌های حقوقی پلتفرم (بسته‌ی مجوز 5د): حریم خصوصی، قوانین و شرایط، تماس با ما — روی دامنه‌ی مرکزی.
    ورودی: $platform (هویت و تماس از تنظیمات پلتفرم)، $versions، $draft. بخش‌ها: title، content.
--}}
<!DOCTYPE html>
<html lang="fa" dir="rtl" class="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.favicons')
    <title>@yield('title') — {{ $platform['brand'] }}</title>
    <link rel="canonical" href="{{ \App\Support\CentralUrl::to(request()->path()) }}">
    @vite(['resources/css/app.css'])
    @include('partials.mahru-theme')
    <style>
        .legal h2 { font-weight: 800; font-size: 1.15rem; margin: 2rem 0 .75rem; color: rgb(var(--m-gold2)); }
        .legal h3 { font-weight: 700; margin: 1.25rem 0 .5rem; }
        .legal p, .legal li { line-height: 2; color: rgb(var(--m-tx) / .85); }
        .legal ul { list-style: disc; padding-right: 1.5rem; margin: .5rem 0; }
        .legal ol { list-style: decimal; padding-right: 1.5rem; margin: .5rem 0; }
        .legal a { text-decoration: underline; }
    </style>
</head>
<body class="font-vazir antialiased min-h-screen flex flex-col bg-m-bg text-m-tx" dir="rtl">
    <header class="border-b border-m-gold/10">
        <div class="max-w-3xl mx-auto px-4 py-4 flex flex-wrap items-center justify-between gap-3">
            <a href="{{ \App\Support\CentralUrl::to('/') }}" class="font-serif-fa text-xl font-bold text-m-gold2">{{ $platform['brand'] }}</a>
            <nav class="flex flex-wrap gap-4 text-sm" aria-label="صفحه‌های حقوقی">
                @foreach (['legal.terms' => 'قوانین و شرایط', 'legal.privacy' => 'حریم خصوصی', 'legal.contact' => 'تماس با ما'] as $name => $label)
                    <a href="{{ \App\Support\CentralUrl::route($name) }}" @if (request()->routeIs($name)) aria-current="page" class="text-m-gold2 font-bold" @else class="text-m-tx/70 hover:text-m-gold2" @endif>{{ $label }}</a>
                @endforeach
            </nav>
        </div>
    </header>

    <main class="flex-1 max-w-3xl w-full mx-auto px-4 py-8">
        @if ($draft)
            <div class="mb-6 p-4 rounded-xl border border-m-warn/40 bg-m-warn/10 text-sm leading-7" data-legal-draft role="note">
                <b>پیش‌نویس — نیاز به بازبینی حقوقی.</b> این متن هنوز به تأیید حقوق‌دان نرسیده و ممکن است تغییر کند.
            </div>
        @endif

        <h1 class="text-2xl font-extrabold mb-2">@yield('title')</h1>
        @hasSection('version')
            <p class="text-xs text-m-tx/50 mb-6">نسخه‌ی <span dir="ltr" class="persian-number">@yield('version')</span></p>
        @endif

        <article class="legal">
            @yield('content')
        </article>
    </main>

    <footer class="border-t border-m-gold/10 py-6 text-center text-xs text-m-tx/50">
        © {{ to_persian_num((string) \Morilog\Jalali\Jalalian::now()->getYear()) }} {{ $platform['legal_name'] ?: $platform['brand'] }}
    </footer>
</body>
</html>
