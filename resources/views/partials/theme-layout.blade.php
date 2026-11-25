{{--
    ⭐ بسته‌ی «و»: چیدمان قالب سایت روی همه‌ی صفحه‌های سالن (خانه‌ی هر چیدمان نمای جدای خودش را دارد: salon-home/*).
    اینجا فقط چیزهایی که در همه‌ی صفحه‌ها عوض می‌شوند: فونت تیترها، گردی گوشه‌ها، شکل هدر، پهنای ستون و کارت‌ها.
    html[data-layout] را اسکریپت partials/mahru-theme پیش از نقاشی صفحه می‌گذارد. فونت‌ها روی همین سرور (بدون CDN).
--}}
{{-- بسته‌ی ۵الف: مشترک هر ۴ قالب پولی — پانویس تیره‌ی پالت (partials/site-footer/palette) و «طرح پالت» (x-palette-art) --}}
<style data-theme-layout-common>
.m-foot{background:rgb(var(--m-foot-bg));color:rgb(255 255 255 / .9)}
.m-foot__name{font-family:var(--m-display);font-size:19px;color:#fff}
.m-foot__mark{color:rgb(var(--m-foot-ac));display:inline-flex}
.m-foot__mark .text-m-gold{color:rgb(var(--m-foot-ac))}
.m-foot__h{display:block;color:#fff;margin-bottom:2px}
.m-foot__dim{color:rgb(255 255 255 / .72)}
.m-foot__btn{background:rgb(var(--m-foot-ac));color:var(--m-foot-on);border-radius:999px;padding:6px 16px;font-weight:700;font-size:13px}
.m-foot__ghost{border:1px solid rgb(255 255 255 / .3);color:rgb(255 255 255 / .85);border-radius:999px;padding:5px 14px;font-size:13px}
.m-foot__bar{border-top:1px solid rgb(255 255 255 / .12);color:rgb(255 255 255 / .6)}
.m-foot__legal [data-platform-legal-links]{margin:0;justify-content:flex-end}
.m-foot__legal a:hover{color:#fff}
.m-art{position:relative;overflow:hidden;background:linear-gradient(var(--m-art-angle,135deg),rgb(var(--m-art-a,var(--m-gold))),rgb(var(--m-art-b,var(--m-accent2))))}
.m-art>svg{position:absolute;inset:0;width:100%;height:100%;opacity:.14;pointer-events:none}
.m-art>img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}
</style>
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
{{-- بسته‌ی ۵ب — «نمایشگاه» (استودیو + گالری + کاتالوگ): تیترها با کوفی پررنگ، متن وزیرمتن، گوشه‌های نرم، سربرگ ساده با خط زیرین --}}
<link rel="preload" href="{{ asset('fonts/mahru/NotoKufiArabic-Bold.woff2') }}" as="font" type="font/woff2" crossorigin>
<style data-theme-layout="showcase">
@font-face{font-family:"MahruKufi";src:url("{{ asset('fonts/mahru/NotoKufiArabic-Bold.woff2') }}") format("woff2");font-weight:500 700;font-display:swap}
@font-face{font-family:"MahruKufi";src:url("{{ asset('fonts/mahru/NotoKufiArabic-Black.woff2') }}") format("woff2");font-weight:800 900;font-display:swap}
html[data-layout=showcase]{--m-r-lg:22px;--m-r-md:16px;--m-r-sm:14px;--m-display:"MahruKufi","Mahru",sans-serif}
html[data-layout=showcase] .font-serif-fa,html[data-layout=showcase] h1,html[data-layout=showcase] h2{font-family:var(--m-display);font-weight:700;letter-spacing:0}
html[data-layout=showcase] .rounded-xl{border-radius:16px}
html[data-layout=showcase] .rounded-2xl{border-radius:22px}
html[data-layout=showcase] .rounded-3xl{border-radius:30px}
html[data-layout=showcase] .btn-gold,html[data-layout=showcase] .m-btn,html[data-layout=showcase] .btn-glass,html[data-layout=showcase] .m-btn-ghost{border-radius:14px!important;font-weight:800;box-shadow:none}
html[data-layout=showcase] .m-card,html[data-layout=showcase] .auth-card,html[data-layout=showcase] .specialist-card,html[data-layout=showcase] .admin-card{border-radius:22px;box-shadow:none;border-color:var(--m-line2)}
html[data-layout=showcase] #main-navbar,html[data-layout=showcase] #main-navbar.scrolled{background:rgb(var(--m-bg) / .96);border-bottom:1px solid var(--m-line);box-shadow:none}
.sc-hdr .nav-link{color:rgb(var(--m-tx))}
.sc-hdr__name{font-family:var(--m-display);font-size:18px;font-weight:700;color:rgb(var(--m-tx))}
.sc-hdr__book{background:rgb(var(--m-gold));color:var(--m-on-gold);padding:9px 20px;border-radius:14px;font-weight:800}
.sc-hdr__book:hover{filter:brightness(1.06)}
</style>
@elseif ($layout === 'app')
{{-- بسته‌ی ۵الف — «اپ»: وزیرمتن پررنگ، گوشه‌های خیلی گرد، سربرگ ساده با خط زیرین و دکمه‌ی قرصی پررنگ --}}
<style data-theme-layout="app">
html[data-layout=app]{--m-r-lg:26px;--m-r-md:18px;--m-r-sm:14px;--m-display:"Mahru",sans-serif}
html[data-layout=app] .font-serif-fa,html[data-layout=app] h1,html[data-layout=app] h2{font-weight:900;letter-spacing:-.01em}
html[data-layout=app] .rounded-xl,html[data-layout=app] .rounded-2xl{border-radius:18px}
html[data-layout=app] .rounded-3xl{border-radius:26px}
html[data-layout=app] .m-card,html[data-layout=app] .auth-card,html[data-layout=app] .specialist-card,html[data-layout=app] .admin-card{border-radius:26px;box-shadow:none}
html[data-layout=app] .btn-gold,html[data-layout=app] .m-btn{box-shadow:none}
html[data-layout=app] #main-navbar,html[data-layout=app] #main-navbar.scrolled{background:rgb(var(--m-bg) / .94);backdrop-filter:blur(8px);border-bottom:1px solid var(--m-line);box-shadow:none}
.ap-hdr .nav-link{color:rgb(var(--m-tx))}
.ap-hdr__book{background:rgb(var(--m-gold));color:var(--m-on-gold);padding:10px 22px;border-radius:999px;font-weight:700}
.ap-hdr__book:hover{filter:brightness(1.06)}
</style>
@elseif ($layout === 'signature')
{{-- بسته‌ی ۵الف — «شاهکار»: تیترها با نسخ، متن وزیرمتن نازک، گوشه‌های تیز (فقط تراشه‌ها و دکمه‌ها گرد)، سربرگ وسط‌چین --}}
<link rel="preload" href="{{ asset('fonts/mahru/NotoNaskhArabic-Medium.woff2') }}" as="font" type="font/woff2" crossorigin>
<style data-theme-layout="signature">
@font-face{font-family:"MahruNaskh";src:url("{{ asset('fonts/mahru/NotoNaskhArabic-Medium.woff2') }}") format("woff2");font-weight:400 600;font-display:swap}
@font-face{font-family:"MahruNaskh";src:url("{{ asset('fonts/mahru/NotoNaskhArabic-Bold.woff2') }}") format("woff2");font-weight:700 900;font-display:swap}
@font-face{font-family:"Mahru";src:url("{{ asset('fonts/mahru/Vazirmatn-Light.woff2') }}") format("woff2");font-weight:300;font-display:swap}
html[data-layout=signature]{--m-r-lg:6px;--m-r-md:4px;--m-r-sm:12px;--m-display:"MahruNaskh","Mahru",serif}
html[data-layout=signature] .font-serif-fa,html[data-layout=signature] h1,html[data-layout=signature] h2{font-family:var(--m-display);font-weight:500;letter-spacing:0}
html[data-layout=signature] .rounded-2xl,html[data-layout=signature] .rounded-3xl{border-radius:6px}
html[data-layout=signature] .m-card,html[data-layout=signature] .auth-card,html[data-layout=signature] .specialist-card,html[data-layout=signature] .admin-card{border-radius:6px;box-shadow:none;border-color:var(--m-line2)}
html[data-layout=signature] #main-navbar,html[data-layout=signature] #main-navbar.scrolled{background:rgb(var(--m-bg) / .96);border-bottom:1px solid var(--m-line);box-shadow:none}
.sg-hdr .nav-link{color:rgb(var(--m-tx))}
.sg-name{font-family:var(--m-display);font-size:20px;color:rgb(var(--m-tx))}
.sg-hdr__book{border:1px solid var(--m-line2);color:rgb(var(--m-gold));padding:7px 18px;border-radius:999px;font-weight:600}
.sg-hdr__book:hover{background:rgb(var(--m-gold) / .1)}
</style>
@endif
