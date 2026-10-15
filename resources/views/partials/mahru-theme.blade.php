{{--
    ⭐ سیستم طراحی «ماهرو» برای صفحه‌های عمومی و مشتری (ادامه ۷، تأییدشده با پیش‌نمایش).
    همان قواعد لندینگ مرکزی (central/landing): تم تیره/روشن با کلید localStorage «mahru-theme»
    روی html[data-theme]، پیش‌فرض = تنظیم سیستم‌عامل، فونت Vazirmatn روی همین سرور (نه Google Fonts)،
    دکمه‌ی قرصی طلایی، کارت‌های گرد با خط نازک طلایی.

    رنگ‌ها کانال RGB هستند (--m-gold: 216 185 106) تا Tailwind (text-m-gold/40) و style="rgb(var(--m-gold) / .2)"
    هر دو شفافیت بگیرند. متغیرهای قدیمی --rasta-* هم به همین‌ها وصل شده‌اند تا صفحه‌ای جا نماند.
    --rasta-dark فقط «پس‌زمینه» است؛ متن روی دکمه‌ی طلایی همیشه --m-on-gold (ثابت در هر دو تم).

    پنل‌های مدیر و متخصص هم این partial را دارند (بسته‌ی ه)؛ پنل ابرمدیر نه. بسته‌ی «و»: پالت و چیدمان قالب، پایین همین فایل.
--}}
@php
    /*
     * ⭐ بسته‌ی «و»: قالب سالن جاری. صفحه‌های سالن (سایت، مشتری، متخصص، ورود) پالت و چیدمان «قالب سایت» را می‌گیرند؛
     * پنل مدیر با ['themeScope' => 'admin'] فقط پالت قالب پنل مدیر را (چیدمان پنل مدیر در layouts/admin است).
     */
    $__appearance = app(\App\Support\Themes\CurrentAppearance::class)->get();
    if (($themeScope ?? 'site') === 'admin') {
        $__themeTokens = \App\Support\Themes\ThemeCatalog::adminPaletteTokens($__appearance->admin);
        $__themeLayout = null;
    } else {
        $__themeTokens = $__appearance->palette ? \App\Support\Themes\ThemeCatalog::sitePaletteTokens($__appearance->palette) : null;
        $__themeLayout = $__appearance->isDefaultSite() ? null : $__appearance->layout;
    }
@endphp
<script>
(function(){var d=document.documentElement,t=null;try{t=new URLSearchParams(location.search).get('theme');if(t!=='light'&&t!=='dark')t=localStorage.getItem('mahru-theme')}catch(e){}
if(t!=='light'&&t!=='dark'){t=(window.matchMedia&&matchMedia('(prefers-color-scheme: light)').matches)?'light':'dark'}d.dataset.theme=t})();
</script>
@if ($__themeLayout)
<script>document.documentElement.dataset.layout=@json($__themeLayout);</script>
@endif
<link rel="preload" href="{{ asset('fonts/mahru/Vazirmatn-Regular.woff2') }}" as="font" type="font/woff2" crossorigin>
<style>
@font-face{font-family:"Mahru";src:url("{{ asset('fonts/mahru/Vazirmatn-Regular.woff2') }}") format("woff2");font-weight:400;font-display:swap}
@font-face{font-family:"Mahru";src:url("{{ asset('fonts/mahru/Vazirmatn-Medium.woff2') }}") format("woff2");font-weight:500;font-display:swap}
@font-face{font-family:"Mahru";src:url("{{ asset('fonts/mahru/Vazirmatn-SemiBold.woff2') }}") format("woff2");font-weight:600;font-display:swap}
@font-face{font-family:"Mahru";src:url("{{ asset('fonts/mahru/Vazirmatn-Bold.woff2') }}") format("woff2");font-weight:700;font-display:swap}
@font-face{font-family:"Mahru";src:url("{{ asset('fonts/mahru/Vazirmatn-ExtraBold.woff2') }}") format("woff2");font-weight:800 900;font-display:swap}

:root,html[data-theme=dark]{
    color-scheme:dark;
    --m-bg:21 17 14;--m-bg2:28 23 18;--m-bg3:37 30 23;--m-card:37 30 23;
    --m-tx:244 237 224;--m-mu:179 164 140;
    --m-gold:216 185 106;--m-gold2:227 201 133;
    --m-ok:156 197 143;--m-bad:216 138 120;--m-info:143 180 205;--m-warn:232 184 92;
    --m-line:rgba(235,214,155,.12);--m-line2:rgba(235,214,155,.28);
    --m-btn:linear-gradient(135deg,#F0DDA6 0%,#D8B96A 45%,#A67F2E 100%);
    --m-tgrad:linear-gradient(135deg,#F0DDA6 0%,#D8B96A 45%,#B38A39 100%);
    --m-hdr:rgba(21,17,14,.72);
    --m-sh-card:0 40px 80px -36px rgba(0,0,0,.85);
    --m-sh-soft:0 22px 44px -18px rgba(0,0,0,.8);
}
html[data-theme=light]{
    color-scheme:light;
    --m-bg:251 248 242;--m-bg2:255 255 255;--m-bg3:245 239 227;--m-card:255 255 255;
    --m-tx:33 25 15;--m-mu:107 93 72;
    --m-gold:138 106 34;--m-gold2:107 78 24;
    --m-ok:63 122 52;--m-bad:169 70 31;--m-info:42 98 138;--m-warn:150 96 12;
    --m-line:rgba(122,90,28,.13);--m-line2:rgba(122,90,28,.28);
    --m-btn:linear-gradient(135deg,#EBD69B 0%,#D2B060 45%,#B38A39 100%);
    --m-tgrad:linear-gradient(135deg,#A67F2E 0%,#8A6A22 50%,#6B4E18 100%);
    --m-hdr:rgba(251,248,242,.82);
    --m-sh-card:0 30px 70px -34px rgba(92,68,22,.3);
    --m-sh-soft:0 18px 40px -20px rgba(92,68,22,.32);
}
:root{
    --m-on-gold:#221A0C;
    --m-r-lg:28px;--m-r-md:20px;--m-r-sm:14px;
    /* متغیرهای قدیمی (رستا) → توکن‌های ماهرو */
    --rasta-gold:rgb(var(--m-gold));
    --rasta-gold-light:rgb(var(--m-gold2));
    --rasta-cream:rgb(var(--m-tx));
    --rasta-dark:rgb(var(--m-bg));
    --rasta-brown:rgb(var(--m-card));
    --rasta-bg:rgb(var(--m-bg));
}

html{background:rgb(var(--m-bg))}
body{
    background:rgb(var(--m-bg));color:rgb(var(--m-tx));
    font-family:"Mahru","Vazirmatn",system-ui,sans-serif;
    -webkit-font-smoothing:antialiased;
    transition:background-color .35s ease,color .35s ease;
}
/* عنوان‌ها: همان Vazirmatn پررنگ لندینگ به‌جای Noto Naskh از Google Fonts */
.font-serif-fa{font-family:"Mahru","Vazirmatn",system-ui,sans-serif;font-weight:800;letter-spacing:-.01em}
.persian-number{font-feature-settings:"ss02"}
::selection{background:rgb(var(--m-gold) / .3)}

::-webkit-scrollbar{width:8px;height:8px}
::-webkit-scrollbar-track{background:rgb(var(--m-bg2))}
::-webkit-scrollbar-thumb{background:rgb(var(--m-gold) / .45);border-radius:10px}

/* بسته‌ی د: تصویری که فقط در یک تم دیده می‌شود (لوگوی سالن در x-brand-emblem) */
html[data-theme=dark] .m-only-light,html:not([data-theme]) .m-only-light{display:none!important}
html[data-theme=light] .m-only-dark{display:none!important}

/* متن طلایی گرادیانی مثل تیتر لندینگ */
.m-gold-text{background:var(--m-tgrad);-webkit-background-clip:text;background-clip:text;color:transparent}

/* دکمه‌ها: قرصی، گرادیان طلایی، متن تیره‌ی ثابت */
.btn-gold,.m-btn{
    background:var(--m-btn)!important;color:var(--m-on-gold)!important;border:0;border-radius:999px;
    font-weight:700;box-shadow:0 10px 24px -12px rgba(166,127,46,.7);
    transition:transform .3s cubic-bezier(.2,.8,.2,1),box-shadow .3s ease,filter .3s ease;
}
.btn-gold:hover,.m-btn:hover{transform:translateY(-2px);box-shadow:0 16px 30px -12px rgba(166,127,46,.8);filter:saturate(1.08)}
.btn-glass,.m-btn-ghost{
    background:rgb(var(--m-tx) / .05);border:1px solid var(--m-line2);border-radius:999px;color:rgb(var(--m-tx));
    backdrop-filter:blur(8px);transition:background .3s ease,transform .3s ease;
}
.btn-glass:hover,.m-btn-ghost:hover{background:rgb(var(--m-tx) / .1)}

/* کارت‌ها و فرم‌ها */
.auth-card,.m-card{
    background:rgb(var(--m-bg2));border:1px solid var(--m-line);border-radius:var(--m-r-lg);box-shadow:var(--m-sh-card);
}
.input-gold{
    background:rgb(var(--m-bg));border:1px solid var(--m-line2);color:rgb(var(--m-tx));border-radius:var(--m-r-sm);
}
.input-gold::placeholder{color:rgb(var(--m-mu) / .7)}
.input-gold:focus{border-color:rgb(var(--m-gold));box-shadow:0 0 0 3px rgb(var(--m-gold) / .2);outline:none}

/* ورودی‌های فرم در هر دو تم خوانا بمانند (پلاگین forms پس‌زمینه‌ی سفید می‌دهد) */
input[type=text],input[type=email],input[type=tel],input[type=password],input[type=number],input[type=search],input[type=date],input[type=time],input[type=url],select,textarea{color:rgb(var(--m-tx))}
input::placeholder,textarea::placeholder{color:rgb(var(--m-mu) / .7)}
select option{background:rgb(var(--m-bg2));color:rgb(var(--m-tx))}

/* هدر شیشه‌ای مثل لندینگ */
#main-navbar{
    backdrop-filter:blur(16px) saturate(1.2);-webkit-backdrop-filter:blur(16px) saturate(1.2);
    background:transparent;border-bottom:1px solid transparent;
    transition:background-color .4s ease,border-color .4s ease,box-shadow .4s ease;
}
#main-navbar.scrolled{background:var(--m-hdr);border-bottom-color:var(--m-line);box-shadow:var(--m-sh-soft)}
.nav-link{color:rgb(var(--m-mu));transition:color .25s ease}
.nav-link:hover{color:rgb(var(--m-tx))}

/* دکمه‌ی تغییر تم */
.m-theme-btn{
    display:inline-flex;align-items:center;justify-content:center;width:38px;height:38px;border-radius:999px;
    border:1px solid var(--m-line2);color:rgb(var(--m-gold2));background:rgb(var(--m-tx) / .04);
    transition:background .25s ease,transform .25s ease;
}
.m-theme-btn{width:38px!important;height:38px!important;padding:0!important;flex:0 0 auto}
.m-theme-btn:hover{background:rgb(var(--m-tx) / .09);transform:rotate(-12deg)}
.m-theme-btn svg{width:18px;height:18px}
html[data-theme=dark] .m-theme-btn .m-sun{display:none}
html[data-theme=light] .m-theme-btn .m-moon{display:none}

/* صفحه‌بندی پیش‌فرض لاراول (vendor/pagination: bg-white ثابت) */
nav[role=navigation] a.relative,nav[role=navigation] span.relative,nav[role=navigation] span[aria-disabled] span{background:rgb(var(--m-card))!important;color:rgb(var(--m-tx))!important;border-color:var(--m-line2)!important}
nav[role=navigation] span[aria-current=page] span{background:var(--m-btn)!important;color:var(--m-on-gold)!important;border-color:transparent!important}
nav[role=navigation] p{color:rgb(var(--m-mu))!important}

/* لوگوی پلتفرم: نسخه‌ی تیره/روشن */
.m-logo-light{display:none!important}
html[data-theme=light] .m-logo-dark{display:none!important}
html[data-theme=light] .m-logo-light{display:block!important}

/* سایه‌ی کارت هاور (قبلاً سیاه ثابت؛ در تم روشن کثیف دیده می‌شد) */
.hover-shadow{transition:box-shadow .3s,transform .3s}
.hover-shadow:hover{box-shadow:var(--m-sh-soft);transform:translateY(-2px)}
.fade-in{animation:mFadeIn .5s ease-in-out}
@keyframes mFadeIn{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}
@media (prefers-reduced-motion:reduce){*,*::before,*::after{animation-duration:.01ms!important;transition-duration:.01ms!important}}
</style>
@if ($__themeTokens)
    @include('partials.theme-tokens', ['tokens' => $__themeTokens])
@endif
@if ($__themeLayout)
    @include('partials.theme-layout', ['layout' => $__themeLayout])
@endif
