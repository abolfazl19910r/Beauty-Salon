{{-- صفحه‌ی اصلی/فروش دامنه‌ی مرکزی — لندینگ تازه‌ی ماهرو (۲۰۲۶-۱۰-۱۴؛ docs/brand/kit، MAHRU_UI_PROMPT). ظاهر از فایل طراح؛
     قیمت پلن‌ها، دوره‌ی رایگان، سقف متخصص و پیامک از CentralLandingController (config/billing)، دکمه‌ها به ثبت‌نام سالن، فرم دمو
     به demo-requests.store. ادعاهایی که نرم‌افزار ندارد (چندشعبه، پیگیری خودکار، دوره‌ی درمانی) برداشته شده‌اند. نام فارسی «ماهرو». --}}
<!doctype html>
<html lang="fa" dir="rtl" data-theme="dark" data-default-theme="system">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>{{ config('brand.name') }} | نرم‌افزار نوبت‌دهی و مدیریت سالن زیبایی</title>
<meta name="description" content="ماهرو؛ رزرو آنلاین ۲۴ ساعته، تقویم پرسنل، یادآوری پیامکی، پرداخت بیعانه، باشگاه مشتریان و گزارش مالی — همه در یک پنل برای سالن‌های زیبایی.">
<meta name="theme-color" content="#15110E" id="metaTheme">
<meta name="color-scheme" content="dark light">
<meta property="og:title" content="ماهرو | نوبت‌دهی آنلاین سالن زیبایی">
<meta property="og:description" content="سالن شما همیشه پر از نوبت؛ بدون یک تماس بی‌پاسخ.">
<meta property="og:type" content="website">
<meta property="og:url" content="{{ route('central.home') }}">
<meta property="og:image" content="{{ asset('brand/mahru-og.png') }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:site_name" content="{{ config('brand.name') }}">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:image" content="{{ asset('brand/mahru-og.png') }}">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta property="og:locale" content="fa_IR">
<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="48x48">
<link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
<link rel="manifest" href="{{ asset('site.webmanifest') }}">
<meta name="application-name" content="{{ config('brand.name') }}">
<meta name="apple-mobile-web-app-title" content="{{ config('brand.name') }}">
<script>
/* تم پیش از رندر — ترتیب: ?theme= ← انتخاب کاربر ← data-default-theme ← تنظیم سیستم */
(function(){var d=document.documentElement,t=null;try{t=new URLSearchParams(location.search).get('theme');if(t!=='light'&&t!=='dark')t=localStorage.getItem('mahru-theme')}catch(e){}
if(t!=='light'&&t!=='dark'){var def=d.getAttribute('data-default-theme');t=(def==='light'||def==='dark')?def:(matchMedia('(prefers-color-scheme: light)').matches?'light':'dark')}d.dataset.theme=t})();
</script>
{{-- فونت‌ها روی همین سرور (بدون Google Fonts؛ در ایران کند/بسته می‌شود) — public/fonts/mahru، مجوز OFL --}}
<link rel="preload" href="{{ asset('fonts/mahru/Vazirmatn-Regular.woff2') }}" as="font" type="font/woff2" crossorigin>
<style>
@font-face{font-family:"Vazirmatn";src:url("{{ asset('fonts/mahru/Vazirmatn-Regular.woff2') }}") format("woff2");font-weight:400;font-style:normal;font-display:swap}
@font-face{font-family:"Vazirmatn";src:url("{{ asset('fonts/mahru/Vazirmatn-Medium.woff2') }}") format("woff2");font-weight:500;font-style:normal;font-display:swap}
@font-face{font-family:"Vazirmatn";src:url("{{ asset('fonts/mahru/Vazirmatn-SemiBold.woff2') }}") format("woff2");font-weight:600;font-style:normal;font-display:swap}
@font-face{font-family:"Vazirmatn";src:url("{{ asset('fonts/mahru/Vazirmatn-Bold.woff2') }}") format("woff2");font-weight:700;font-style:normal;font-display:swap}
@font-face{font-family:"Vazirmatn";src:url("{{ asset('fonts/mahru/Vazirmatn-ExtraBold.woff2') }}") format("woff2");font-weight:800;font-style:normal;font-display:swap}
@font-face{font-family:"Vazirmatn";src:url("{{ asset('fonts/mahru/Vazirmatn-Black.woff2') }}") format("woff2");font-weight:900;font-style:normal;font-display:swap}
@font-face{font-family:"Sora";src:url("{{ asset('fonts/mahru/Sora-400.woff2') }}") format("woff2");font-weight:400;font-style:normal;font-display:swap}
@font-face{font-family:"Sora";src:url("{{ asset('fonts/mahru/Sora-600.woff2') }}") format("woff2");font-weight:600;font-style:normal;font-display:swap}
@font-face{font-family:"Sora";src:url("{{ asset('fonts/mahru/Sora-700.woff2') }}") format("woff2");font-weight:700;font-style:normal;font-display:swap}
</style>
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"SoftwareApplication","name":"ماهرو","alternateName":"Mahru","applicationCategory":"BusinessApplication","operatingSystem":"Web","inLanguage":"fa","offers":{"@type":"AggregateOffer","lowPrice":"{{ $plans[0]['price'] ?? 0 }}","priceCurrency":"IRT"},"description":"نرم‌افزار نوبت‌دهی و مدیریت سالن‌های زیبایی"}
</script>
<style>
/* =========================================================
   TOKENS
   ========================================================= */
:root{
--ease:cubic-bezier(.2,.8,.2,1);
--r-lg:28px;--r-md:20px;--r-sm:14px;
--on-gold:#221A0C;
}
html[data-theme="dark"]{
--bg:#15110E;--bg2:#1C1712;--bg3:#251E17;--bg4:#2E251C;
--sf:rgba(255,255,255,.035);--sf2:rgba(255,255,255,.06);
--tx:#F4EDE0;--mu:#B3A48C;--mu2:#8C7E68;
--ln:rgba(235,214,155,.12);--ln2:rgba(235,214,155,.28);
--gl:#E3C985;
--btn:linear-gradient(135deg,#F0DDA6 0%,#D8B96A 45%,#A67F2E 100%);
--tgrad:linear-gradient(135deg,#F0DDA6 0%,#D8B96A 45%,#B38A39 100%);
--lg1:#EBD69B;--lg2:#A67F2E;
--hdr:rgba(21,17,14,.72);--glass:rgba(37,30,23,.86);
--tint:rgba(166,127,46,.16);--tint2:rgba(166,127,46,.28);
--bad:#D88A78;--ok:#9CC58F;
--sh:0 60px 110px -40px rgba(0,0,0,.95);
--sh-card:0 40px 80px -36px rgba(0,0,0,.85);
--sh-soft:0 22px 44px -18px rgba(0,0,0,.8);
--aur:.26;--grain:.045;--glow:.38;
}
html[data-theme="light"]{
--bg:#FBF8F2;--bg2:#FFFFFF;--bg3:#F5EFE3;--bg4:#EDE4D2;
--sf:rgba(122,90,28,.04);--sf2:rgba(122,90,28,.07);
--tx:#21190F;--mu:#6B5D48;--mu2:#8A7B63;
--ln:rgba(122,90,28,.13);--ln2:rgba(122,90,28,.28);
--gl:#8A6A22;
--btn:linear-gradient(135deg,#EBD69B 0%,#D2B060 45%,#B38A39 100%);
--tgrad:linear-gradient(135deg,#A67F2E 0%,#8A6A22 50%,#6B4E18 100%);
--lg1:#C9A455;--lg2:#7A5A1C;
--hdr:rgba(251,248,242,.8);--glass:rgba(255,255,255,.9);
--tint:rgba(201,164,85,.16);--tint2:rgba(201,164,85,.3);
--bad:#A9461F;--ok:#3F7A34;
--sh:0 50px 100px -44px rgba(92,68,22,.38);
--sh-card:0 30px 70px -34px rgba(92,68,22,.3);
--sh-soft:0 18px 40px -20px rgba(92,68,22,.32);
--aur:.14;--grain:.03;--glow:.2;
}
/* =========================================================
   BASE
   ========================================================= */
*,*:before,*:after{box-sizing:border-box;margin:0}
html{scroll-behavior:smooth;scroll-padding-top:90px;-webkit-text-size-adjust:100%;overflow-x:clip}
body{font-family:"Vazirmatn",Tahoma,"Sora",system-ui,sans-serif;background:var(--bg);color:var(--tx);line-height:1.85;overflow-x:hidden;-webkit-font-smoothing:antialiased;transition:background-color .5s,color .5s}
a{color:inherit;text-decoration:none}
button,input,select{font:inherit;color:inherit}
img,svg{display:block}
.wrap{max-width:1200px;margin:0 auto;padding:0 24px;position:relative}
h1,h2,h3,h4{line-height:1.4;font-weight:800}
.gold{background:var(--tgrad);-webkit-background-clip:text;background-clip:text;color:transparent}
.num,.lat{font-family:"Sora","Vazirmatn",sans-serif}
.sr{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0);white-space:nowrap}
:focus-visible{outline:2px solid var(--gl);outline-offset:3px;border-radius:8px}
::selection{background:var(--tint2)}
.skip{position:fixed;top:-60px;right:16px;z-index:200;background:var(--btn);color:var(--on-gold);padding:10px 18px;border-radius:12px;font-weight:700;transition:top .2s}
.skip:focus{top:12px}
/* ambient */
#pg{position:fixed;top:0;inset-inline:0;height:3px;background:var(--btn);transform:scaleX(0);transform-origin:right;z-index:120}
.aur{position:fixed;inset:0;z-index:-1;overflow:hidden;pointer-events:none}
.aur i{position:absolute;width:600px;height:600px;border-radius:50%;filter:blur(130px);opacity:var(--aur);background:#A67F2E;animation:fl 26s ease-in-out infinite alternate}
.aur i:nth-child(1){top:-180px;right:-120px}
.aur i:nth-child(2){top:48%;left:-200px;background:#7a5a1c;animation-delay:-9s}
.aur i:nth-child(3){bottom:-240px;right:28%;background:#c9a455;opacity:calc(var(--aur)*.55);animation-delay:-15s}
@keyframes fl{to{transform:translate(-90px,70px) scale(1.18)}}
.grain{position:fixed;inset:0;z-index:0;pointer-events:none;opacity:var(--grain);background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='120' height='120'%3E%3Cfilter id='n'%3E%3CfeTurbulence baseFrequency='0.9' numOctaves='2'/%3E%3C/filter%3E%3Crect width='120' height='120' filter='url(%23n)' opacity='.6'/%3E%3C/svg%3E")}
main,footer,header{position:relative;z-index:1}
/* buttons */
.btn{display:inline-flex;align-items:center;justify-content:center;gap:10px;padding:15px 28px;border-radius:999px;font-weight:700;font-size:15px;line-height:1;border:1px solid transparent;cursor:pointer;transition:transform .3s var(--ease),box-shadow .3s,border-color .3s,background-color .3s;white-space:nowrap}
.btn svg{width:18px;height:18px}
.btn-gold{background:var(--btn);color:var(--on-gold);box-shadow:0 14px 34px -14px rgba(166,127,46,.8),inset 0 1px 0 rgba(255,255,255,.35)}
.btn-gold:hover{transform:translateY(-2px);box-shadow:0 22px 46px -14px rgba(166,127,46,.75),inset 0 1px 0 rgba(255,255,255,.35)}
.btn-line{border-color:var(--ln2);background:var(--sf);color:var(--tx);backdrop-filter:blur(8px)}
.btn-line:hover{border-color:var(--gl);transform:translateY(-2px)}
.btn-s{padding:11px 20px;font-size:14px}
.btn-block{width:100%}
.eyebrow{display:inline-flex;gap:9px;align-items:center;font-size:13px;font-weight:600;color:var(--gl);border:1px solid var(--ln2);border-radius:999px;padding:6px 15px;margin-bottom:22px;background:var(--sf)}
.eyebrow:before{content:"";width:7px;height:7px;border-radius:50%;background:currentColor;box-shadow:0 0 0 0 currentColor;animation:pu 2s infinite}
@keyframes pu{0%{box-shadow:0 0 0 0 rgba(201,164,85,.6)}70%,100%{box-shadow:0 0 0 8px rgba(201,164,85,0)}}
/* =========================================================
   HEADER
   ========================================================= */
header{position:sticky;top:0;z-index:60;backdrop-filter:blur(18px) saturate(140%);-webkit-backdrop-filter:blur(18px) saturate(140%);background:var(--hdr);border-bottom:1px solid transparent;transition:border-color .3s,background-color .5s}
header.s{border-color:var(--ln)}
.nav{display:flex;align-items:center;justify-content:space-between;height:74px;gap:16px;transition:height .3s}
header.s .nav{height:62px}
.brand{display:flex;align-items:center;gap:10px;font-weight:900;font-size:21px;letter-spacing:-.01em}
.brand svg{width:42px;height:42px}
.brand small{display:block;font:600 9.5px/1 "Sora";color:var(--mu);letter-spacing:.26em;margin-top:2px}
.links{display:flex;gap:6px;font-size:14.5px;color:var(--mu)}
.links a{padding:8px 14px;border-radius:999px;transition:color .25s,background-color .25s}
.links a:hover,.links a.on{color:var(--tx);background:var(--sf2)}
.nav-cta{display:flex;align-items:center;gap:10px}
.icon-btn{width:42px;height:42px;border-radius:50%;border:1px solid var(--ln2);background:var(--sf);display:grid;place-items:center;cursor:pointer;transition:border-color .3s,transform .4s var(--ease);flex:none}
.icon-btn:hover{border-color:var(--gl)}
.icon-btn svg{width:19px;height:19px}
#themeBtn:hover{transform:rotate(18deg)}
#themeBtn .sun{display:none}
html[data-theme="light"] #themeBtn .sun{display:block}
html[data-theme="light"] #themeBtn .moon{display:none}
.burger{display:none;border-radius:13px}
@media(max-width:960px){.links{display:none}.burger{display:grid}}
@media(max-width:520px){.nav-cta .btn{display:none}}
.mnav{position:fixed;inset-inline:0;top:0;z-index:80;background:var(--bg2);border-bottom:1px solid var(--ln2);padding:18px 22px 26px;transform:translateY(-105%);transition:transform .45s var(--ease);box-shadow:var(--sh)}
.mnav.open{transform:none}
.mnav .top{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px}
.mnav a.m{display:flex;justify-content:space-between;padding:14px 4px;font-size:17px;font-weight:600;border-bottom:1px solid var(--ln)}
.mnav a.m:after{content:"←";color:var(--mu2)}
.mnav .btn{margin-top:18px}
.scrim{position:fixed;inset:0;z-index:70;background:rgba(10,8,6,.5);opacity:0;pointer-events:none;transition:opacity .3s}
.scrim.on{opacity:1;pointer-events:auto}
/* =========================================================
   HERO
   ========================================================= */
.hero{padding:72px 0 96px;overflow-x:clip}
.hero .wrap{display:grid;grid-template-columns:1.08fr .92fr;gap:48px;align-items:center}
@media(max-width:1000px){.hero .wrap{grid-template-columns:1fr;gap:72px}.hero{padding-top:44px}}
.hero h1{font-size:clamp(34px,5.2vw,62px);font-weight:900;line-height:1.3;margin-bottom:22px;letter-spacing:-.015em}
.w{display:inline-block;opacity:0;transform:translateY(30px);filter:blur(6px);animation:wi .9s var(--ease) forwards}
@keyframes wi{to{opacity:1;transform:none;filter:none}}
.lead{color:var(--mu);font-size:18px;max-width:560px;margin-bottom:34px}
.lead b{color:var(--tx);font-weight:600}
.cta{display:flex;gap:12px;flex-wrap:wrap}
.proof{display:flex;flex-wrap:wrap;gap:10px;margin-top:34px}
.proof span{display:inline-flex;align-items:center;gap:8px;font-size:13.5px;color:var(--mu);padding:7px 14px;border:1px solid var(--ln);border-radius:999px;background:var(--sf)}
.proof svg{width:16px;height:16px;color:var(--gl)}
/* phone */
.viz{position:relative;max-width:372px;width:100%;margin-inline:auto;perspective:1400px}
.viz:before{content:"";position:absolute;inset:8% -12% 4%;border-radius:50%;background:radial-gradient(closest-side,var(--tint2),transparent);filter:blur(20px);z-index:-1}
.phone{position:relative;background:#0E0B08;border:1px solid var(--ln2);border-radius:46px;padding:11px;box-shadow:var(--sh),inset 0 0 0 1px rgba(255,255,255,.04);transition:transform .3s var(--ease);transform-style:preserve-3d}
html[data-theme="light"] .phone{background:#2A2119}
.screen{position:relative;background:var(--bg2);border-radius:36px;overflow:hidden;aspect-ratio:9/19;display:flex;flex-direction:column;transition:background-color .5s}
.notch{position:absolute;top:10px;left:50%;transform:translateX(-50%);width:104px;height:26px;background:#0E0B08;border-radius:999px;z-index:5}
html[data-theme="light"] .notch{background:#2A2119}
.app-h{padding:46px 16px 12px;display:flex;align-items:center;gap:10px;background:radial-gradient(ellipse at 50% -40%,var(--tint2),transparent 70%);border-bottom:1px solid var(--ln)}
.app-h svg{width:38px;height:38px;flex:none}
.app-h b{display:block;font-size:14px;line-height:1.4}
.app-h small{color:var(--mu);font-size:10.5px}
.app-h .rate{margin-inline-start:auto;font:600 10.5px "Sora";color:var(--gl);background:var(--tint);padding:3px 8px;border-radius:999px}
.app-b{padding:12px 14px;display:grid;gap:12px;flex:1;align-content:start}
.lbl{font-size:10.5px;color:var(--mu2);font-weight:600;margin-bottom:6px;display:flex;justify-content:space-between}
.chips{display:flex;gap:6px;overflow:hidden}
.chip{flex:1;border:1px solid var(--ln);background:var(--sf);border-radius:12px;padding:7px 6px;font-size:10.5px;line-height:1.5;text-align:center;cursor:pointer;transition:.25s}
.chip small{display:block;color:var(--mu2);font-size:9.5px}
.days{display:grid;grid-template-columns:repeat(5,1fr);gap:6px}
.day{border:1px solid var(--ln);background:var(--sf);border-radius:12px;padding:6px 0;text-align:center;font-size:9.5px;color:var(--mu);cursor:pointer;transition:.25s;line-height:1.5}
.day b{display:block;font-size:15px;color:var(--tx);font-weight:700}
.slots{display:grid;grid-template-columns:repeat(3,1fr);gap:6px}
.slot{border:1px solid var(--ln);background:var(--sf);border-radius:10px;padding:7px 0;text-align:center;font:600 11px "Sora";color:var(--tx);cursor:pointer;transition:.25s}
.slot.busy{opacity:.38;text-decoration:line-through;cursor:not-allowed}
.chip.sel,.day.sel,.slot.sel{background:var(--btn);border-color:transparent;color:var(--on-gold)}
.chip.sel small,.day.sel b,.day.sel{color:var(--on-gold)}
.chip:not(.sel):hover,.day:not(.sel):hover,.slot:not(.sel):not(.busy):hover{border-color:var(--ln2)}
.book{margin:4px 14px 16px;padding:13px;border-radius:14px;border:0;background:var(--btn);color:var(--on-gold);font-weight:800;font-size:13px;cursor:pointer;transition:opacity .3s,transform .3s var(--ease)}
.book:disabled{opacity:.4;cursor:default}
.book:not(:disabled):hover{transform:translateY(-1px)}
.done{position:absolute;inset:0;z-index:4;background:var(--bg2);display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:30px 22px;opacity:0;pointer-events:none;transform:scale(1.04);transition:opacity .45s,transform .45s var(--ease)}
.done.on{opacity:1;pointer-events:auto;transform:none}
.done .ck{width:64px;height:64px;border-radius:50%;background:var(--btn);display:grid;place-items:center;color:var(--on-gold);margin-bottom:16px;box-shadow:0 0 0 10px var(--tint)}
.done .ck svg{width:30px;height:30px;stroke-dasharray:30;stroke-dashoffset:30}
.done.on .ck svg{animation:dr .6s .2s var(--ease) forwards}
@keyframes dr{to{stroke-dashoffset:0}}
.done h4{font-size:16px;margin-bottom:4px}
.done p{font-size:11.5px;color:var(--mu);margin-bottom:16px}
.ticket{width:100%;border:1px dashed var(--ln2);border-radius:14px;padding:10px 12px;font-size:11px;display:grid;gap:4px;text-align:start}
.ticket div{display:flex;justify-content:space-between;gap:8px}.ticket span{color:var(--mu)}
.done .again{margin-top:14px;font-size:11px;color:var(--gl);background:none;border:0;cursor:pointer;text-decoration:underline;text-underline-offset:4px}
.fl{position:absolute;display:flex;gap:10px;align-items:center;background:var(--glass);border:1px solid var(--ln2);border-radius:16px;padding:10px 14px;font-size:12.5px;line-height:1.5;box-shadow:var(--sh-soft);z-index:3;backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px)}
.fl i{width:32px;height:32px;border-radius:50%;background:var(--btn);display:grid;place-items:center;color:var(--on-gold);font-style:normal;flex:none}
.fl i svg{width:16px;height:16px}
.fl small{display:block;color:var(--mu);font-size:10.5px}
.f1{top:-4%;inset-inline-start:-58px;animation:bob 5s ease-in-out infinite}
.f2{bottom:18%;inset-inline-end:-78px;animation:bob 6s ease-in-out -2s infinite}
@keyframes bob{50%{translate:0 -10px}}
@media(max-width:560px){.f1{inset-inline-start:-10px;top:-6%}.f2{inset-inline-end:-10px;bottom:6%}}
/* =========================================================
   MARQUEE
   ========================================================= */
.mq{border-block:1px solid var(--ln);background:var(--sf);overflow:hidden;direction:ltr;padding:18px 0;position:relative;-webkit-mask-image:linear-gradient(90deg,transparent,#000 12%,#000 88%,transparent);mask-image:linear-gradient(90deg,transparent,#000 12%,#000 88%,transparent)}
.mq-t{display:flex;width:max-content;animation:mq 40s linear infinite;color:var(--mu);font-size:16px;direction:rtl}
.mq:hover .mq-t{animation-play-state:paused}
.mq span{display:flex;align-items:center;gap:52px;padding-inline-end:52px;white-space:nowrap}
.mq span:after{content:"✦";color:var(--gl);font-size:11px;opacity:.7}
@keyframes mq{to{transform:translateX(-50%)}}
/* =========================================================
   SECTIONS
   ========================================================= */
section{padding:110px 0}
.sh{text-align:center;max-width:740px;margin:0 auto 60px}
.kicker{display:inline-block;font:600 11.5px "Sora";letter-spacing:.24em;color:var(--gl);text-transform:uppercase;margin-bottom:14px}
.sh h2{font-size:clamp(28px,4vw,46px);font-weight:900;margin-bottom:16px;letter-spacing:-.01em}
.sh p{color:var(--mu);font-size:17.5px}
.card{border:1px solid var(--ln);border-radius:var(--r-lg);background:var(--bg2);transition:transform .4s var(--ease),border-color .4s,box-shadow .4s,background-color .5s}
/* before / after */
.ba{display:grid;grid-template-columns:1fr 1fr;gap:22px}
@media(max-width:820px){.ba{grid-template-columns:1fr}}
.pane{padding:38px;position:relative;overflow:hidden}
.pane.after{background:linear-gradient(160deg,var(--tint),var(--bg2) 62%);border-color:var(--ln2)}
.pane h3{font-size:23px;margin-bottom:6px}
.pane>p{color:var(--mu);margin-bottom:24px;font-size:15px}
.pane ul{list-style:none;padding:0;display:grid;gap:14px}
.pane li{display:flex;gap:12px;font-size:15.5px;color:var(--mu)}
.pane li:before{flex:none;width:24px;height:24px;border-radius:50%;display:grid;place-items:center;font-size:12px;font-weight:800;margin-top:2px}
.before li:before{content:"✕";color:var(--bad);background:color-mix(in srgb,var(--bad) 14%,transparent)}
.after li{color:var(--tx)}
.after li:before{content:"✓";color:var(--on-gold);background:var(--btn)}
.tag{position:absolute;top:24px;inset-inline-end:24px;font-size:11.5px;font-weight:600;padding:4px 13px;border-radius:999px}
.tag.b{color:var(--bad);border:1px solid color-mix(in srgb,var(--bad) 35%,transparent)}
.tag.a{color:var(--gl);border:1px solid var(--ln2);background:var(--tint)}
/* bento */
.bento{display:grid;grid-template-columns:repeat(6,1fr);gap:20px}
.tile{position:relative;overflow:hidden;padding:30px;background:linear-gradient(165deg,var(--bg3),var(--bg2));grid-column:span 2;min-height:310px;display:flex;flex-direction:column}
.tile:before{content:"";position:absolute;inset:0;background:radial-gradient(300px circle at var(--mx,50%) var(--my,0),var(--tint),transparent 70%);opacity:0;transition:opacity .35s;pointer-events:none}
.tile:hover{border-color:var(--ln2)}
.tile:hover:before{opacity:1}
.tile.w4{grid-column:span 4}.tile.w3{grid-column:span 3;min-height:0}
@media(max-width:920px){.tile,.tile.w4,.tile.w3{grid-column:span 6;min-height:0}}
.tile h3{font-size:20px;margin:18px 0 8px;position:relative}
.tile p{color:var(--mu);font-size:15px;position:relative}
.ic{width:48px;height:48px;border-radius:15px;background:var(--tint);border:1px solid var(--ln2);display:grid;place-items:center;color:var(--gl);position:relative;flex:none}
.ic svg{width:22px;height:22px}
.vis{min-height:104px;display:flex;align-items:center;position:relative;margin-top:16px}
.tslots{display:grid;grid-template-columns:repeat(4,1fr);gap:9px;width:100%;font-family:"Sora"}
.tslots span{padding:10px 0;text-align:center;border:1px solid var(--ln);border-radius:12px;font-size:12.5px;color:var(--mu);animation:pk 8s infinite}
.tslots span:nth-child(2){animation-delay:1s}.tslots span:nth-child(3){animation-delay:2s}.tslots span:nth-child(4){animation-delay:3s}.tslots span:nth-child(5){animation-delay:4s}.tslots span:nth-child(6){animation-delay:5s}.tslots span:nth-child(7){animation-delay:6s}.tslots span:nth-child(8){animation-delay:7s}
@keyframes pk{0%,4%{background:transparent;color:var(--mu)}8%,18%{background:var(--btn);color:var(--on-gold);border-color:transparent}24%,100%{background:transparent;color:var(--mu)}}
.sms{background:var(--sf2);border:1px solid var(--ln);border-radius:18px 18px 18px 5px;padding:12px 15px;font-size:13.5px;width:100%}
.sms small{display:block;color:var(--mu2);font-size:11px;margin-top:4px}
.ty{display:inline-flex;gap:4px;margin-top:8px}.ty i{width:6px;height:6px;border-radius:50%;background:var(--gl);animation:ty 1.2s infinite}.ty i:nth-child(2){animation-delay:.2s}.ty i:nth-child(3){animation-delay:.4s}
@keyframes ty{40%{transform:translateY(-5px);opacity:.4}}
.staff{display:grid;gap:7px;width:100%}
.staff div{display:flex;align-items:center;gap:10px;font-size:12.5px}
.staff .av{width:28px;height:28px;font-size:12px}
.staff .bar{flex:1;height:8px;border-radius:99px;background:var(--sf2);overflow:hidden}
.staff .bar i{display:block;height:100%;border-radius:99px;background:var(--btn);width:0;transition:width 1.2s var(--ease)}
.tile.in .staff .bar i{width:var(--w)}
.av{width:36px;height:36px;border-radius:50%;background:var(--btn);display:grid;place-items:center;color:var(--on-gold);font-size:13px;font-weight:800;flex:none}
.ringv{gap:16px}
.ringv svg{width:96px;height:96px;transform:rotate(-90deg)}
.ringv circle{fill:none;stroke-width:9;stroke-linecap:round}
.ringv .bgc{stroke:var(--sf2)}
.ringv .fg{stroke:url(#ringGrad);stroke-dasharray:239;stroke-dashoffset:239;transition:stroke-dashoffset 1.6s .3s var(--ease)}
.tile.in .ringv .fg{stroke-dashoffset:62}
.ringv b{position:absolute;inset-inline-start:0;width:96px;text-align:center;font:700 19px "Sora"}
.ringv p{font-size:13px}
.bars{display:flex;gap:9px;align-items:flex-end;height:96px;width:100%}
.bars i{flex:1;border-radius:7px 7px 3px 3px;background:var(--btn);transform:scaleY(0);transform-origin:bottom;transition:transform .9s var(--ease);opacity:.9}
.tile.in .bars i{transform:scaleY(1)}
.bars i:nth-child(2){transition-delay:.08s}.bars i:nth-child(3){transition-delay:.16s}.bars i:nth-child(4){transition-delay:.24s}.bars i:nth-child(5){transition-delay:.32s}.bars i:nth-child(6){transition-delay:.4s}.bars i:nth-child(7){transition-delay:.48s}
.row{display:flex;gap:18px;align-items:flex-start}
.row h3{margin-top:0}
/* =========================================================
   PRODUCT DASHBOARD
   ========================================================= */
#product{padding-top:40px}
.ptabs{display:flex;gap:6px;justify-content:center;margin:0 auto 26px;padding:5px;border:1px solid var(--ln);border-radius:999px;background:var(--sf);width:max-content;max-width:100%;overflow-x:auto}
.ptabs button{border:0;background:transparent;padding:9px 20px;border-radius:999px;cursor:pointer;color:var(--mu);font-size:14.5px;font-weight:600;transition:.3s;white-space:nowrap}
.ptabs button[aria-selected="true"]{background:var(--btn);color:var(--on-gold)}
.dash{border:1px solid var(--ln2);border-radius:26px;background:var(--bg2);box-shadow:var(--sh-card);overflow:hidden;display:grid;grid-template-columns:64px 1fr;min-height:520px}
.side{border-inline-end:1px solid var(--ln);background:var(--bg3);display:flex;flex-direction:column;align-items:center;gap:10px;padding:16px 0}
.side svg.lg{width:36px;height:36px;margin-bottom:10px}
.side span{width:40px;height:40px;border-radius:12px;display:grid;place-items:center;color:var(--mu2)}
.side span.on{background:var(--tint);color:var(--gl)}
.side span svg{width:19px;height:19px}
.dmain{display:flex;flex-direction:column;min-width:0}
.dtop{display:flex;align-items:center;gap:12px;padding:16px 22px;border-bottom:1px solid var(--ln);flex-wrap:wrap}
.dtop h4{font-size:16px}
.dtop small{color:var(--mu);font-size:12.5px}
.dtop .sp{flex:1}
.pill{font-size:12px;border:1px solid var(--ln);border-radius:999px;padding:5px 12px;color:var(--mu);background:var(--sf)}
.pill.g{color:var(--gl);border-color:var(--ln2);background:var(--tint)}
.view{display:none;padding:20px 22px;animation:fi .5s var(--ease)}
.view.on{display:block}
@keyframes fi{from{opacity:0;transform:translateY(10px)}}
.kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:18px}
.kpi{border:1px solid var(--ln);border-radius:16px;padding:12px 14px;background:var(--sf)}
.kpi small{color:var(--mu);font-size:12px}
.kpi b{display:block;font-size:20px;line-height:1.5}
.kpi em{font-style:normal;font-size:11.5px;color:var(--ok)}
@media(max-width:760px){.kpis{grid-template-columns:repeat(2,1fr)}}
.cal{--h:52px;display:grid;grid-template-columns:52px repeat(3,1fr);border:1px solid var(--ln);border-radius:16px;overflow:hidden;font-size:12px}
.cal .hd{padding:10px;border-bottom:1px solid var(--ln);display:flex;align-items:center;gap:8px;background:var(--sf);font-weight:600}
.cal .hd .av{width:24px;height:24px;font-size:10.5px}
.cal .times{display:grid;grid-auto-rows:var(--h);color:var(--mu2);font:500 11px "Sora";text-align:center;border-inline-end:1px solid var(--ln)}
.cal .times span{padding-top:4px;border-top:1px solid var(--ln)}
.cal .col{position:relative;height:calc(var(--h)*8);background:repeating-linear-gradient(180deg,transparent 0 calc(var(--h) - 1px),var(--ln) calc(var(--h) - 1px) var(--h));border-inline-end:1px solid var(--ln)}
.cal .col:last-child{border-inline-end:0}
.appt{position:absolute;inset-inline:6px;top:calc(var(--s)*var(--h) + 3px);height:calc(var(--d)*var(--h) - 6px);border-radius:11px;padding:6px 9px;background:var(--tint);border-inline-start:3px solid var(--gl);overflow:hidden;line-height:1.55;transition:transform .3s var(--ease)}
.appt:hover{transform:scale(1.02)}
.appt b{display:block;font-size:12px}
.appt small{color:var(--mu);font-size:10.5px}
.appt.alt{background:var(--sf2);border-inline-start-color:var(--mu2)}
.appt.new{animation:pop 1s var(--ease) both;box-shadow:0 0 0 2px var(--gl)}
@keyframes pop{0%{opacity:0;transform:scale(.9)}}
.now{position:absolute;inset-inline:0;top:calc(4.4*var(--h));height:2px;background:var(--bad);z-index:2}
.now:before{content:"";position:absolute;inset-inline-start:-4px;top:-3px;width:8px;height:8px;border-radius:50%;background:var(--bad)}
.tbl{width:100%;border-collapse:collapse;font-size:13.5px}
.tbl th{text-align:start;color:var(--mu);font-weight:600;font-size:12px;padding:10px 12px;border-bottom:1px solid var(--ln)}
.tbl td{padding:12px;border-bottom:1px solid var(--ln)}
.tbl tr:last-child td{border-bottom:0}
.tbl .who{display:flex;align-items:center;gap:10px}
.tbl .who .av{width:30px;height:30px;font-size:11px}
.badge{display:inline-block;font-size:11px;padding:2px 10px;border-radius:999px;background:var(--tint);color:var(--gl);font-weight:600}
.badge.m{background:var(--sf2);color:var(--mu)}
.tbl-wrap{overflow-x:auto;border:1px solid var(--ln);border-radius:16px}
.tbl-wrap table{min-width:560px}
.rep{display:grid;grid-template-columns:1.6fr 1fr;gap:16px}
@media(max-width:820px){.rep{grid-template-columns:1fr}}
.box{border:1px solid var(--ln);border-radius:16px;padding:16px 18px;background:var(--sf)}
.box h5{font-size:13.5px;margin-bottom:12px;display:flex;justify-content:space-between}
.box h5 span{color:var(--mu);font-weight:400;font-size:12px}
.chart{width:100%;height:180px}
.chart .area{fill:url(#areaGrad)}
.chart .line{fill:none;stroke:var(--gl);stroke-width:2.5;stroke-linecap:round;stroke-linejoin:round;stroke-dasharray:700;stroke-dashoffset:700}
.view.on .chart .line{animation:dl 1.6s .2s var(--ease) forwards}
@keyframes dl{to{stroke-dashoffset:0}}
.chart text{fill:var(--mu2);font:10px "Sora"}
.top-s{display:grid;gap:12px;font-size:13px}
.top-s div{display:grid;grid-template-columns:1fr auto;gap:6px}
.top-s .bar{grid-column:1/-1;height:7px;border-radius:99px;background:var(--sf2);overflow:hidden}
.top-s .bar i{display:block;height:100%;background:var(--btn);border-radius:99px}
@media(max-width:700px){.dash{grid-template-columns:1fr}.side{display:none}.cal{--h:46px;grid-template-columns:40px repeat(3,minmax(92px,1fr))}.cal-wrap{overflow-x:auto}}
/* =========================================================
   HOW
   ========================================================= */
#how{background:var(--sf);border-block:1px solid var(--ln)}
.how{display:grid;grid-template-columns:.95fr 1.05fr;gap:48px;align-items:center}
@media(max-width:920px){.how{grid-template-columns:1fr}}
.steps{display:grid;gap:6px}
.st{display:flex;gap:18px;padding:22px;border-radius:22px;border:1px solid transparent;cursor:pointer;position:relative;overflow:hidden;transition:.35s;text-align:start;background:transparent;width:100%}
.st.on{border-color:var(--ln);background:var(--bg2);box-shadow:var(--sh-soft)}
.st b{font:700 30px/1.2 "Sora";color:var(--mu2);min-width:38px;transition:color .3s}
.st.on b{color:var(--gl)}
.st h3{font-size:19px;margin-bottom:4px}
.st p{color:var(--mu);font-size:15px;max-height:0;opacity:0;overflow:hidden;transition:.45s var(--ease)}
.st.on p{max-height:140px;opacity:1}
.st:after{content:"";position:absolute;bottom:0;inset-inline:0;height:2px;background:var(--btn);transform:scaleX(0);transform-origin:right}
.st.on:after{animation:tm 5s linear forwards}
.st.on.hold:after{animation:none;transform:scaleX(1)}
@keyframes tm{to{transform:scaleX(1)}}
.scr{background:linear-gradient(160deg,var(--bg3),var(--bg2));border:1px solid var(--ln2);border-radius:30px;padding:28px;min-height:370px;position:relative;box-shadow:var(--sh-card)}
.pn{position:absolute;inset:28px;opacity:0;transform:translateY(16px);transition:.5s var(--ease);pointer-events:none}
.pn.on{opacity:1;transform:none;pointer-events:auto}
.pn h5{font-size:14px;margin-bottom:14px;color:var(--mu);font-weight:600}
.fld{border:1px solid var(--ln);border-radius:13px;padding:11px 16px;margin-bottom:10px;background:var(--sf);display:flex;justify-content:space-between;font-size:14px}
.fld em{font-style:normal;color:var(--gl);font-weight:600}
.lnk{padding:14px;border-radius:15px;border:1px dashed var(--gl);color:var(--gl);font:600 15px "Sora";text-align:center;direction:ltr;margin-bottom:16px;background:var(--tint)}
.share{display:flex;gap:16px;align-items:center;justify-content:center}
.qr{width:132px;height:132px;padding:10px;border-radius:16px;background:#fff;border:1px solid var(--ln)}
.chs{display:grid;gap:8px;font-size:13px}
.chs span{display:flex;align-items:center;gap:8px;padding:8px 12px;border:1px solid var(--ln);border-radius:12px;background:var(--sf)}
.chs span:before{content:"";width:8px;height:8px;border-radius:50%;background:var(--gl)}
.nt{display:flex;gap:12px;align-items:center;padding:12px 14px;border-radius:15px;background:var(--sf);border:1px solid var(--ln);margin-bottom:10px;font-size:14px}
.nt small{display:block;color:var(--mu);font-size:12px}
.nt .av{width:34px;height:34px}
.nt time{margin-inline-start:auto;font:500 11px "Sora";color:var(--mu2)}
/* =========================================================
   STATS
   ========================================================= */
.stats{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;text-align:center}
@media(max-width:700px){.stats{grid-template-columns:1fr}}
.stats>div{padding:40px 22px}
.stats>div:hover{transform:translateY(-5px);border-color:var(--ln2)}
.stats b{display:block;font-size:56px;line-height:1.15;font-weight:700}
.stats span{color:var(--mu);font-size:15px}
/* =========================================================
   DAY TIMELINE
   ========================================================= */
.tl{position:relative;max-width:900px;margin:0 auto;display:grid;gap:18px}
.tl:before{content:"";position:absolute;top:10px;bottom:10px;inset-inline-start:112px;width:2px;background:linear-gradient(var(--ln2),var(--ln) 80%,transparent)}
.tl-i{display:grid;grid-template-columns:80px 34px 1fr;gap:16px;align-items:start}
.tl-i time{font:700 15px/2 "Sora";color:var(--gl);text-align:end}
.tl-i .dot{width:34px;height:34px;border-radius:50%;background:var(--bg2);border:2px solid var(--ln2);display:grid;place-items:center;color:var(--gl);position:relative;z-index:1;transition:.4s}
.tl-i .dot svg{width:15px;height:15px}
.tl-i.in .dot{background:var(--btn);color:var(--on-gold);border-color:transparent;box-shadow:0 0 0 6px var(--tint)}
.tl-i .card{padding:18px 22px}
.tl-i h3{font-size:17px;margin-bottom:2px}
.tl-i p{color:var(--mu);font-size:14.5px}
@media(max-width:600px){.tl:before{inset-inline-start:16px}.tl-i{grid-template-columns:34px 1fr}.tl-i time{grid-column:2;text-align:start;line-height:1;order:-1}.tl-i .dot{grid-row:1/3;grid-column:1}.tl-i .card{grid-column:2}}
/* =========================================================
   AUDIENCES
   ========================================================= */
.tabs{display:flex;gap:10px;justify-content:center;flex-wrap:wrap;margin-bottom:30px}
.tabs button{color:var(--mu);background:var(--sf);border:1px solid var(--ln);border-radius:999px;padding:10px 22px;cursor:pointer;transition:.25s;font-weight:500;font-size:15px}
.tabs button:hover{border-color:var(--ln2);color:var(--tx)}
.tabs button[aria-selected="true"]{background:var(--btn);color:var(--on-gold);border-color:transparent;font-weight:700}
.aud{max-width:880px;margin:0 auto;padding:38px;display:none;grid-template-columns:1.1fr .9fr;gap:34px;animation:fi .5s var(--ease)}
.aud.on{display:grid}
@media(max-width:700px){.aud.on{grid-template-columns:1fr}.aud{padding:28px}}
.aud h3{font-size:23px;margin-bottom:10px}
.aud p{color:var(--mu);font-size:15.5px}
.aud ul{list-style:none;padding:0;display:grid;gap:12px;align-content:center}
.aud li{display:flex;gap:11px;font-size:15px}
.aud li:before{content:"✓";color:var(--gl);font-weight:800}
/* =========================================================
   PRICING
   ========================================================= */
.prc{display:grid;grid-template-columns:repeat(3,1fr);gap:22px;align-items:stretch}
@media(max-width:940px){.prc{grid-template-columns:1fr;max-width:460px;margin:0 auto}}
.pl{padding:36px 30px;display:flex;flex-direction:column;position:relative}
.pl:hover{transform:translateY(-6px);border-color:var(--ln2)}
.pl.hot{background:linear-gradient(170deg,var(--tint2),var(--bg2) 55%);border-color:var(--ln2);box-shadow:0 40px 80px -40px rgba(166,127,46,.55)}
.pl .ribbon{position:absolute;top:-14px;left:50%;transform:translateX(-50%);background:var(--btn);color:var(--on-gold);font-size:12px;font-weight:800;border-radius:999px;padding:6px 18px;white-space:nowrap}
.pl h3{font-size:20px}
.pl .for{color:var(--mu);font-size:13.5px;margin-bottom:22px}
.pl .pr{font-size:40px;font-weight:900;line-height:1.3}
.pl .pr small{font-size:13px;color:var(--mu);font-weight:400}
.pl ul{list-style:none;padding:0;margin:22px 0 28px;display:grid;gap:11px;flex:1;border-top:1px solid var(--ln);padding-top:22px}
.pl li{display:flex;gap:10px;font-size:14.5px}
.pl li:before{content:"✓";color:var(--gl);font-weight:800}
.pl li.no{color:var(--mu2)}.pl li.no:before{content:"—";color:var(--mu2)}
.incl{margin-top:26px;text-align:center;color:var(--mu);font-size:14px;display:flex;flex-wrap:wrap;gap:8px 22px;justify-content:center}
.incl span:before{content:"✦ ";color:var(--gl)}
/* trust strip */
.trust{display:grid;grid-template-columns:repeat(4,1fr);gap:18px}
@media(max-width:900px){.trust{grid-template-columns:repeat(2,1fr)}}
@media(max-width:520px){.trust{grid-template-columns:1fr}}
.trust .card{padding:24px}
.trust h4{font-size:16px;margin:14px 0 4px}
.trust p{color:var(--mu);font-size:14px}
/* =========================================================
   FAQ
   ========================================================= */
.faq{max-width:820px;margin:0 auto}
.faq details{border:1px solid var(--ln);border-radius:20px;padding:4px 24px;margin-bottom:12px;background:var(--bg2);transition:border-color .3s,background-color .5s}
.faq details[open]{border-color:var(--ln2)}
.faq summary{cursor:pointer;font-weight:700;list-style:none;display:flex;justify-content:space-between;gap:14px;align-items:center;padding:16px 0;font-size:16px}
.faq summary::-webkit-details-marker{display:none}
.faq summary:after{content:"";flex:none;width:30px;height:30px;border-radius:50%;border:1px solid var(--ln2);background:var(--sf) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23A67F2E' stroke-width='2.4' stroke-linecap='round'%3E%3Cpath d='M12 6v12M6 12h12'/%3E%3C/svg%3E") center/14px no-repeat;transition:transform .35s var(--ease)}
.faq details[open] summary:after{transform:rotate(45deg)}
.faq p{color:var(--mu);padding-bottom:20px;font-size:15px}
/* =========================================================
   FINAL / FORM
   ========================================================= */
.final .box2{position:relative;overflow:hidden;border:1px solid var(--ln2);border-radius:40px;padding:64px;background:radial-gradient(ellipse at 80% 0,rgba(166,127,46,var(--glow)),transparent 60%),linear-gradient(160deg,var(--bg3),var(--bg2));display:grid;grid-template-columns:1fr 1fr;gap:48px;align-items:center}
@media(max-width:900px){.final .box2{grid-template-columns:1fr;padding:40px 24px;border-radius:30px}}
.final h2{font-size:clamp(28px,4vw,44px);font-weight:900;margin:18px 0 14px}
.final .txt p{color:var(--mu);max-width:460px}
.final ul{list-style:none;padding:0;margin-top:22px;display:grid;gap:10px;font-size:15px}
.final li{display:flex;gap:10px}.final li:before{content:"✓";color:var(--gl);font-weight:800}
.mk{width:84px;height:84px;position:relative}
.mk svg{width:100%;height:100%;animation:bob 5s ease-in-out infinite;filter:drop-shadow(0 10px 26px rgba(166,127,46,.45))}
.form{background:var(--bg2);border:1px solid var(--ln);border-radius:26px;padding:28px;box-shadow:var(--sh-card);display:grid;gap:14px;position:relative}
.form h3{font-size:19px}
.form .sub{color:var(--mu);font-size:13.5px;margin-top:-8px}
.fieldset{display:grid;gap:6px}
.fieldset label{font-size:13px;font-weight:600}
.fieldset input,.fieldset select{width:100%;padding:13px 16px;border-radius:14px;border:1px solid var(--ln2);background:var(--sf);transition:border-color .25s,box-shadow .25s;outline:0}
.fieldset input:focus,.fieldset select:focus{border-color:var(--gl);box-shadow:0 0 0 4px var(--tint)}
.fieldset input[aria-invalid="true"]{border-color:var(--bad)}
.err{color:var(--bad);font-size:12px;min-height:0}
.form .btn{margin-top:4px}
.form .note{font-size:12px;color:var(--mu2);text-align:center}
.form .ok{position:absolute;inset:0;border-radius:inherit;background:var(--bg2);display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:30px;opacity:0;pointer-events:none;transition:opacity .4s}
.form.sent .ok{opacity:1;pointer-events:auto}
.form .ok .ck{width:62px;height:62px;border-radius:50%;background:var(--btn);display:grid;place-items:center;color:var(--on-gold);margin-bottom:14px}
.form .ok p{color:var(--mu);font-size:14px}
/* =========================================================
   FOOTER
   ========================================================= */
footer{border-top:1px solid var(--ln);padding:60px 0 32px;color:var(--mu);font-size:14px}
.fg{display:grid;grid-template-columns:1.6fr 1fr 1fr 1fr;gap:34px;margin-bottom:38px}
@media(max-width:780px){.fg{grid-template-columns:1fr 1fr}.fg>div:first-child{grid-column:1/-1}}
.fg h4{color:var(--tx);margin-bottom:14px;font-size:15px}
.fg a{display:block;margin-bottom:9px;transition:color .25s;width:max-content}
.fg a:hover{color:var(--gl)}
.copy{display:flex;justify-content:space-between;gap:14px;flex-wrap:wrap;border-top:1px solid var(--ln);padding-top:24px;font-size:13px}
/* floating */
#ttop{position:fixed;bottom:24px;inset-inline-end:24px;z-index:50;width:48px;height:48px;opacity:0;transform:translateY(14px);pointer-events:none;background:var(--glass);backdrop-filter:blur(10px);box-shadow:var(--sh-soft);transition:opacity .35s,transform .35s var(--ease),border-color .3s}
#ttop.show{opacity:1;transform:none;pointer-events:auto}
.mcta{position:fixed;bottom:0;inset-inline:0;z-index:49;display:none;padding:12px 16px calc(12px + env(safe-area-inset-bottom));background:var(--glass);backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);border-top:1px solid var(--ln2);transform:translateY(110%);transition:transform .4s var(--ease)}
.mcta.show{transform:none}
@media(max-width:700px){.mcta{display:block}#ttop{bottom:88px}}
/* reveal */
.rv{opacity:0;transform:translateY(30px);transition:opacity .9s var(--ease),transform .9s var(--ease)}
.rv.in{opacity:1;transform:none}
.d1{transition-delay:.08s}.d2{transition-delay:.16s}.d3{transition-delay:.24s}
@media(hover:hover){.card.rv.in:hover{transform:translateY(-5px)}}
@media(prefers-reduced-motion:reduce){*,*:before,*:after{animation:none!important;transition:none!important}.rv,.w{opacity:1!important;transform:none!important;filter:none!important}html{scroll-behavior:auto}.chart .line{stroke-dashoffset:0}.done .ck svg{stroke-dashoffset:0}}
/* افزوده‌ی Laravel (۲۰۲۶-۱۰-۱۴) */
.hp{position:absolute!important;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0);white-space:nowrap}
.prc.p4{grid-template-columns:repeat(4,1fr)}
@media(max-width:1180px){.prc.p4{grid-template-columns:repeat(2,1fr);max-width:900px;margin:0 auto}}
@media(max-width:620px){.prc.p4{grid-template-columns:1fr;max-width:460px}}
.pl .per{color:var(--mu);font-size:13.5px;margin-top:4px;min-height:1.6em}
.prc.p4 .pl{padding:32px 24px}
.prc.p4 .pl .pr{font-size:clamp(26px,2.4vw,32px);white-space:nowrap}
.prc.p4 .pl ul{align-content:start}
.pl .buy{display:block;text-align:center;margin-top:12px;font-size:13.5px;color:var(--gl);font-weight:700}
@media(max-width:560px){.nav-login{display:none}}
</style>
</head>
<body>
<a class="skip" href="#main">رفتن به محتوای اصلی</a>
<div id="pg" aria-hidden="true"></div>
<div class="aur" aria-hidden="true"><i></i><i></i><i></i></div>
<div class="grain" aria-hidden="true"></div>

<!-- ============ SHARED SVG: logo + gradients ============ -->
<svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false"><defs>
<linearGradient id="mg" gradientUnits="userSpaceOnUse" x1="160" y1="145" x2="360" y2="370"><stop offset="0" style="stop-color:var(--lg1)"/><stop offset="1" style="stop-color:var(--lg2)"/></linearGradient>
<linearGradient id="ringGrad" x1="0" y1="0" x2="1" y2="1"><stop offset="0" style="stop-color:var(--lg1)"/><stop offset="1" style="stop-color:var(--lg2)"/></linearGradient>
<linearGradient id="areaGrad" x1="0" y1="0" x2="0" y2="1"><stop offset="0" style="stop-color:var(--lg1);stop-opacity:.35"/><stop offset="1" style="stop-color:var(--lg1);stop-opacity:0"/></linearGradient>
<symbol id="mark" viewBox="130 130 252 252"><g fill="url(#mg)">
<path d="M308.1 158A111 111 0 1 0 366.9 252.1A107.3 107.3 0 1 1 308.1 158Z"/>
<path d="M247.2 162.4A94 94 0 1 0 320.7 324.1A89 89 0 0 1 247.2 162.4Z"/>
<circle cx="241" cy="259" r="13"/><circle cx="283" cy="259" r="13"/><circle cx="325" cy="259" r="13"/>
<path d="M212 308A29 28 0 0 1 270 308V349A94 94 0 0 1 212 339.1Z"/>
<path d="M263.2 287.5A29 28 0 0 1 312 308V331.5A94 94 0 0 1 254 350V342.3A28 28 0 1 0 263.2 287.5Z"/>
<path d="M305.2 287.5A29 28 0 0 1 344.6 287.4A94 94 0 0 1 331.7 311.7A28 28 0 0 0 305.2 287.5Z"/>
<circle cx="249" cy="196" r="8"/><circle cx="321" cy="221" r="8"/><circle cx="314" cy="183" r="7"/></g>
<line x1="249" y1="196" x2="321" y2="221" stroke="url(#mg)" stroke-width="5" stroke-linecap="round"/></symbol>
</defs></svg>

<!-- ============ HEADER ============ -->
<header id="hd"><div class="wrap nav">
<a class="brand" href="#top" aria-label="ماهرو — صفحه اصلی"><svg aria-hidden="true"><use href="#mark"/></svg><span>ماهرو<small>MAHRU</small></span></a>
<nav class="links" aria-label="ناوبری اصلی"><a href="#why">چرا ماهرو</a><a href="#features">امکانات</a><a href="#product">پنل مدیریت</a><a href="#how">نحوه کار</a><a href="#plans">تعرفه‌ها</a><a href="#faq">سؤالات</a></nav>
<div class="nav-cta">
<button class="icon-btn" id="themeBtn" type="button" aria-label="تغییر به حالت روشن">
<svg class="moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
<svg class="sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
</button>
<a class="btn btn-line btn-s nav-login" href="{{ route('login') }}">ورود</a>
<a class="btn btn-gold btn-s" href="#start">درخواست دمو</a>
<button class="icon-btn burger" id="burger" type="button" aria-label="باز کردن منو" aria-expanded="false" aria-controls="mnav"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h10"/></svg></button>
</div>
</div></header>

<div class="mnav" id="mnav" role="dialog" aria-modal="true" aria-label="منوی موبایل" aria-hidden="true">
<div class="top"><span class="brand"><svg width="36" height="36" aria-hidden="true"><use href="#mark"/></svg>ماهرو</span><button class="icon-btn" id="mnavX" type="button" aria-label="بستن منو">✕</button></div>
<a class="m" href="#why">چرا ماهرو</a><a class="m" href="#features">امکانات</a><a class="m" href="#product">پنل مدیریت</a><a class="m" href="#how">نحوه کار</a><a class="m" href="#plans">تعرفه‌ها</a><a class="m" href="#faq">سؤالات</a>
<a class="btn btn-gold btn-block" href="#start">درخواست دمو رایگان</a>
<a class="btn btn-line btn-block" href="{{ route('salon-signup.create') }}">ساخت سالن</a>
<a class="btn btn-line btn-block" href="{{ route('login') }}">ورود به پنل</a>
</div>
<div class="scrim" id="scrim"></div>

<main id="main">
<span id="top"></span>
<!-- ============ HERO ============ -->
<section class="hero"><div class="wrap">
<div>
<span class="eyebrow">نرم‌افزار رزرو و مدیریت سالن‌های زیبایی</span>
<h1 id="h1" aria-label="سالن شما، همیشه پر از نوبت؛ بدون یک تماس بی‌پاسخ">سالن شما، همیشه پر از نوبت؛ بدون یک تماس بی‌پاسخ</h1>
<p class="lead">ماهرو <b>نوبت‌دهی آنلاین، تقویم پرسنل، یادآوری پیامکی، بیعانه و باشگاه مشتریان</b> را در یک پنل ساده و شیک جمع می‌کند. مشتری شما در هر ساعت شبانه‌روز نوبت می‌گیرد و شما روی کار اصلی‌تان، یعنی زیبایی، تمرکز می‌کنید.</p>
<div class="cta"><a class="btn btn-gold" href="{{ route('salon-signup.create') }}">@if ($trialDays > 0)شروع رایگان {{ to_persian_num((string) $trialDays) }} روزه@else ساخت سالن@endif <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M11 6l-6 6 6 6"/></svg></a><a class="btn btn-line" href="#product">دیدن پنل مدیریت</a></div>
<div class="proof">
<span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>راه‌اندازی در یک روز</span>
<span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="2" width="12" height="20" rx="3"/><path d="M11 18h2"/></svg>بدون نصب، روی هر دستگاه</span>
<span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a8 8 0 0 1-8 8H5l-2 2V12a8 8 0 0 1 8-8h2a8 8 0 0 1 8 8z"/></svg>پشتیبانی فارسی</span>
</div>
</div>

<div class="viz" id="viz">
<div class="fl f1" aria-hidden="true"><i><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5 9-10"/></svg></i><div><b>نوبت جدید از اینستاگرام</b><small>رنگ و لایت · بیعانه پرداخت شد</small></div></div>
<div class="phone" id="phone"><div class="screen">
<div class="notch"></div>
<div class="app-h"><svg aria-hidden="true"><use href="#mark"/></svg><div><b>سالن رزگلد</b><small>رزرو آنلاین نوبت</small></div><span class="rate">دمو</span></div>
<div class="app-b" id="booker" aria-label="نمونه‌ی تعاملی رزرو نوبت">
<div><div class="lbl">۱. خدمت</div><div class="chips" id="svc">
<button class="chip" type="button" data-n="کوتاهی و براشینگ" data-t="۴۵ دقیقه">کوتاهی<small>۴۵ دقیقه</small></button>
<button class="chip" type="button" data-n="رنگ و لایت" data-t="۲ ساعت">رنگ و لایت<small>۲ ساعت</small></button>
<button class="chip" type="button" data-n="مانیکور ژلی" data-t="۶۰ دقیقه">مانیکور<small>۶۰ دقیقه</small></button>
</div></div>
<div><div class="lbl"><span>۲. روز</span><span id="mon"></span></div><div class="days" id="days"></div></div>
<div><div class="lbl"><span>۳. ساعت</span><span>آرایشگر: نگار</span></div><div class="slots" id="slots">
<button class="slot" type="button">10:00</button><button class="slot busy" type="button" disabled>11:30</button><button class="slot" type="button">13:00</button>
<button class="slot" type="button">14:30</button><button class="slot busy" type="button" disabled>16:00</button><button class="slot" type="button">17:30</button>
</div></div>
</div>
<button class="book" id="bookBtn" type="button" disabled>تأیید و رزرو نوبت</button>
<div class="done" id="done" aria-live="polite">
<div class="ck"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5 9-10"/></svg></div>
<h4>نوبت شما ثبت شد</h4><p>پیامک تأیید برای شما ارسال شد.</p>
<div class="ticket"><div><span>خدمت</span><b id="tS">—</b></div><div><span>زمان</span><b id="tD">—</b></div><div><span>آرایشگر</span><b>نگار</b></div></div>
<button class="again" id="again" type="button">رزرو نوبت دیگر</button>
</div>
</div></div>
<div class="fl f2" aria-hidden="true"><i><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9M13.7 21a2 2 0 0 1-3.4 0"/></svg></i><div><b>یادآوری ارسال شد</b><small>۲ ساعت قبل از نوبت</small></div></div>
</div>
</div></section>

<div class="mq" aria-hidden="true"><div class="mq-t" id="mq"><span>آرایشگاه زنانه</span><span>سالن ناخن</span><span>کلینیک پوست و مو</span><span>میکاپ و عروس</span><span>مراکز لیزر</span><span>اسپا و ماساژ</span><span>آرایشگاه مردانه</span></div></div>

<!-- ============ WHY ============ -->
<section id="why"><div class="wrap">
<div class="sh rv"><span class="kicker">Why Mahru</span><h2>تلفن و دفترچه را کنار بگذارید، <span class="gold">مشتری را نگه دارید</span></h2><p>بیشتر سالن‌ها نوبت را از دست نمی‌دهند چون مشتری ندارند؛ چون نوبت‌ها در تماس‌های بی‌پاسخ، پیام‌های گم‌شده و دفترچه‌های درهم می‌ماند.</p></div>
<div class="ba">
<div class="card pane before rv"><span class="tag b">وضعیت فعلی</span><h3>بدون ماهرو</h3><p>هر روز ساعت‌ها از وقت تیم صرف هماهنگی می‌شود.</p><ul><li>تماس و پیام در ساعت شلوغی بی‌پاسخ می‌ماند</li><li>نوبت‌ها تداخل پیدا می‌کنند و مشتری ناراضی می‌شود</li><li>فراموشی نوبت، ساعت کاری و درآمد را هدر می‌دهد</li><li>سابقه‌ی مشتری و گزارش مالی جایی ثبت نمی‌شود</li></ul></div>
<div class="card pane after rv d1"><span class="tag a">با ماهرو</span><h3 class="gold">همه‌چیز خودکار</h3><p>هر نوبت، از رزرو تا پیگیری، بدون دخالت دست انجام می‌شود.</p><ul><li>مشتری ۲۴ ساعته از لینک اختصاصی سالن نوبت می‌گیرد</li><li>تقویم مدت هر خدمت را حساب می‌کند و تداخل نمی‌دهد</li><li>پیامک تأیید و یادآوری، غیبت‌ها را کم می‌کند</li><li>پرونده‌ی مشتری، امتیاز وفاداری و گزارش درآمد همیشه در دسترس است</li></ul></div>
</div></div></section>

<!-- ============ FEATURES ============ -->
<section id="features" style="padding-top:0"><div class="wrap">
<div class="sh rv"><span class="kicker">Features</span><h2>هرچه یک <span class="gold">سالن حرفه‌ای</span> لازم دارد</h2><p>هر امکان برای کار روزمره‌ی سالن طراحی شده، نه برای پُر کردن فهرست قابلیت‌ها.</p></div>
<div class="bento">
<div class="card tile w4 rv">
<div class="row"><div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><rect x="3" y="5" width="18" height="16" rx="3"/><path d="M3 10h18M8 3v4M16 3v4"/></svg></div><div><h3>رزرو آنلاین ۲۴ ساعته</h3><p>هر سالن یک لینک اختصاصی دارد. مشتری خدمت و آرایشگر دلخواه را انتخاب می‌کند، فقط ساعت‌های خالی را می‌بیند و در چند ثانیه نوبتش ثبت می‌شود.</p></div></div>
<div class="vis"><div class="tslots"><span>۱۰:۰۰</span><span>۱۰:۳۰</span><span>۱۱:۰۰</span><span>۱۱:۳۰</span><span>۱۴:۰۰</span><span>۱۴:۳۰</span><span>۱۵:۰۰</span><span>۱۶:۳۰</span></div></div>
</div>
<div class="card tile rv d1">
<div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a8 8 0 0 1-8 8H5l-2 2V12a8 8 0 0 1 8-8h2a8 8 0 0 1 8 8z"/></svg></div>
<h3>یادآوری پیامکی</h3><p>پیامک تأیید بعد از رزرو و یادآوری پیش از نوبت، خودکار.</p>
<div class="vis"><div class="sms">مریم عزیز، نوبت شما امروز ساعت ۱۶:۳۰ است. منتظرتان هستیم ✨<small>سالن رزگلد · لغو: ۲</small></div></div>
</div>
<div class="card tile rv">
<div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="9" cy="8" r="3.5"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6"/><circle cx="17.5" cy="9.5" r="2.5"/><path d="M16 14.5c2.8.3 5 2.5 5 5.5"/></svg></div>
<h3>تقویم پرسنل و شیفت‌ها</h3><p>ساعت کار، مرخصی و مهارت هر آرایشگر جدا تعریف می‌شود.</p>
<div class="vis"><div class="staff"><div><span class="av">ن</span><span class="bar"><i style="--w:86%"></i></span></div><div><span class="av">س</span><span class="bar"><i style="--w:64%"></i></span></div><div><span class="av">ا</span><span class="bar"><i style="--w:72%"></i></span></div></div></div>
</div>
<div class="card tile rv d1">
<div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l2.6 5.6 6 .7-4.5 4.1 1.2 5.9L12 15.4 6.7 18.3l1.2-5.9L3.4 8.3l6-.7z"/></svg></div>
<h3>باشگاه مشتریان</h3><p>پرونده‌ی مشتری، امتیاز وفاداری و کد تخفیف برای برگرداندن مشتریان قدیمی.</p>
<div class="vis ringv"><svg viewBox="0 0 92 92" aria-hidden="true"><circle class="bgc" cx="46" cy="46" r="38"/><circle class="fg" cx="46" cy="46" r="38"/></svg><b class="num">۷۴٪</b><p>نمونه: سهم مشتریان بازگشتی</p></div>
</div>
<div class="card tile rv d2">
<div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M4 20V10M10 20V4M16 20v-8M22 20H2"/></svg></div>
<h3>گزارش و آمار</h3><p>درآمد، پرکارترین پرسنل و محبوب‌ترین خدمات در یک نگاه.</p>
<div class="vis"><div class="bars"><i style="height:35%"></i><i style="height:52%"></i><i style="height:44%"></i><i style="height:70%"></i><i style="height:62%"></i><i style="height:80%"></i><i style="height:94%"></i></div></div>
</div>
<div class="card tile w3 rv">
<div class="row"><div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="6" width="18" height="13" rx="3"/><path d="M3 10h18M7 15h4"/></svg></div><div><h3>پرداخت و بیعانه</h3><p>برای خدمات طولانی بیعانه‌ی آنلاین بگیرید تا نوبت‌ها بی‌خبر خالی نمانند. گزارش مالی روزانه، هفتگی و ماهانه هم آماده است.</p></div></div>
</div>
<div class="card tile w3 rv d1">
<div class="row"><div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 7H5a2 2 0 0 1 0-4h13v4M3 5v14a2 2 0 0 0 2 2h15V7"/><path d="M16 14h.01"/></svg></div><div><h3>کیف پول و تسویه‌ی پرسنل</h3><p>سهم هر متخصص از هر نوبت خودکار حساب می‌شود؛ متخصص موجودی‌اش را می‌بیند و برداشت به شبا را از پنل خودش درخواست می‌کند.</p></div></div>
</div>
</div></div></section>

<!-- ============ PRODUCT ============ -->
<section id="product"><div class="wrap">
<div class="sh rv"><span class="kicker">Dashboard</span><h2>کل سالن، <span class="gold">در یک صفحه</span></h2><p>تقویم پرسنل، پرونده‌ی مشتریان و گزارش درآمد؛ همه با چند کلیک. (داده‌ها نمونه هستند)</p></div>
<div class="ptabs rv" role="tablist" aria-label="نماهای پنل"><button role="tab" type="button" aria-selected="true" aria-controls="v-cal" id="t-cal">تقویم امروز</button><button role="tab" type="button" aria-selected="false" aria-controls="v-cli" id="t-cli">مشتریان</button><button role="tab" type="button" aria-selected="false" aria-controls="v-rep" id="t-rep">گزارش مالی</button></div>
<div class="dash rv">
<aside class="side" aria-hidden="true">
<svg class="lg"><use href="#mark"/></svg>
<span class="on"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><rect x="3" y="5" width="18" height="16" rx="3"/><path d="M3 10h18M8 3v4M16 3v4"/></svg></span>
<span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="9" cy="8" r="3.5"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6"/></svg></span>
<span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M4 20V10M10 20V4M16 20v-8M22 20H2"/></svg></span>
<span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/></svg></span>
</aside>
<div class="dmain">
<div class="dtop"><div><h4 id="dTitle">تقویم امروز</h4><small id="today">—</small></div><span class="sp"></span><span class="pill g">۱۲ نوبت</span><span class="pill">۳ پرسنل فعال</span></div>

<div class="view on" id="v-cal" role="tabpanel" aria-labelledby="t-cal">
<div class="kpis"><div class="kpi"><small>نوبت‌های امروز</small><b class="num">۱۲</b><em>۳ رزرو آنلاین شبانه</em></div><div class="kpi"><small>درآمد پیش‌بینی‌شده</small><b class="num">۱۸٫۴ م</b><em>تومان</em></div><div class="kpi"><small>بیعانه‌ی دریافتی</small><b class="num">۲٫۱ م</b><em>تومان</em></div><div class="kpi"><small>ساعت خالی</small><b class="num">۲</b><em style="color:var(--mu)">روی لینک باز است</em></div></div>
<div class="cal-wrap"><div class="cal" aria-label="تقویم نمونه">
<div class="hd" style="justify-content:center">ساعت</div><div class="hd"><span class="av">ن</span>نگار</div><div class="hd"><span class="av">س</span>سارا</div><div class="hd"><span class="av">ا</span>الهه</div>
<div class="times"><span>10</span><span>11</span><span>12</span><span>13</span><span>14</span><span>15</span><span>16</span><span>17</span></div>
<div class="col"><div class="appt" style="--s:0;--d:.75"><b>مریم رضایی</b><small>کوتاهی و براشینگ</small></div><div class="appt alt" style="--s:1;--d:1"><b>شیدا امینی</b><small>براشینگ</small></div><div class="appt" style="--s:3;--d:2"><b>نازنین کریمی</b><small>رنگ و لایت · بیعانه ✓</small></div><div class="appt new" style="--s:6.5;--d:1.25"><b>هانیه شکوهی</b><small>رزرو آنلاین · ۰۱:۴۰ بامداد</small></div><div class="now"></div></div>
<div class="col"><div class="appt alt" style="--s:.5;--d:1.5"><b>پریسا محمدی</b><small>مانیکور ژلی</small></div><div class="appt" style="--s:2.5;--d:1"><b>آیدا نوری</b><small>پدیکور</small></div><div class="appt" style="--s:5;--d:1.5"><b>الناز رحیمی</b><small>کاشت ناخن</small></div></div>
<div class="col"><div class="appt" style="--s:1;--d:1.25"><b>رها صادقی</b><small>پاکسازی پوست</small></div><div class="appt alt" style="--s:3.5;--d:.75"><b>سمیرا یزدانی</b><small>اصلاح ابرو</small></div><div class="appt" style="--s:4.5;--d:2"><b>مینا جعفری</b><small>میکاپ مجلسی</small></div></div>
</div></div>
</div>

<div class="view" id="v-cli" role="tabpanel" aria-labelledby="t-cli">
<div class="tbl-wrap"><table class="tbl">
<thead><tr><th>مشتری</th><th>تعداد مراجعه</th><th>آخرین نوبت</th><th>امتیاز وفاداری</th><th>وضعیت</th></tr></thead>
<tbody>
<tr><td><div class="who"><span class="av">م</span>مریم رضایی</div></td><td class="num">۱۸</td><td>امروز</td><td class="num">۹۲۰</td><td><span class="badge">طلایی</span></td></tr>
<tr><td><div class="who"><span class="av">ن</span>نازنین کریمی</div></td><td class="num">۱۱</td><td>امروز</td><td class="num">۵۴۰</td><td><span class="badge">نقره‌ای</span></td></tr>
<tr><td><div class="who"><span class="av">پ</span>پریسا محمدی</div></td><td class="num">۷</td><td>۳ هفته پیش</td><td class="num">۳۱۰</td><td><span class="badge m">پیامک دعوت ارسال شد</span></td></tr>
<tr><td><div class="who"><span class="av">ه</span>هانیه شکوهی</div></td><td class="num">۱</td><td>امروز</td><td class="num">۴۰</td><td><span class="badge m">مشتری جدید</span></td></tr>
<tr><td><div class="who"><span class="av">ر</span>رها صادقی</div></td><td class="num">۲۴</td><td>امروز</td><td class="num">۱٬۲۸۰</td><td><span class="badge">طلایی</span></td></tr>
</tbody></table></div>
</div>

<div class="view" id="v-rep" role="tabpanel" aria-labelledby="t-rep">
<div class="kpis"><div class="kpi"><small>درآمد این ماه</small><b class="num">۳۲۶ م</b><em>تومان</em></div><div class="kpi"><small>نوبت انجام‌شده</small><b class="num">۲۸۴</b><em>این ماه</em></div><div class="kpi"><small>رزرو آنلاین</small><b class="num">۶۲٪</b><em>از کل نوبت‌ها</em></div><div class="kpi"><small>غیبت بی‌خبر</small><b class="num">۳٪</b><em>با یادآوری پیامکی</em></div></div>
<div class="rep">
<div class="box"><h5>درآمد هفتگی <span>میلیون تومان</span></h5>
<svg class="chart" viewBox="0 0 400 180" preserveAspectRatio="none" aria-hidden="true">
<path class="area" d="M0 150 C40 140 60 120 100 118 S160 130 200 96 S270 70 300 74 S360 40 400 28 V180 H0Z"/>
<path class="line" d="M0 150 C40 140 60 120 100 118 S160 130 200 96 S270 70 300 74 S360 40 400 28"/>
</svg></div>
<div class="box"><h5>پرطرفدارترین خدمات <span>این ماه</span></h5><div class="top-s">
<div><span>رنگ و لایت</span><b class="num">۳۸٪</b><span class="bar"><i style="width:88%"></i></span></div>
<div><span>کوتاهی و براشینگ</span><b class="num">۲۷٪</b><span class="bar"><i style="width:64%"></i></span></div>
<div><span>ناخن</span><b class="num">۲۱٪</b><span class="bar"><i style="width:50%"></i></span></div>
<div><span>پوست</span><b class="num">۱۴٪</b><span class="bar"><i style="width:34%"></i></span></div>
</div></div>
</div>
</div>
</div></div>
</div></section>

<!-- ============ HOW ============ -->
<section id="how"><div class="wrap">
<div class="sh rv"><span class="kicker">How it works</span><h2>سه قدم تا <span class="gold">اولین رزرو</span></h2><p>بدون برنامه‌نویسی، بدون نصب و با راهنمایی قدم‌به‌قدم.</p></div>
<div class="how">
<div class="steps rv" id="steps">
<button class="st on" type="button"><b>۱</b><div><h3>سالن را بسازید</h3><p>نام سالن، خدمات، قیمت، مدت‌زمان و پرسنل را وارد کنید. لینک اختصاصی سالن همان لحظه ساخته می‌شود.</p></div></button>
<button class="st" type="button"><b>۲</b><div><h3>لینک را منتشر کنید</h3><p>لینک یا QR سالن را در اینستاگرام، گوگل‌مپ و پیام‌رسان‌ها بگذارید تا مشتری مستقیم نوبت بگیرد.</p></div></button>
<button class="st" type="button"><b>۳</b><div><h3>نوبت‌ها را مدیریت کنید</h3><p>همه‌چیز در تقویم می‌آید و تأیید، یادآوری و پیگیری مشتری خودکار انجام می‌شود.</p></div></button>
</div>
<div class="scr rv d1" id="scr">
<div class="pn on"><h5>اطلاعات سالن</h5><div class="fld">نام سالن<em>سالن رزگلد</em></div><div class="fld">کوتاهی و براشینگ<em>۴۵ دقیقه</em></div><div class="fld">رنگ و لایت<em>۲ ساعت</em></div><div class="fld">پرسنل<em>۳ نفر</em></div><div class="fld">بیعانه‌ی خدمات طولانی<em>فعال</em></div></div>
<div class="pn"><h5>لینک اختصاصی شما آماده است</h5><div class="lnk">mahru.ir/rosegold</div><div class="share">
<svg class="qr" viewBox="0 0 21 21" shape-rendering="crispEdges" aria-hidden="true"><path fill="#1A1410" d="M0 0h7v7H0zM1 1v5h5V1zM2 2h3v3H2zM14 0h7v7h-7zM15 1v5h5V1zM16 2h3v3h-3zM0 14h7v7H0zM1 15v5h5v-5zM2 16h3v3H2zM8 0h1v2H8zM10 1h2v1h-2zM9 3h2v2H9zM12 4h1v3h-1zM8 6h1v2H8zM10 8h3v1h-3zM0 8h2v1H0zM3 9h3v1H3zM14 8h2v2h-2zM17 9h3v1h-3zM8 10h2v2H8zM11 11h3v1h-3zM15 11h1v3h-1zM18 12h3v1h-3zM0 11h1v2H0zM2 12h3v1H2zM9 13h2v2H9zM12 14h2v1h-2zM17 14h2v2h-2zM8 16h1v3H8zM10 17h3v1h-3zM14 16h2v1h-2zM16 18h3v1h-3zM13 19h2v2h-2zM10 20h2v1h-2zM19 20h2v1h-2z"/></svg>
<div class="chs"><span>بیوی اینستاگرام</span><span>گوگل‌مپ</span><span>پیام‌رسان‌ها</span></div></div></div>
<div class="pn"><h5>امروز در سالن</h5><div class="nt"><span class="av">✓</span><div>نوبت جدید ثبت شد<small>نازنین کریمی · رنگ و لایت</small></div><time>09:12</time></div><div class="nt"><span class="av">★</span><div>یادآوری ارسال شد<small>مریم رضایی · ۲ ساعت قبل</small></div><time>08:00</time></div><div class="nt"><span class="av">₮</span><div>بیعانه دریافت شد<small>پریسا محمدی · مانیکور ژلی</small></div><time>07:41</time></div></div>
</div></div></div></section>

<!-- ============ STATS ============ -->
<section style="padding-bottom:0"><div class="wrap"><div class="stats">
<div class="card rv"><b class="gold num" data-to="24">۰</b><span>ساعت شبانه‌روز، سالن شما برای رزرو باز است</span></div>
<div class="card rv d1"><b class="gold num" data-to="30">۰</b><span>ثانیه یا کمتر، زمان لازم برای رزرو یک نوبت</span></div>
<div class="card rv d2"><b class="gold num" data-to="1">۰</b><span>روز، برای راه‌اندازی کامل و گرفتن اولین نوبت</span></div>
</div></div></section>

<!-- ============ A DAY WITH MAHRU ============ -->
<section id="day"><div class="wrap">
<div class="sh rv"><span class="kicker">A day with Mahru</span><h2>یک روز کاری، <span class="gold">بدون یک تماس</span></h2><p>این‌ها کارهایی است که ماهرو در طول روز، بی‌صدا برای سالن شما انجام می‌دهد.</p></div>
<div class="tl">
<div class="tl-i rv"><time>01:40</time><span class="dot"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg></span><div class="card"><h3>رزرو نیمه‌شب از اینستاگرام</h3><p>مشتری از لینک بیو، نوبت رنگ و لایت را برای فردا می‌گیرد و بیعانه را آنلاین پرداخت می‌کند.</p></div></div>
<div class="tl-i rv"><time>08:00</time><span class="dot"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/></svg></span><div class="card"><h3>یادآوری‌ها ارسال می‌شود</h3><p>همه‌ی مشتری‌های امروز پیش از نوبتشان پیامک یادآوری می‌گیرند.</p></div></div>
<div class="tl-i rv"><time>11:15</time><span class="dot"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 3-6.7L3 8M3 3v5h5"/></svg></span><div class="card"><h3>یک لغو، یک فرصت</h3><p>مشتری نوبتش را لغو می‌کند و آن ساعت بلافاصله دوباره روی لینک رزرو باز می‌شود.</p></div></div>
<div class="tl-i rv"><time>17:30</time><span class="dot"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l2.6 5.6 6 .7-4.5 4.1 1.2 5.9L12 15.4 6.7 18.3l1.2-5.9L3.4 8.3l6-.7z"/></svg></span><div class="card"><h3>امتیاز وفاداری ثبت می‌شود</h3><p>بعد از هر خدمت، امتیاز به پرونده‌ی مشتری اضافه می‌شود و سابقه‌ی سلیقه‌اش ذخیره می‌ماند.</p></div></div>
<div class="tl-i rv"><time>21:00</time><span class="dot"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M4 20V10M10 20V4M16 20v-8M22 20H2"/></svg></span><div class="card"><h3>گزارش روز آماده است</h3><p>درآمد، نوبت‌های انجام‌شده و عملکرد هر آرایشگر؛ قبل از اینکه چراغ سالن را خاموش کنید.</p></div></div>
</div></div></section>

<!-- ============ AUDIENCES ============ -->
<section id="for" style="padding-top:0"><div class="wrap">
<div class="sh rv"><span class="kicker">Made for you</span><h2>برای هر نوع <span class="gold">سالن</span></h2><p>ماهرو با ساختار خدمات و پرسنل شما سازگار می‌شود.</p></div>
<div class="tabs rv" id="tabs" role="tablist" aria-label="نوع سالن"><button role="tab" type="button" aria-selected="true">آرایشگاه و سالن مو</button><button role="tab" type="button" aria-selected="false">ناخن و زیبایی</button><button role="tab" type="button" aria-selected="false">پوست، مو و لیزر</button></div>
<div class="rv">
<div class="card aud on" role="tabpanel"><div><h3>آرایشگاه و سالن مو</h3><p>رنگ، لایت و کوتاهی زمان‌های متفاوتی می‌خواهند. ماهرو مدت هر خدمت را جدا حساب می‌کند تا تداخلی پیش نیاید.</p></div><ul><li>زمان‌بندی خدمات طولانی مثل رنگ و لایت</li><li>انتخاب آرایشگر مورد علاقه‌ی مشتری</li><li>کد تخفیف و امتیاز برای برگرداندن مشتری‌ها</li></ul></div>
<div class="card aud" role="tabpanel"><div><h3>ناخن و زیبایی</h3><p>نوبت‌های کوتاه و پشت‌سرهم، بدون فاصله‌ی هدررفته و با یادآوری دقیق.</p></div><ul><li>رزرو سریع خدمات کوتاه</li><li>بیعانه برای جلوگیری از غیبت</li><li>پرونده‌ی سلیقه و سابقه‌ی هر مشتری</li></ul></div>
<div class="card aud" role="tabpanel"><div><h3>پوست، مو و لیزر</h3><p>خدمات طولانی با مدت‌زمان دقیق و متخصص مشخص، بدون تداخل در تقویم.</p></div><ul><li>سابقه‌ی نوبت‌های هر مشتری در پرونده‌اش</li><li>یادآوری پیامکی پیش از هر نوبت</li><li>گزارش دقیق از هر خدمت انجام‌شده</li></ul></div>
</div></div></section>

<!-- ============ PRICING ============ -->
<section id="plans" style="padding-top:0"><div class="wrap">
<div class="sh rv"><span class="kicker">Pricing</span><h2>یک نرم‌افزار کامل، <span class="gold">چهار مدت اشتراک</span></h2><p>@if ($trialDays > 0){{ to_persian_num((string) $trialDays) }} روز رایگان، بدون نیاز به کارت بانکی. هر وقت راضی بودید، پلن را از داخل پنل سالن می‌خرید.@else همه‌ی امکانات در همه‌ی پلن‌ها؛ فقط مدت و تخفیف فرق می‌کند.@endif</p></div>
<div class="prc p4">
@foreach ($plans as $plan)
<div class="card pl rv {{ $plan['type'] === '6m' ? 'hot' : '' }}" data-plan="{{ $plan['type'] }}">
@if ($plan['type'] === '6m')<span class="ribbon">انتخاب بیشتر سالن‌ها</span>@endif
<h3>{{ $plan['label'] }}</h3>
<p class="for">{{ $plan['months'] > 1 ? 'پرداخت یک‌جا برای '.to_persian_num((string) $plan['months']).' ماه' : 'پرداخت ماه‌به‌ماه' }}</p>
<div class="pr gold num">{{ to_persian_num(number_format($plan['price'])) }} <small>تومان</small></div>
<p class="per">@if ($plan['months'] > 1)ماهانه حدود {{ to_persian_num(number_format($plan['per_month'])) }} تومان@endif</p>
<ul>
<li>همه‌ی امکانات ماهرو</li>
<li>{{ to_persian_num((string) $maxSpecialists) }} متخصص در قیمت پلن</li>
<li>{{ to_persian_num(number_format($plan['sms_total'])) }} قطعه پیامک ({{ to_persian_num(number_format($smsQuota)) }} در ماه)</li>
@if ($plan['saving'] > 0)<li>{{ to_persian_num((string) $plan['saving_percent']) }} درصد صرفه‌جویی ({{ to_persian_num(number_format($plan['saving'])) }} تومان)</li>@endif
</ul>
@if ($trialDays > 0)
<a class="btn {{ $plan['type'] === '6m' ? 'btn-gold' : 'btn-line' }} btn-block" href="{{ $plan['signup_url'] }}">شروع {{ to_persian_num((string) $trialDays) }} روز رایگان</a>
<a class="buy" href="{{ $plan['buy_url'] }}">خرید فوری، بدون دوره‌ی رایگان</a>
@else
<a class="btn {{ $plan['type'] === '6m' ? 'btn-gold' : 'btn-line' }} btn-block" href="{{ $plan['buy_url'] }}">خرید و پرداخت آنلاین</a>
@endif
</div>
@endforeach
</div>
<div class="incl rv"><span>هر متخصص بیشتر ماهانه {{ to_persian_num(number_format($extraSpecialistPrice)) }} تومان</span><span>بدون هزینه‌ی راه‌اندازی</span><span>تیکت پشتیبانی فارسی</span><span>به‌روزرسانی رایگان</span></div>
</div></section>


<!-- ============ TRUST ============ -->
<section style="padding-top:0"><div class="wrap"><div class="trust">
<div class="card rv"><div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="11" rx="3"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg></div><h4>داده‌های جدا برای هر سالن</h4><p>اطلاعات مشتریان هر سالن فقط برای همان سالن قابل دسترس است.</p></div>
<div class="card rv d1"><div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l8 4v5c0 5-3.5 8-8 9-4.5-1-8-4-8-9V7z"/></svg></div><h4>ارتباط رمزنگاری‌شده</h4><p>همه‌ی اطلاعات بین مرورگر و سرور، رمزنگاری‌شده منتقل می‌شود.</p></div>
<div class="card rv d2"><div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="2" width="12" height="20" rx="3"/><path d="M11 18h2"/></svg></div><h4>روی هر دستگاه</h4><p>پنل و صفحه‌ی رزرو روی موبایل، تبلت و کامپیوتر کار می‌کند.</p></div>
<div class="card rv d3"><div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1v-6h3zM3 19a2 2 0 0 0 2 2h1v-6H3z"/></svg></div><h4>پشتیبانی فارسی</h4><p>در راه‌اندازی و کار روزمره، تیم پشتیبانی کنار شماست.</p></div>
</div></div></section>

<!-- ============ FAQ ============ -->
<section id="faq" style="padding-top:0"><div class="wrap">
<div class="sh rv"><span class="kicker">FAQ</span><h2>سؤالات <span class="gold">متداول</span></h2></div>
<div class="faq rv">
<details open><summary>برای شروع به چه چیزی نیاز دارم؟</summary><p>فقط اطلاعات سالن و فهرست خدمات‌تان. سالن را خودتان در چند دقیقه می‌سازید (یا درخواست دمو بدهید تا با هم بسازیم) و بقیه‌ی تنظیمات داخل پنل انجام می‌شود.</p></details>
<details><summary>مشتریان باید برنامه‌ای نصب کنند؟</summary><p>خیر. رزرو از طریق لینک اختصاصی سالن و روی هر مرورگری انجام می‌شود؛ روی موبایل، تبلت و کامپیوتر.</p></details>
<details><summary>پیامک یادآوری چطور کار می‌کند؟</summary><p>پس از ثبت نوبت پیامک تأیید ارسال می‌شود و پیش از نوبت هم یادآوری می‌رسد. پیامک‌ها از سهمیه‌ی ماهانه‌ی پلن ({{ to_persian_num(number_format($smsQuota)) }} قطعه) ارسال می‌شوند و هر نوع اعلان را در تنظیمات سالن روشن یا خاموش می‌کنید.</p></details>
@if ($trialDays > 0)
<details><summary>بعد از تمام شدن دوره‌ی رایگان چه می‌شود؟</summary><p>پنل مدیریت فقط صفحه‌ی خرید اشتراک را نشان می‌دهد و آدرس رزرو سالن برای مشتری‌ها موقتاً بسته می‌شود. هیچ اطلاعاتی پاک نمی‌شود؛ به محض خرید، همه‌چیز همان‌طور که بود ادامه پیدا می‌کند. اگر وسط دوره‌ی رایگان بخرید، اشتراک از همان روز خرید فعال می‌شود.</p></details>
<details><summary>در دوره‌ی رایگان محدودیتی هست؟</summary><p>همه‌ی امکانات باز است. تنها تفاوت، سقف پیامک است: در دوره‌ی رایگان {{ to_persian_num(number_format($trialSmsQuota)) }} قطعه پیامک در ماه و بعد از خرید {{ to_persian_num(number_format($smsQuota)) }} قطعه پیامک در ماه.</p></details>
@endif
<details><summary>اگر بیشتر از {{ to_persian_num((string) $maxSpecialists) }} متخصص داشته باشم؟</summary><p>موقع ساخت سالن تعداد متخصص‌ها را وارد کنید؛ هر متخصص بیشتر ماهانه {{ to_persian_num(number_format($extraSpecialistPrice)) }} تومان به قیمت پلن اضافه می‌شود (در پلن‌های چندماهه با همان تخفیف).</p></details>
<details><summary>پول پیش‌پرداخت مشتری‌ها به حساب چه کسی واریز می‌شود؟</summary><p>مستقیم به حساب درگاه پرداخت خود سالن. اطلاعات درگاه را موقع ساخت سالن یا بعداً از پنل مدیریت وارد می‌کنید؛ تا درگاهی ثبت نشود، پرداخت آنلاین برای مشتری‌های سالن ممکن نیست.</p></details>
<details><summary>اطلاعات مشتریانم امن است؟</summary><p>داده‌های هر سالن فقط برای خود آن سالن قابل دسترس است، با رمزنگاری منتقل می‌شود و بین سالن‌ها هیچ اشتراکی وجود ندارد.</p></details>
<details><summary>اگر به کمک نیاز داشتم چه؟</summary><p>تیم پشتیبانی فارسی در راه‌اندازی و کار روزمره کنار شماست؛ از داخل پنل تیکت بزنید@if ($contact['support_phone']) یا تماس بگیرید@endif.</p></details>
</div></div></section>


<!-- ============ FINAL CTA + FORM ============ -->
<section class="final" id="start" style="padding-top:0"><div class="wrap"><div class="box2 rv">
<div class="txt">
<div class="mk"><svg aria-hidden="true"><use href="#mark"/></svg></div>
<h2>آماده‌اید سالنتان <span class="gold">درخشان‌تر</span> شود؟</h2>
<p>شماره‌تان را بگذارید؛ همکاران ما تماس می‌گیرند، اطلاعات سالن را با هم وارد می‌کنیم و اولین نوبت آنلاین را همان روز ثبت می‌کنیم.</p>
<ul><li>دموی رایگان و بدون تعهد</li><li>راه‌اندازی در یک روز</li><li>بدون نیاز به کارت بانکی</li></ul>
<p style="margin-top:14px">یا خودتان همین حالا <a class="gold" href="{{ route('salon-signup.create') }}" style="font-weight:800">سالن را بسازید</a>.</p>
</div>
<form class="form{{ session('demo_requested') ? ' sent' : '' }}" id="demoForm" action="{{ route('demo-requests.store') }}" method="post" novalidate>
@csrf
<div class="hp" aria-hidden="true"><label for="fWebsite">وب‌سایت</label><input id="fWebsite" name="website" tabindex="-1" autocomplete="off"></div>
<h3>درخواست دموی رایگان</h3><p class="sub">کمتر از یک دقیقه وقت می‌گیرد.</p>
<div class="fieldset"><label for="fName">نام و نام خانوادگی</label><input id="fName" name="name" autocomplete="name" required placeholder="مثلاً سارا محمدی" value="{{ old('name') }}" @error('name') aria-invalid="true" @enderror><span class="err" id="eName">@error('name'){{ $message }}@enderror</span></div>
<div class="fieldset"><label for="fSalon">نام سالن</label><input id="fSalon" name="salon" autocomplete="organization" placeholder="مثلاً سالن رزگلد" value="{{ old('salon') }}"></div>
<div class="fieldset"><label for="fPhone">شماره موبایل</label><input id="fPhone" name="phone" type="tel" inputmode="numeric" autocomplete="tel" dir="ltr" required placeholder="09xx xxx xxxx" style="text-align:right" value="{{ old('phone') }}" @error('phone') aria-invalid="true" @enderror><span class="err" id="ePhone">@error('phone'){{ $message }}@enderror</span></div>
<div class="fieldset"><label for="fType">نوع سالن</label><select id="fType" name="type">@foreach (\App\Models\DemoRequest::TYPES as $typeKey => $typeLabel)<option value="{{ $typeKey }}" @selected(old('type') === $typeKey)>{{ $typeLabel }}</option>@endforeach</select></div>
<button class="btn btn-gold btn-block" type="submit" id="fBtn">ثبت درخواست دمو</button>
@if ($contact['support_phone'])<p class="note">یا تماس مستقیم: <a href="tel:{{ $contact['support_phone'] }}" class="num" dir="ltr">{{ $contact['support_phone'] }}</a></p>@endif
<div class="ok" role="status" aria-live="polite"><div class="ck"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5 9-10"/></svg></div><h3>درخواست شما ثبت شد</h3><p>به‌زودی برای هماهنگی دمو با شما تماس می‌گیریم.</p></div>
</form>
</div></div></section>
</main>

<!-- ============ FOOTER ============ -->
<footer><div class="wrap"><div class="fg">
<div><a class="brand" href="#top" style="margin-bottom:14px"><svg aria-hidden="true"><use href="#mark"/></svg><span>ماهرو<small>MAHRU</small></span></a><p style="max-width:300px">نرم‌افزار نوبت‌دهی و مدیریت سالن‌های زیبایی؛ ساده برای مشتری، قدرتمند برای مدیر.</p></div>
<div><h4>محصول</h4><a href="#features">امکانات</a><a href="#product">پنل مدیریت</a><a href="#plans">تعرفه‌ها</a><a href="#for">مناسب چه کسانی</a></div>
<div><h4>پشتیبانی</h4><a href="#faq">سؤالات متداول</a><a href="#start">درخواست دمو</a><a href="{{ route('login') }}">ورود به پنل</a>@if ($contact['support_phone'])<a href="tel:{{ $contact['support_phone'] }}">تماس با ما</a>@endif</div>
@if (array_filter(\Illuminate\Support\Arr::except($contact, ['support_phone'])))<div><h4>شبکه‌ها</h4>@if ($contact['instagram_url'])<a href="{{ $contact['instagram_url'] }}" rel="noopener" target="_blank">اینستاگرام</a>@endif @if ($contact['linkedin_url'])<a href="{{ $contact['linkedin_url'] }}" rel="noopener" target="_blank">لینکدین</a>@endif @if ($contact['telegram_url'])<a href="{{ $contact['telegram_url'] }}" rel="noopener" target="_blank">تلگرام</a>@endif @if ($contact['bale_url'])<a href="{{ $contact['bale_url'] }}" rel="noopener" target="_blank">بله</a>@endif @if ($contact['public_email'])<a href="mailto:{{ $contact['public_email'] }}">ایمیل</a>@endif</div>@endif
</div><div class="copy"><span>© <span id="yr">{{ to_persian_num((string) \Morilog\Jalali\Jalalian::now()->getYear()) }}</span> ماهرو · همه‌ی حقوق محفوظ است</span><span>ساخته‌شده برای سالن‌های ایران</span></div></div></footer>

<button class="icon-btn" id="ttop" type="button" aria-label="بازگشت به بالا"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5M6 11l6-6 6 6"/></svg></button>
<div class="mcta" id="mcta"><a class="btn btn-gold btn-block" href="#start">درخواست دمو رایگان · راه‌اندازی در یک روز</a></div>

<script>
(()=>{
const $=(s,r=document)=>[...r.querySelectorAll(s)],$1=(s,r=document)=>r.querySelector(s);
const rm=matchMedia('(prefers-reduced-motion:reduce)').matches;
const fa=n=>Number(n).toLocaleString('fa-IR');
const root=document.documentElement;

/* ---------- theme ---------- */
const metaTheme=$1('#metaTheme'),themeBtn=$1('#themeBtn');
function syncTheme(){const l=root.dataset.theme==='light';metaTheme.content=l?'#FBF8F2':'#15110E';themeBtn.setAttribute('aria-label',l?'تغییر به حالت تیره':'تغییر به حالت روشن')}
themeBtn.addEventListener('click',()=>{root.dataset.theme=root.dataset.theme==='light'?'dark':'light';try{localStorage.setItem('mahru-theme',root.dataset.theme)}catch(e){}syncTheme()});
syncTheme();

/* ---------- hero words ---------- */
const h1=$1('#h1'),words=h1.textContent.trim().split(/\s+/);
h1.innerHTML=words.map((w,i)=>`<span class="w${i>=words.length-3?' gold':''}" aria-hidden="true" style="animation-delay:${.08+i*.08}s">${w}</span>`).join(' ');

/* ---------- marquee ---------- */
const mq=$1('#mq');mq.innerHTML+=mq.innerHTML;

/* ---------- dates (Jalali) ---------- */
let fmtW,fmtD,fmtM,fmtFull;
try{fmtW=new Intl.DateTimeFormat('fa-IR-u-ca-persian',{weekday:'long'});fmtD=new Intl.DateTimeFormat('fa-IR-u-ca-persian',{day:'numeric'});fmtM=new Intl.DateTimeFormat('fa-IR-u-ca-persian',{month:'long'});fmtFull=new Intl.DateTimeFormat('fa-IR-u-ca-persian',{weekday:'long',day:'numeric',month:'long',year:'numeric'});$1('#yr').textContent=new Intl.DateTimeFormat('fa-IR-u-ca-persian',{year:'numeric'}).format(new Date())}catch(e){}
const now=new Date();
if(fmtFull)$1('#today').textContent=fmtFull.format(now);

/* ---------- mobile nav ---------- */
const mnav=$1('#mnav'),scrim=$1('#scrim'),burger=$1('#burger');
function setMenu(o){mnav.classList.toggle('open',o);scrim.classList.toggle('on',o);burger.setAttribute('aria-expanded',o);mnav.setAttribute('aria-hidden',!o);document.body.style.overflow=o?'hidden':'';if(o)$1('#mnavX').focus()}
burger.addEventListener('click',()=>setMenu(true));$1('#mnavX').addEventListener('click',()=>setMenu(false));scrim.addEventListener('click',()=>setMenu(false));
$('.mnav a').forEach(a=>a.addEventListener('click',()=>setMenu(false)));
addEventListener('keydown',e=>{if(e.key==='Escape')setMenu(false)});

/* ---------- scroll ui ---------- */
const hd=$1('#hd'),pg=$1('#pg'),ttop=$1('#ttop');
const navLinks=$('.links a'),secs=navLinks.map(a=>$1(a.getAttribute('href')));
let ticking=false;
function onScroll(){const y=scrollY;hd.classList.toggle('s',y>8);pg.style.transform=`scaleX(${y/Math.max(1,document.documentElement.scrollHeight-innerHeight)})`;ttop.classList.toggle('show',y>700);
let cur=-1;secs.forEach((s,i)=>{if(s&&s.getBoundingClientRect().top<innerHeight*.4)cur=i});navLinks.forEach((a,i)=>a.classList.toggle('on',i===cur));ticking=false}
addEventListener('scroll',()=>{if(!ticking){requestAnimationFrame(onScroll);ticking=true}},{passive:true});onScroll();
ttop.addEventListener('click',()=>scrollTo({top:0,behavior:rm?'auto':'smooth'}));
const mcta=$1('#mcta'),hero=$1('.hero'),start=$1('#start');let heroVis=true,startVis=false;
const mio=new IntersectionObserver(es=>{es.forEach(e=>{if(e.target===hero)heroVis=e.isIntersecting;else startVis=e.isIntersecting});mcta.classList.toggle('show',!heroVis&&!startVis)},{threshold:.1});
mio.observe(hero);mio.observe(start);

/* ---------- reveal + counters ---------- */
const io=new IntersectionObserver(es=>es.forEach(e=>{if(!e.isIntersecting)return;const t=e.target;t.classList.add('in');io.unobserve(t);
$('[data-to]',t).concat(t.matches('[data-to]')?[t]:[]).forEach(c=>{const to=+c.dataset.to,s=performance.now();if(rm){c.textContent=fa(to);return}(function f(n){const p=Math.min((n-s)/1400,1);c.textContent=fa(Math.round(to*(1-Math.pow(1-p,3))));p<1&&requestAnimationFrame(f)})(s)})}),{threshold:.15,rootMargin:'0px 0px -40px 0px'});
$('.rv,.tile,.tl-i').forEach(e=>io.observe(e));

/* ---------- spotlight ---------- */
$('.tile').forEach(t=>t.addEventListener('pointermove',e=>{const r=t.getBoundingClientRect();t.style.setProperty('--mx',e.clientX-r.left+'px');t.style.setProperty('--my',e.clientY-r.top+'px')}));

/* ---------- phone tilt ---------- */
const viz=$1('#viz'),phone=$1('#phone');
if(!rm&&matchMedia('(hover:hover)').matches){viz.addEventListener('pointermove',e=>{const r=viz.getBoundingClientRect(),x=(e.clientX-r.left)/r.width-.5,y=(e.clientY-r.top)/r.height-.5;phone.style.transform=`rotateY(${x*-7}deg) rotateX(${y*5}deg)`});viz.addEventListener('pointerleave',()=>phone.style.transform='')}

/* ---------- interactive booking demo ---------- */
const daysEl=$1('#days'),bookBtn=$1('#bookBtn'),done=$1('#done');
for(let i=0;i<5;i++){const d=new Date(now);d.setDate(now.getDate()+i);const b=document.createElement('button');b.type='button';b.className='day';
const wd=fmtW?fmtW.format(d):['یکشنبه','دوشنبه','سه‌شنبه','چهارشنبه','پنجشنبه','جمعه','شنبه'][d.getDay()];
b.innerHTML=`<b>${fmtD?fmtD.format(d):fa(d.getDate())}</b>${i===0?'امروز':i===1?'فردا':wd}`;b.dataset.label=(i===0?'امروز':i===1?'فردا':wd)+' '+(fmtD?fmtD.format(d):'')+(fmtM?' '+fmtM.format(d):'');daysEl.appendChild(b)}
if(fmtM)$1('#mon').textContent=fmtM.format(now);
const state={svc:null,day:null,slot:null};let auto=true,autoT;
function pick(group,el){$('.sel',group).forEach(x=>x.classList.remove('sel'));el.classList.add('sel');el.setAttribute('aria-pressed','true')}
function check(){bookBtn.disabled=!(state.svc&&state.day&&state.slot)}
function sel(kind,el){state[kind]=el;pick(el.parentElement,el);check()}
$('#svc .chip').forEach(c=>c.addEventListener('click',()=>{stopAuto();sel('svc',c)}));
daysEl.addEventListener('click',e=>{const b=e.target.closest('.day');if(b){stopAuto();sel('day',b)}});
$('#slots .slot:not(.busy)').forEach(s=>s.addEventListener('click',()=>{stopAuto();sel('slot',s)}));
function confirm(){$1('#tS').textContent=state.svc.dataset.n;$1('#tD').textContent=state.day.dataset.label.trim()+' · '+state.slot.textContent;done.classList.add('on')}
bookBtn.addEventListener('click',()=>{stopAuto();confirm()});
function reset(){done.classList.remove('on');$('#booker .sel').forEach(x=>{x.classList.remove('sel');x.removeAttribute('aria-pressed')});state.svc=state.day=state.slot=null;check()}
$1('#again').addEventListener('click',()=>{stopAuto();reset()});
function stopAuto(){if(auto){auto=false;clearTimeout(autoT);if(done.classList.contains('on'))reset()}}
const seq=[()=>sel('svc',$('#svc .chip')[1]),()=>sel('day',$('.day',daysEl)[1]),()=>sel('slot',$('#slots .slot')[3]),()=>confirm(),()=>{},()=>reset()];
let si=0;function run(){if(!auto)return;seq[si]();si=(si+1)%seq.length;autoT=setTimeout(run,si===4?2600:1100)}
if(!rm){const pio=new IntersectionObserver(es=>{if(es[0].isIntersecting&&auto&&!autoT){autoT=setTimeout(run,1400)}},{threshold:.4});pio.observe(phone)}

/* ---------- dashboard tabs ---------- */
const pt=$('.ptabs button'),views=$('.view'),titles=['تقویم امروز','مشتریان','گزارش مالی'];
function tabTo(btns,i,onChange){btns.forEach((b,k)=>{b.setAttribute('aria-selected',k===i);b.tabIndex=k===i?0:-1});onChange(i)}
function keyNav(btns,onChange){btns.forEach((b,i)=>{b.addEventListener('click',()=>tabTo(btns,i,onChange));b.addEventListener('keydown',e=>{let j=null;if(e.key==='ArrowLeft')j=(i+1)%btns.length;if(e.key==='ArrowRight')j=(i-1+btns.length)%btns.length;if(j!==null){e.preventDefault();btns[j].focus();tabTo(btns,j,onChange)}})})}
keyNav(pt,i=>{views.forEach((v,k)=>v.classList.toggle('on',k===i));$1('#dTitle').textContent=titles[i]});
const tb=$('#tabs button'),au=$('.aud');
keyNav(tb,i=>au.forEach((a,k)=>a.classList.toggle('on',k===i)));

/* ---------- steps ---------- */
const st=$('.st'),pn=$('.pn');let cur=0,tm,stepsSeen=false;
function go(i,manual){cur=i;st.forEach((s,k)=>{s.classList.remove('on','hold');void s.offsetWidth;if(k===i){s.classList.add('on');if(manual||rm)s.classList.add('hold')}});pn.forEach((p,k)=>p.classList.toggle('on',k===i));clearTimeout(tm);if(!rm&&!manual&&stepsSeen)tm=setTimeout(()=>go((cur+1)%3),5000)}
st.forEach((s,i)=>s.addEventListener('click',()=>go(i,true)));
new IntersectionObserver(es=>{if(es[0].isIntersecting&&!stepsSeen){stepsSeen=true;go(0)}},{threshold:.35}).observe($1('#steps'));
go(0,true);

/* ---------- faq: one open at a time ---------- */
$('.faq details').forEach(d=>d.addEventListener('toggle',()=>{if(d.open)$('.faq details').forEach(o=>{if(o!==d)o.open=false})}));

/* ---------- demo form ---------- */
const form=$1('#demoForm');
const toEn=s=>s.replace(/[۰-۹]/g,d=>'۰۱۲۳۴۵۶۷۸۹'.indexOf(d)).replace(/[٠-٩]/g,d=>'٠١٢٣٤٥٦٧٨٩'.indexOf(d)).replace(/\D/g,'');
function setErr(input,el,msg){el.textContent=msg;input.setAttribute('aria-invalid',msg?'true':'false')}
form.addEventListener('submit',async e=>{e.preventDefault();
const n=$1('#fName'),p=$1('#fPhone');let ok=true;
if(n.value.trim().length<2){setErr(n,$1('#eName'),'لطفاً نام خود را وارد کنید.');ok=false}else setErr(n,$1('#eName'),'');
let ph=toEn(p.value);if(ph.startsWith('98'))ph='0'+ph.slice(2);if(ph.length===10&&ph.startsWith('9'))ph='0'+ph;
if(!/^09\d{9}$/.test(ph)){setErr(p,$1('#ePhone'),'شماره موبایل معتبر نیست (مثلاً ۰۹۱۲۳۴۵۶۷۸۹).');ok=false}else setErr(p,$1('#ePhone'),'');
if(!ok){form.querySelector('[aria-invalid="true"]').focus();return}
const btn=$1('#fBtn');btn.disabled=true;btn.textContent='در حال ارسال…';
const data=new FormData(form);data.set('phone',ph);
try{const r=await fetch(form.action,{method:'POST',body:data,headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});
if(r.status===422){const j=await r.json().catch(()=>({}));const er=j.errors||{};setErr(n,$1('#eName'),(er.name||[''])[0]);setErr(p,$1('#ePhone'),(er.phone||er.type||[''])[0]);throw 'shown'}
if(r.status===429){setErr(p,$1('#ePhone'),'درخواست‌های زیادی از این دستگاه رسیده است؛ کمی بعد دوباره تلاش کنید.');throw 'shown'}
if(!r.ok)throw 0;form.classList.add('sent')}
catch(err){btn.disabled=false;btn.textContent='ثبت درخواست دمو';if(err!=='shown')setErr(p,$1('#ePhone'),'ارسال انجام نشد. لطفاً دوباره تلاش کنید'+(@json((bool) $contact['support_phone'])?' یا مستقیم تماس بگیرید.':'.'))}
});
})();
</script>
</body>
</html>
