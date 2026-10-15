{{--
    ⭐ بسته‌ی «و»: چیدمان قالب سایت روی همه‌ی صفحه‌های سالن (خانه‌ی هر چیدمان نمای جدای خودش را دارد: salon-home/*).
    اینجا فقط چیزهایی که در همه‌ی صفحه‌ها عوض می‌شوند: فونت تیترها، گردی گوشه‌ها، شکل هدر، پهنای ستون و کارت‌ها.
    html[data-layout] را اسکریپت partials/mahru-theme پیش از نقاشی صفحه می‌گذارد. فونت‌ها روی همین سرور (بدون CDN).
--}}
@if ($layout === 'majalle')
<link rel="preload" href="{{ asset('fonts/mahru/NotoNaskhArabic-Bold.woff2') }}" as="font" type="font/woff2" crossorigin>
<style data-theme-layout="majalle">
@font-face{font-family:"MahruNaskh";src:url("{{ asset('fonts/mahru/NotoNaskhArabic-Medium.woff2') }}") format("woff2");font-weight:400 600;font-display:swap}
@font-face{font-family:"MahruNaskh";src:url("{{ asset('fonts/mahru/NotoNaskhArabic-Bold.woff2') }}") format("woff2");font-weight:700 900;font-display:swap}
html[data-layout=majalle]{--m-r-lg:4px;--m-r-md:3px;--m-r-sm:2px;--m-display:"MahruNaskh","Mahru",serif}
html[data-layout=majalle] .font-serif-fa,html[data-layout=majalle] h1,html[data-layout=majalle] h2{font-family:var(--m-display);font-weight:700;letter-spacing:0}
html[data-layout=majalle] .rounded-lg,html[data-layout=majalle] .rounded-xl,html[data-layout=majalle] .rounded-2xl,html[data-layout=majalle] .rounded-3xl{border-radius:4px}
html[data-layout=majalle] .btn-gold,html[data-layout=majalle] .m-btn,html[data-layout=majalle] .btn-glass,html[data-layout=majalle] .m-btn-ghost{border-radius:3px!important}
html[data-layout=majalle] .m-card,html[data-layout=majalle] .auth-card,html[data-layout=majalle] .specialist-card,html[data-layout=majalle] .admin-card{box-shadow:none;border-color:var(--m-line2);border-radius:4px}
html[data-layout=majalle] #main-navbar,html[data-layout=majalle] #main-navbar.scrolled{background:rgb(var(--m-bg));border-bottom:1px solid rgb(var(--m-tx) / .85);box-shadow:none}
html[data-layout=majalle] #main-navbar .nav-link{letter-spacing:.02em}
</style>
@elseif ($layout === 'aram')
<style data-theme-layout="aram">
@font-face{font-family:"Mahru";src:url("{{ asset('fonts/mahru/Vazirmatn-ExtraLight.woff2') }}") format("woff2");font-weight:200;font-display:swap}
@font-face{font-family:"Mahru";src:url("{{ asset('fonts/mahru/Vazirmatn-Light.woff2') }}") format("woff2");font-weight:300;font-display:swap}
html[data-layout=aram]{--m-r-lg:16px;--m-r-md:12px;--m-r-sm:10px;--m-display:"Mahru",sans-serif}
html[data-layout=aram] .font-serif-fa,html[data-layout=aram] h1{font-weight:300;letter-spacing:-.01em}
html[data-layout=aram] h2{font-weight:500}
html[data-layout=aram] main.container{max-width:980px}
html[data-layout=aram] .rounded-2xl,html[data-layout=aram] .rounded-3xl{border-radius:16px}
html[data-layout=aram] .btn-gold,html[data-layout=aram] .m-btn,html[data-layout=aram] .btn-glass,html[data-layout=aram] .m-btn-ghost{border-radius:12px!important;font-weight:500;box-shadow:none}
html[data-layout=aram] .m-card,html[data-layout=aram] .auth-card,html[data-layout=aram] .specialist-card{box-shadow:none;border-color:var(--m-line2)}
html[data-layout=aram] #main-navbar,html[data-layout=aram] #main-navbar.scrolled{background:rgb(var(--m-bg));box-shadow:none;border-bottom:1px solid var(--m-line)}
</style>
@elseif ($layout === 'bento')
<link rel="preload" href="{{ asset('fonts/mahru/NotoKufiArabic-Bold.woff2') }}" as="font" type="font/woff2" crossorigin>
<style data-theme-layout="bento">
@font-face{font-family:"MahruKufi";src:url("{{ asset('fonts/mahru/NotoKufiArabic-Bold.woff2') }}") format("woff2");font-weight:500 700;font-display:swap}
@font-face{font-family:"MahruKufi";src:url("{{ asset('fonts/mahru/NotoKufiArabic-Black.woff2') }}") format("woff2");font-weight:800 900;font-display:swap}
html[data-layout=bento]{--m-r-lg:28px;--m-r-md:22px;--m-r-sm:16px;--m-display:"MahruKufi","Mahru",sans-serif}
html[data-layout=bento] .font-serif-fa,html[data-layout=bento] h1,html[data-layout=bento] h2{font-family:var(--m-display);font-weight:800;letter-spacing:0}
html[data-layout=bento] .rounded-xl,html[data-layout=bento] .rounded-2xl{border-radius:22px}
html[data-layout=bento] .rounded-3xl{border-radius:28px}
html[data-layout=bento] .m-card,html[data-layout=bento] .auth-card,html[data-layout=bento] .specialist-card,html[data-layout=bento] .admin-card{border-radius:28px;box-shadow:none}
html[data-layout=bento] #main-navbar,html[data-layout=bento] #main-navbar.scrolled{background:transparent;border:0;box-shadow:none}
html[data-layout=bento] #main-navbar > nav{background:rgb(var(--m-bg2));border:1px solid var(--m-line);border-radius:24px;margin-top:10px;height:4.25rem}
</style>
@elseif ($layout === 'vitrin')
<style data-theme-layout="vitrin">
html[data-layout=vitrin]{--m-r-lg:22px;--m-r-md:16px;--m-r-sm:12px;--m-display:"Mahru",sans-serif}
html[data-layout=vitrin] .font-serif-fa,html[data-layout=vitrin] h1{font-weight:900;letter-spacing:-.02em}
html[data-layout=vitrin] .rounded-2xl,html[data-layout=vitrin] .rounded-3xl{border-radius:22px}
html[data-layout=vitrin] #main-navbar,html[data-layout=vitrin] #main-navbar.scrolled{background:rgb(var(--m-bg2));border-bottom:1px solid var(--m-line);box-shadow:none}
</style>
@endif
