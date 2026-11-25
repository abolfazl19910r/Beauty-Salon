{{--
    ⭐ بسته‌ی «و»: چیدمان قالب سایت روی همه‌ی صفحه‌های سالن (خانه‌ی هر چیدمان نمای جدای خودش را دارد: salon-home/*).
    اینجا فقط چیزهایی که در همه‌ی صفحه‌ها عوض می‌شوند: فونت تیترها، گردی گوشه‌ها، شکل هدر، پهنای ستون و کارت‌ها.
    html[data-layout] را اسکریپت partials/mahru-theme پیش از نقاشی صفحه می‌گذارد. فونت‌ها روی همین سرور (بدون CDN).
--}}
@if ($layout === 'editorial')
<link rel="preload" href="{{ asset('fonts/mahru/NotoNaskhArabic-Bold.woff2') }}" as="font" type="font/woff2" crossorigin>
<style data-theme-layout="editorial">
@font-face{font-family:"MahruNaskh";src:url("{{ asset('fonts/mahru/NotoNaskhArabic-Medium.woff2') }}") format("woff2");font-weight:400 600;font-display:swap}
@font-face{font-family:"MahruNaskh";src:url("{{ asset('fonts/mahru/NotoNaskhArabic-Bold.woff2') }}") format("woff2");font-weight:700 900;font-display:swap}
html[data-layout=editorial]{--m-r-lg:4px;--m-r-md:3px;--m-r-sm:2px;--m-display:"MahruNaskh","Mahru",serif}
html[data-layout=editorial] .font-serif-fa,html[data-layout=editorial] h1,html[data-layout=editorial] h2{font-family:var(--m-display);font-weight:700;letter-spacing:0}
html[data-layout=editorial] .rounded-lg,html[data-layout=editorial] .rounded-xl,html[data-layout=editorial] .rounded-2xl,html[data-layout=editorial] .rounded-3xl{border-radius:4px}
html[data-layout=editorial] .btn-gold,html[data-layout=editorial] .m-btn,html[data-layout=editorial] .btn-glass,html[data-layout=editorial] .m-btn-ghost{border-radius:3px!important}
html[data-layout=editorial] .m-card,html[data-layout=editorial] .auth-card,html[data-layout=editorial] .specialist-card,html[data-layout=editorial] .admin-card{box-shadow:none;border-color:var(--m-line2);border-radius:4px}
html[data-layout=editorial] #main-navbar,html[data-layout=editorial] #main-navbar.scrolled{background:rgb(var(--m-bg));border-bottom:1px solid rgb(var(--m-tx) / .85);box-shadow:none}
html[data-layout=editorial] #main-navbar .nav-link{letter-spacing:.02em}
</style>
@elseif ($layout === 'showcase')
<style data-theme-layout="showcase">
@font-face{font-family:"Mahru";src:url("{{ asset('fonts/mahru/Vazirmatn-ExtraLight.woff2') }}") format("woff2");font-weight:200;font-display:swap}
@font-face{font-family:"Mahru";src:url("{{ asset('fonts/mahru/Vazirmatn-Light.woff2') }}") format("woff2");font-weight:300;font-display:swap}
html[data-layout=showcase]{--m-r-lg:16px;--m-r-md:12px;--m-r-sm:10px;--m-display:"Mahru",sans-serif}
html[data-layout=showcase] .font-serif-fa,html[data-layout=showcase] h1{font-weight:300;letter-spacing:-.01em}
html[data-layout=showcase] h2{font-weight:500}
html[data-layout=showcase] main.container{max-width:980px}
html[data-layout=showcase] .rounded-2xl,html[data-layout=showcase] .rounded-3xl{border-radius:16px}
html[data-layout=showcase] .btn-gold,html[data-layout=showcase] .m-btn,html[data-layout=showcase] .btn-glass,html[data-layout=showcase] .m-btn-ghost{border-radius:12px!important;font-weight:500;box-shadow:none}
html[data-layout=showcase] .m-card,html[data-layout=showcase] .auth-card,html[data-layout=showcase] .specialist-card{box-shadow:none;border-color:var(--m-line2)}
html[data-layout=showcase] #main-navbar,html[data-layout=showcase] #main-navbar.scrolled{background:rgb(var(--m-bg));box-shadow:none;border-bottom:1px solid var(--m-line)}
</style>
@elseif ($layout === 'app')
<link rel="preload" href="{{ asset('fonts/mahru/NotoKufiArabic-Bold.woff2') }}" as="font" type="font/woff2" crossorigin>
<style data-theme-layout="app">
@font-face{font-family:"MahruKufi";src:url("{{ asset('fonts/mahru/NotoKufiArabic-Bold.woff2') }}") format("woff2");font-weight:500 700;font-display:swap}
@font-face{font-family:"MahruKufi";src:url("{{ asset('fonts/mahru/NotoKufiArabic-Black.woff2') }}") format("woff2");font-weight:800 900;font-display:swap}
html[data-layout=app]{--m-r-lg:28px;--m-r-md:22px;--m-r-sm:16px;--m-display:"MahruKufi","Mahru",sans-serif}
html[data-layout=app] .font-serif-fa,html[data-layout=app] h1,html[data-layout=app] h2{font-family:var(--m-display);font-weight:800;letter-spacing:0}
html[data-layout=app] .rounded-xl,html[data-layout=app] .rounded-2xl{border-radius:22px}
html[data-layout=app] .rounded-3xl{border-radius:28px}
html[data-layout=app] .m-card,html[data-layout=app] .auth-card,html[data-layout=app] .specialist-card,html[data-layout=app] .admin-card{border-radius:28px;box-shadow:none}
html[data-layout=app] #main-navbar,html[data-layout=app] #main-navbar.scrolled{background:transparent;border:0;box-shadow:none}
html[data-layout=app] #main-navbar > nav{background:rgb(var(--m-bg2));border:1px solid var(--m-line);border-radius:24px;margin-top:10px;height:4.25rem}
</style>
@elseif ($layout === 'signature')
<style data-theme-layout="signature">
html[data-layout=signature]{--m-r-lg:22px;--m-r-md:16px;--m-r-sm:12px;--m-display:"Mahru",sans-serif}
html[data-layout=signature] .font-serif-fa,html[data-layout=signature] h1{font-weight:900;letter-spacing:-.02em}
html[data-layout=signature] .rounded-2xl,html[data-layout=signature] .rounded-3xl{border-radius:22px}
html[data-layout=signature] #main-navbar,html[data-layout=signature] #main-navbar.scrolled{background:rgb(var(--m-bg2));border-bottom:1px solid var(--m-line);box-shadow:none}
</style>
@endif
