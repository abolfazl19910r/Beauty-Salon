<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ config('brand.name') }}؛ نرم‌افزار نوبت‌دهی آنلاین و مدیریت سالن زیبایی با آدرس اختصاصی رزرو برای هر سالن.">
    <title>{{ config('brand.name') }} — نوبت‌دهی آنلاین سالن زیبایی</title>
    @include('partials.platform-icons')
    {{-- ⭐ پیش‌نمایش اشتراک‌گذاری لینک (تلگرام، واتساپ، لینکدین، X) — ۲۰۲۶-۰۹-۲۵ --}}
    <meta property="og:type" content="website">
    <meta property="og:locale" content="fa_IR">
    <meta property="og:site_name" content="{{ config('brand.name') }}">
    <meta property="og:title" content="{{ config('brand.name') }} — نوبت‌دهی آنلاین سالن زیبایی">
    <meta property="og:description" content="برای سالن شما یک سایت نوبت‌دهی اختصاصی، با پنل مدیر و متخصص و پرداخت آنلاین مستقیم به حساب خود سالن.">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:image" content="{{ asset('brand/mahru-og.png') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="{{ config('brand.name') }} — نوبت‌دهی آنلاین سالن‌های زیبایی">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:image" content="{{ asset('brand/mahru-og.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;700;800;900&display=swap" rel="stylesheet">

    {{--
        ⭐ صفحه‌ی اصلی/فروش دامنه‌ی مرکزی (۲۰۲۶-۰۹-۲۳) — CentralLandingController.
        عمداً self-contained (بدون ویت، بدون x-guest-layout)، دقیقاً هم‌الگو با salon-signup/*:
        این صفحه بیرون از هر سالنه، پس layoutهای وابسته به CurrentSalon اینجا کرش می‌کنن.
        سه پیش‌نمایش پنل (مشتری/متخصص/مدیر) با HTML/CSS خالص ساخته شدن، با همون design tokenهای
        واقعی هر پنل (طلایی/آلویی/slate) تا چیزی که مشتری می‌بینه همونی باشه که می‌خره.
    --}}
    <style>
        :root {
            --ink: #1A1410;
            --cocoa: #2E2117;
            --cocoa-2: #3A2A1D;
            --gold: #C9A24B;
            --champagne: #E6CD8A;
            --cream: #F8F3E9;
            --cream-2: #EFE6D4;
            --text-dim: rgba(248, 243, 233, .72);
            --ok: #7EE0A6;
            --bad: #FF8A8A;
            --plum-bg: #1F1424;
            --plum-surface: #2C1B32;
            --plum-border: #3A2640;
            --plum-light: #E0B8E8;
            --plum-text: #F3E1F7;
            --slate-bg: #F8FAFC;
            --slate-border: #E2E8F0;
            --slate-text: #1E293B;
            --slate-dim: #64748B;
            --display: 'Vazirmatn', Tahoma, sans-serif;
            --body: 'Vazirmatn', Tahoma, sans-serif;
        }

        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body { margin: 0; font-family: var(--body); color: var(--ink); background: var(--cream); line-height: 1.85; }
        img { max-width: 100%; }
        a { color: inherit; }
        :focus-visible { outline: 3px solid var(--gold); outline-offset: 3px; border-radius: 6px; }
        .wrap { width: min(1160px, 100% - 2.5rem); margin-inline: auto; }
        h1, h2, h3 { font-family: var(--display); font-weight: 800; line-height: 1.45; margin: 0; }
        h2 { font-size: clamp(1.6rem, 3vw, 2.3rem); }
        p { margin: 0; }
        .num { font-feature-settings: "tnum"; }

        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: .5rem;
            padding: .8rem 1.6rem; border-radius: 999px; font-weight: 700; font-size: 1rem;
            text-decoration: none; border: 1px solid transparent; cursor: pointer; font-family: inherit;
        }
        .btn-gold { background: var(--gold); color: var(--ink); }
        .btn-gold:hover { background: var(--champagne); }
        .btn-line { border-color: rgba(201, 162, 75, .5); color: var(--cream); background: transparent; }
        .btn-line:hover { border-color: var(--gold); }
        .btn-ink { background: var(--ink); color: var(--cream); }
        .btn-ink:hover { background: var(--cocoa); }

        /* ---------- header ---------- */
        .top { background: var(--ink); color: var(--cream); position: sticky; top: 0; z-index: 20; border-bottom: 1px solid rgba(201,162,75,.15); }
        .top .wrap { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding-block: .8rem; }
        .brand { display: flex; align-items: center; gap: .6rem; text-decoration: none; }
        .brand-logo { height: 46px; width: auto; display: block; }
        .footer-mark { height: 22px; width: auto; vertical-align: middle; margin-left: .4rem; }
        .brand-mark { width: 38px; height: 38px; border-radius: 50%; border: 1.5px solid var(--gold); display: grid; place-items: center; font-family: var(--display); color: var(--champagne); font-size: 1.15rem; }
        .brand-name { font-family: var(--display); font-weight: 800; font-size: 1.3rem; color: var(--champagne); }
        .nav { display: flex; gap: 1.6rem; font-size: .95rem; }
        .nav a { text-decoration: none; color: var(--text-dim); }
        .nav a:hover { color: var(--champagne); }
        .top-actions { display: flex; gap: .6rem; align-items: center; }
        .top-actions .btn { padding: .5rem 1.1rem; font-size: .9rem; }
        @media (max-width: 860px) { .nav { display: none; } }
        @media (max-width: 520px) { .top-actions .btn-line { display: none; } }

        /* ---------- hero ---------- */
        .hero { background: radial-gradient(1200px 500px at 85% -10%, #3B2A1B 0%, transparent 60%), var(--ink); color: var(--cream); padding: 4.5rem 0 5rem; overflow: hidden; }
        .hero .wrap { display: grid; grid-template-columns: 1.25fr .9fr; gap: 3.5rem; align-items: center; }
        @media (max-width: 960px) { .hero .wrap { grid-template-columns: 1fr; } }
        .hero h1 { font-size: clamp(2.1rem, 5vw, 3.5rem); color: var(--cream); }
        .hero-lead { margin-top: 1.1rem; font-size: 1.1rem; color: var(--text-dim); max-width: 36rem; }

        .composer { margin-top: 2.2rem; background: var(--cocoa); border: 1px solid rgba(201,162,75,.25); border-radius: 1.2rem; padding: 1.3rem; max-width: 40rem; }
        .composer label { display: block; font-size: .92rem; color: var(--champagne); margin-bottom: .7rem; }
        .address { display: flex; align-items: center; direction: ltr; background: var(--ink); border: 1px solid rgba(201,162,75,.3); border-radius: .8rem; padding: .35rem .35rem .35rem .9rem; font-size: 1.05rem; }
        .address:focus-within { border-color: var(--gold); box-shadow: 0 0 0 3px rgba(201,162,75,.2); }
        .address .fixed { color: rgba(248,243,233,.5); white-space: nowrap; font-family: ui-monospace, Menlo, Consolas, monospace; font-size: .95rem; }
        .address input { flex: 1; min-width: 6rem; background: transparent; border: 0; color: var(--champagne); font: 700 1.05rem ui-monospace, Menlo, Consolas, monospace; padding: .55rem .2rem; outline: none; }
        .address .btn { padding: .6rem 1.1rem; font-size: .95rem; white-space: nowrap; direction: rtl; }
        .composer-status { min-height: 1.6em; margin-top: .6rem; font-size: .9rem; color: var(--text-dim); }
        .composer-status.ok { color: var(--ok); }
        .composer-status.bad { color: var(--bad); }
        .hero-facts { display: flex; flex-wrap: wrap; gap: .5rem 1.4rem; margin-top: 1.4rem; font-size: .92rem; color: var(--text-dim); }
        .hero-facts span::before { content: "✓"; color: var(--gold); margin-left: .4rem; }
        @media (max-width: 560px) {
            .address { flex-wrap: wrap; }
            .address .btn { width: 100%; margin-top: .4rem; }
        }

        /* phone mockup (customer booking) */
        .phone { width: min(330px, 100%); margin-inline: auto; background: #0e0a08; border-radius: 2.4rem; padding: .7rem; box-shadow: 0 40px 80px -30px rgba(0,0,0,.8), 0 0 0 1px rgba(201,162,75,.25); }
        .phone-screen { background: var(--cream); border-radius: 1.8rem; overflow: hidden; color: var(--ink); font-size: .78rem; }
        .phone-hero { margin: .7rem; border-radius: 1rem; padding: 1rem; color: var(--cream); background: linear-gradient(160deg, #6b5033, #2E2117); min-height: 96px; }
        .phone-hero strong { font-family: var(--display); font-size: 1.05rem; display: block; color: var(--champagne); }
        .phone-sec { padding: 0 .7rem .7rem; }
        .phone-sec h4 { margin: .4rem 0 .45rem; font-size: .8rem; }
        .svc-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: .4rem; }
        .svc { background: #fff; border-radius: .7rem; padding: .5rem .3rem; text-align: center; border: 1px solid var(--cream-2); }
        .svc i { display: block; width: 26px; height: 26px; border-radius: 50%; margin: 0 auto .3rem; background: var(--cream-2); font-style: normal; line-height: 26px; }
        .days { display: flex; gap: .3rem; }
        .day { flex: 1; text-align: center; background: #fff; border: 1px solid var(--cream-2); border-radius: .6rem; padding: .3rem 0; line-height: 1.4; }
        .day.on { background: var(--gold); border-color: var(--gold); }
        .slots { display: flex; flex-wrap: wrap; gap: .3rem; }
        .slot { border: 1px solid var(--gold); border-radius: 999px; padding: .1rem .55rem; }
        .slot.on { background: var(--ink); color: var(--champagne); border-color: var(--ink); }
        .slot.off { border-color: var(--cream-2); color: #b9ad97; text-decoration: line-through; }
        .phone-cta { margin: .3rem .7rem .9rem; background: var(--gold); border-radius: .8rem; text-align: center; padding: .55rem; font-weight: 700; }

        /* ---------- sections ---------- */
        section { padding: 5rem 0; }
        .sec-head { max-width: 44rem; margin-bottom: 2.8rem; }
        .sec-head p { margin-top: .8rem; color: #5b4a3a; font-size: 1.05rem; }
        .dark { background: var(--ink); color: var(--cream); }
        .dark .sec-head p { color: var(--text-dim); }

        /* steps — واقعاً یک ترتیب زمانیه، پس شماره‌گذاری معنی‌داره */
        .steps { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0; counter-reset: step; border-top: 1px solid #d9cbb0; }
        @media (max-width: 860px) { .steps { grid-template-columns: 1fr; } }
        .step { padding: 1.8rem 1.4rem 0 1.4rem; border-left: 1px solid #d9cbb0; }
        .step:last-child { border-left: 0; }
        @media (max-width: 860px) { .step { border-left: 0; border-bottom: 1px solid #d9cbb0; padding-bottom: 1.6rem; } }
        .step b { font-family: var(--display); font-weight: 900; font-size: 2.2rem; color: var(--gold); display: block; line-height: 1; margin-bottom: .8rem; }
        .step h3 { font-size: 1.2rem; margin-bottom: .45rem; }
        .step p { color: #5b4a3a; }

        /* features by audience */
        .aud { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.4rem; }
        @media (max-width: 960px) { .aud { grid-template-columns: 1fr; } }
        .aud-col { border-radius: 1.2rem; padding: 1.7rem 1.5rem; }
        .aud-col h3 { font-size: 1.3rem; }
        .aud-col .who { font-size: .92rem; margin-top: .3rem; margin-bottom: 1.1rem; }
        .aud-col ul { list-style: none; padding: 0; margin: 0; display: grid; gap: .75rem; }
        .aud-col li { padding-right: 1.3rem; position: relative; font-size: .96rem; }
        .aud-col li::before { content: ""; position: absolute; right: 0; top: .72em; width: .5rem; height: .5rem; border-radius: 50%; }
        .aud-customer { background: #fff; border: 1px solid var(--cream-2); }
        .aud-customer .who { color: #8a6d3b; }
        .aud-customer li::before { background: var(--gold); }
        .aud-specialist { background: var(--plum-surface); color: var(--plum-text); }
        .aud-specialist .who { color: var(--plum-light); }
        .aud-specialist li::before { background: var(--plum-light); }
        .aud-admin { background: var(--slate-bg); border: 1px solid var(--slate-border); color: var(--slate-text); }
        .aud-admin .who { color: var(--slate-dim); }
        .aud-admin li::before { background: #334155; }

        .platform { margin-top: 2.2rem; display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; }
        @media (max-width: 860px) { .platform { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 480px) { .platform { grid-template-columns: 1fr; } }
        .platform div { border-top: 2px solid var(--gold); padding-top: .8rem; }
        .platform strong { display: block; font-size: 1rem; }
        .platform span { font-size: .9rem; color: #5b4a3a; }

        /* preview stage */
        .tabs { display: inline-flex; gap: .3rem; background: var(--cocoa); padding: .3rem; border-radius: 999px; border: 1px solid rgba(201,162,75,.2); margin-bottom: 1.8rem; flex-wrap: wrap; }
        .tab { background: transparent; border: 0; color: var(--text-dim); padding: .55rem 1.2rem; border-radius: 999px; font: 600 .95rem var(--body); cursor: pointer; }
        .tab[aria-selected="true"] { background: var(--gold); color: var(--ink); }
        .stage { border-radius: 1.2rem; overflow: hidden; box-shadow: 0 40px 90px -40px rgba(0,0,0,.9); border: 1px solid rgba(201,162,75,.2); }
        .stage-caption { margin-top: 1.1rem; color: var(--text-dim); max-width: 46rem; }
        .browser-bar { background: #2a2019; display: flex; gap: .4rem; align-items: center; padding: .55rem .9rem; direction: ltr; }
        .browser-bar i { width: 10px; height: 10px; border-radius: 50%; background: #5a4a3a; display: inline-block; }
        .browser-bar span { margin-left: .8rem; font: .75rem ui-monospace, Menlo, Consolas, monospace; color: #b8a58a; }
        .pane[hidden] { display: none; }
        .mock { display: grid; min-height: 430px; font-size: .8rem; }
        .mock-admin { grid-template-columns: 190px 1fr; background: var(--slate-bg); color: var(--slate-text); }
        .mock-spec { grid-template-columns: 190px 1fr; background: var(--plum-bg); color: var(--plum-text); }
        @media (max-width: 720px) { .mock-admin, .mock-spec { grid-template-columns: 1fr; } .side { display: none; } }
        .side { padding: 1rem .8rem; order: -1; }
        .mock-admin .side { background: #fff; border-left: 1px solid var(--slate-border); }
        .mock-spec .side { background: var(--plum-surface); border-left: 1px solid var(--plum-border); }
        .side .logo { font-family: var(--display); font-size: 1.05rem; margin-bottom: 1rem; }
        .side a { display: block; padding: .38rem .6rem; border-radius: .5rem; text-decoration: none; margin-bottom: .15rem; }
        .mock-admin .side a.on { background: var(--slate-text); color: #fff; }
        .mock-spec .side a.on { background: linear-gradient(135deg, #D8AEE0, #A85FB8); color: #250D2B; }
        .main { padding: 1.1rem; min-width: 0; }
        .main h5 { margin: 0 0 .8rem; font-size: .95rem; font-family: var(--display); }
        .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: .6rem; margin-bottom: .9rem; }
        @media (max-width: 720px) { .stats { grid-template-columns: repeat(2, 1fr); } }
        .stat { border-radius: .7rem; padding: .7rem; }
        .stat small { display: block; opacity: .7; }
        .stat b { font-size: 1.1rem; }
        .mock-admin .stat, .mock-admin .panel { background: #fff; border: 1px solid var(--slate-border); }
        .mock-spec .stat, .mock-spec .panel { background: var(--plum-surface); border: 1px solid var(--plum-border); }
        .panels { display: grid; grid-template-columns: 1.5fr 1fr; gap: .6rem; }
        @media (max-width: 720px) { .panels { grid-template-columns: 1fr; } }
        .panel { border-radius: .7rem; padding: .7rem; min-width: 0; }
        .panel h6 { margin: 0 0 .5rem; font-size: .82rem; }
        table.mt { width: 100%; border-collapse: collapse; }
        .mt td { padding: .35rem .2rem; border-top: 1px solid var(--slate-border); white-space: nowrap; }
        .mock-spec .mt td { border-color: var(--plum-border); }
        .chip { display: inline-block; padding: .05rem .5rem; border-radius: 999px; font-size: .7rem; }
        .c-ok { background: #DCFCE7; color: #166534; }
        .c-wait { background: #FEF3C7; color: #92400E; }
        .c-cancel { background: #FEE2E2; color: #991B1B; }
        .bars { display: flex; align-items: flex-end; gap: .35rem; height: 120px; padding-top: .4rem; }
        .bars span { flex: 1; border-radius: .3rem .3rem 0 0; }
        .mock-admin .bars span { background: #334155; }
        .mock-spec .bars span { background: #B98FC4; }
        .mock-cust { background: var(--cream); color: var(--ink); display: block; min-height: 0; }
        .cust-nav { display: flex; justify-content: space-between; align-items: center; padding: .8rem 1.2rem; background: #fff; border-bottom: 1px solid var(--cream-2); }
        .cust-nav b { font-family: var(--display); font-size: 1.05rem; color: #8a6d3b; }
        .cust-nav nav { display: flex; gap: 1rem; }
        .cust-hero { margin: 1rem 1.2rem; border-radius: 1rem; padding: 1.8rem; color: var(--cream); background: linear-gradient(120deg, #2E2117 0%, #6b5033 100%); }
        .cust-hero strong { font-family: var(--display); font-size: 1.5rem; color: var(--champagne); display: block; }
        .cust-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: .7rem; padding: 0 1.2rem 1.2rem; }
        @media (max-width: 720px) { .cust-grid { grid-template-columns: repeat(2, 1fr); } }
        .cust-card { background: #fff; border: 1px solid var(--cream-2); border-radius: .9rem; padding: .8rem; }
        .cust-card .ph { height: 58px; border-radius: .6rem; background: linear-gradient(135deg, var(--cream-2), #d8c49c); margin-bottom: .5rem; }
        .cust-card .price { color: #8a6d3b; font-weight: 700; }

        /* pricing */
        .same-all { display: flex; flex-wrap: wrap; gap: .5rem 1.5rem; margin: -1rem 0 2.2rem; color: #5b4a3a; font-size: .95rem; }
        .same-all span::before { content: "✓"; color: var(--gold); margin-left: .35rem; font-weight: 700; }
        .plans { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; align-items: stretch; }
        @media (max-width: 1000px) { .plans { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 540px) { .plans { grid-template-columns: 1fr; } }
        .plan { background: #fff; border: 1px solid var(--cream-2); border-radius: 1.1rem; padding: 1.5rem 1.3rem; display: flex; flex-direction: column; position: relative; }
        .plan.best { background: var(--ink); color: var(--cream); border-color: var(--gold); }
        .plan .ribbon { position: absolute; top: -.8rem; right: 1.2rem; background: var(--gold); color: var(--ink); font-size: .8rem; font-weight: 700; padding: .15rem .8rem; border-radius: 999px; }
        .plan h3 { font-size: 1.25rem; }
        .plan .price { font-size: 1.9rem; font-weight: 800; margin-top: .9rem; line-height: 1.2; }
        .plan .price small { font-size: .85rem; font-weight: 500; opacity: .7; }
        .plan .per { font-size: .9rem; opacity: .75; margin-top: .25rem; }
        .plan .save { margin-top: .6rem; font-size: .88rem; color: #15803d; min-height: 1.5em; }
        .plan.best .save { color: var(--ok); }
        .plan .actions { margin-top: auto; padding-top: 1.3rem; display: grid; gap: .5rem; }
        .plan .actions .btn { width: 100%; }
        .btn-preview { background: transparent; border: 1px solid #d9cbb0; color: inherit; }
        .plan.best .btn-preview { border-color: rgba(201,162,75,.45); }
        .btn-preview:hover { border-color: var(--gold); }
        .buy-now { text-align: center; font-size: .88rem; text-decoration: underline; text-underline-offset: 4px; opacity: .8; padding: .2rem 0; }
        .buy-now:hover { opacity: 1; color: var(--gold); }

        dialog { border: 0; border-radius: 1.2rem; padding: 0; width: min(640px, 100% - 1.5rem); max-height: calc(100vh - 2rem); color: var(--ink); background: var(--cream); }
        dialog::backdrop { background: rgba(26,20,16,.7); }
        .dlg-head { background: var(--ink); color: var(--cream); padding: 1.3rem 1.5rem; display: flex; justify-content: space-between; align-items: start; gap: 1rem; }
        .dlg-head h3 { color: var(--champagne); font-size: 1.35rem; }
        .dlg-close { background: transparent; border: 1px solid rgba(248,243,233,.3); color: var(--cream); border-radius: 50%; width: 34px; height: 34px; cursor: pointer; font-size: 1rem; flex-shrink: 0; }
        .dlg-body { padding: 1.4rem 1.5rem 1.6rem; }
        .timeline { display: flex; border-radius: .7rem; overflow: hidden; font-size: .82rem; margin: .5rem 0 .4rem; }
        .timeline .t-trial { background: var(--champagne); padding: .55rem .7rem; min-width: 34%; }
        .timeline .t-paid { background: var(--ink); color: var(--cream); padding: .55rem .7rem; flex: 1; }
        .tl-dates { display: flex; justify-content: space-between; font-size: .8rem; color: #6b5a47; }
        .dlg-grid { display: grid; grid-template-columns: 1fr 1fr; gap: .7rem; margin-top: 1.2rem; }
        @media (max-width: 480px) { .dlg-grid { grid-template-columns: 1fr; } }
        .dlg-grid div { background: #fff; border: 1px solid var(--cream-2); border-radius: .8rem; padding: .75rem .9rem; }
        .dlg-grid small { display: block; color: #6b5a47; }
        .dlg-grid b { font-size: 1.05rem; }
        .dlg-note { margin-top: 1rem; font-size: .88rem; color: #5b4a3a; }
        .dlg-actions { margin-top: 1.3rem; display: flex; gap: .6rem; flex-wrap: wrap; }

        /* faq */
        .faq { max-width: 50rem; }
        .faq details { border-bottom: 1px solid #d9cbb0; padding: 1.1rem 0; }
        .faq summary { cursor: pointer; font-weight: 700; font-size: 1.05rem; list-style: none; display: flex; justify-content: space-between; gap: 1rem; }
        .faq summary::-webkit-details-marker { display: none; }
        .faq summary::after { content: "+"; color: var(--gold); font-size: 1.3rem; line-height: 1; }
        .faq details[open] summary::after { content: "−"; }
        .faq details p { margin-top: .7rem; color: #5b4a3a; }

        .final { text-align: center; }
        .final h2 { color: var(--champagne); }
        .final p { color: var(--text-dim); margin: 1rem auto 2rem; max-width: 34rem; }
        .final .btns { display: flex; gap: .8rem; justify-content: center; flex-wrap: wrap; }
        footer { background: #120e0b; color: rgba(248,243,233,.55); font-size: .88rem; padding: 1.6rem 0; }
        footer .wrap { display: flex; justify-content: space-between; flex-wrap: wrap; gap: 1rem; }
        footer a { text-decoration: none; }
        footer a:hover { color: var(--champagne); }

        @media (prefers-reduced-motion: reduce) { html { scroll-behavior: auto; } }
    </style>
</head>
<body>

<header class="top">
    <div class="wrap">
        <a class="brand" href="{{ route('central.home') }}">
            <img class="brand-logo" src="{{ asset('brand/mahru-logo-horizontal-on-dark.svg') }}" alt="{{ config('brand.name') }}" height="46">
        </a>
        <nav class="nav" aria-label="بخش‌های صفحه">
            <a href="#how">روند راه‌اندازی</a>
            <a href="#features">امکانات</a>
            <a href="#preview">پیش‌نمایش</a>
            <a href="#pricing">تعرفه</a>
            <a href="#faq">سؤالات</a>
        </nav>
        <div class="top-actions">
            <a class="btn btn-line" href="{{ route('login') }}">ورود مدیر سالن</a>
            <a class="btn btn-gold" href="{{ route('salon-signup.create') }}">ساخت سالن</a>
        </div>
    </div>
</header>

<main>
    {{-- ================= HERO ================= --}}
    <section class="hero" aria-labelledby="hero-title">
        <div class="wrap">
            <div>
                <h1 id="hero-title">سالن شما، با آدرس رزرو آنلاین خودش</h1>
                <p class="hero-lead">
                    {{ config('brand.name') }} برای سالن شما یک سایت نوبت‌دهی اختصاصی می‌سازد. مشتری‌ها خدمت، متخصص و ساعت را خودشان
                    انتخاب می‌کنند و پیش‌پرداخت می‌دهند؛ شما و متخصص‌هایتان هر کدام پنل خودتان را دارید.
                </p>

                <div class="composer">
                    <label for="slug-input">اول آدرس سالنتان را انتخاب کنید</label>
                    <div class="address">
                        @if ($addressPrefix !== '')
                            <span class="fixed">{{ $addressPrefix }}</span>
                        @endif
                        <input id="slug-input" type="text" inputmode="url" autocomplete="off" spellcheck="false"
                               maxlength="100" placeholder="almas-beauty" aria-describedby="slug-status">
                        @if ($addressSuffix !== '')
                            <span class="fixed">{{ $addressSuffix }}</span>
                        @endif
                        <a id="slug-cta" class="btn btn-gold" href="{{ route('salon-signup.create') }}">
                            @if ($trialDays > 0) شروع {{ to_persian_num((string) $trialDays) }} روز رایگان @else ساخت سالن @endif
                        </a>
                    </div>
                    <div id="slug-status" class="composer-status" role="status" aria-live="polite">
                        فقط حروف انگلیسی، عدد و خط تیره؛ بعد از ساخت قابل تغییر نیست.
                    </div>
                </div>

                <div class="hero-facts">
                    @if ($trialDays > 0)
                        <span>{{ to_persian_num((string) $trialDays) }} روز استفاده‌ی کامل و رایگان</span>
                        <span>بدون نیاز به کارت بانکی برای شروع</span>
                    @endif
                    <span>راه‌اندازی در چند دقیقه</span>
                    <span>فارسی، راست‌به‌چپ و تقویم شمسی</span>
                </div>
            </div>

            <div class="phone" aria-label="نمونه‌ی صفحه‌ی رزرو مشتری روی موبایل" role="img">
                <div class="phone-screen">
                    <div class="phone-hero">
                        <strong>سالن زیبایی شما</strong>
                        نوبت بعدی‌تان را در کمتر از یک دقیقه رزرو کنید
                    </div>
                    <div class="phone-sec">
                        <h4>خدمت</h4>
                        <div class="svc-row">
                            <div class="svc"><i>✂</i>رنگ و لایت</div>
                            <div class="svc"><i>✦</i>کاشت ناخن</div>
                            <div class="svc"><i>❀</i>پاکسازی پوست</div>
                        </div>
                    </div>
                    <div class="phone-sec">
                        <h4>روز</h4>
                        <div class="days">
                            <div class="day">شنبه<br>۵</div>
                            <div class="day on">یکشنبه<br>۶</div>
                            <div class="day">دوشنبه<br>۷</div>
                            <div class="day">سه‌شنبه<br>۸</div>
                        </div>
                    </div>
                    <div class="phone-sec">
                        <h4>ساعت‌های آزاد متخصص</h4>
                        <div class="slots">
                            <span class="slot off">۱۰:۰۰</span>
                            <span class="slot">۱۱:۳۰</span>
                            <span class="slot on">۱۳:۰۰</span>
                            <span class="slot">۱۶:۳۰</span>
                            <span class="slot">۱۸:۰۰</span>
                        </div>
                    </div>
                    <div class="phone-cta">پیش‌پرداخت و ثبت نوبت</div>
                </div>
            </div>
        </div>
    </section>

    {{-- ================= HOW ================= --}}
    <section id="how" aria-labelledby="how-title">
        <div class="wrap">
            <div class="sec-head">
                <h2 id="how-title">از ثبت‌نام تا اولین نوبت آنلاین</h2>
                <p>بدون نصب نرم‌افزار و بدون نیاز به دانش فنی. همه‌چیز در مرورگر انجام می‌شود.</p>
            </div>
            <div class="steps">
                <div class="step">
                    <b>۱</b>
                    <h3>سالن را بسازید</h3>
                    <p>نام سالن، آدرس دلخواه و شماره موبایلتان را وارد کنید و با کد پیامکی تایید کنید.
                        @if ($trialDays > 0) دوره‌ی {{ to_persian_num((string) $trialDays) }} روزه‌ی رایگان همان لحظه شروع می‌شود. @endif
                    </p>
                </div>
                <div class="step">
                    <b>۲</b>
                    <h3>سالن را تنظیم کنید</h3>
                    <p>خدمات و قیمت‌ها، متخصص‌ها و ساعت کاری هر کدام را از پنل مدیریت وارد کنید. هر متخصص پنل اختصاصی خودش را می‌گیرد.</p>
                </div>
                <div class="step">
                    <b>۳</b>
                    <h3>آدرس را به مشتری‌ها بدهید</h3>
                    <p>آدرس اختصاصی سالن در پنل شما نمایش داده می‌شود؛ آن را در اینستاگرام، واتساپ یا کارت ویزیت بگذارید تا مشتری‌ها آنلاین نوبت بگیرند.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ================= FEATURES ================= --}}
    <section id="features" aria-labelledby="features-title" style="padding-top: 1rem;">
        <div class="wrap">
            <div class="sec-head">
                <h2 id="features-title">همه‌ی امکانات، در همه‌ی پلن‌ها</h2>
                <p>{{ config('brand.name') }} سه پنل جدا دارد، برای سه نفری که هر روز با سالن سروکار دارند. پلن‌ها فقط در مدت اشتراک فرق دارند، نه در امکانات.</p>
            </div>

            <div class="aud">
                <div class="aud-col aud-customer">
                    <h3>سایت رزرو مشتری‌ها</h3>
                    <p class="who">روی آدرس اختصاصی سالن شما</p>
                    <ul>
                        <li>انتخاب خدمت، متخصص، روز و ساعت آزاد با تقویم شمسی</li>
                        <li>پیش‌پرداخت آنلاین نوبت از درگاه زرین‌پال؛ باقی مبلغ حضوری</li>
                        <li>ورود با کد پیامکی، بدون نیاز به ایمیل و رمز</li>
                        <li>پیامک تایید نوبت و یادآوری حدود یک ساعت قبل از نوبت</li>
                        <li>جابه‌جایی یا لغو نوبت طبق قوانینی که شما تعیین می‌کنید</li>
                        <li>کد تخفیف، باشگاه مشتریان و امتیاز وفاداری با جایزه</li>
                        <li>کیف پول مشتری برای برگشت وجه</li>
                        <li>ثبت نظر و امتیاز بعد از نوبت با لینک پیامکی</li>
                        <li>صفحه‌ی متخصص‌ها، گالری نمونه‌کار، وبلاگ و اطلاعیه‌های سالن</li>
                    </ul>
                </div>

                <div class="aud-col aud-specialist">
                    <h3>پنل متخصص</h3>
                    <p class="who">برای هر آرایشگر یا متخصص سالن</p>
                    <ul>
                        <li>داشبورد نوبت‌های امروز و آینده با وضعیت هر نوبت</li>
                        <li>تایید دستی یا خودکار نوبت‌های جدید</li>
                        <li>تعیین برنامه‌ی کاری هفتگی و ثبت مرخصی</li>
                        <li>کیف پول درآمد با کمیسیون شفاف و درخواست برداشت وجه</li>
                        <li>گزارش درآمد و عملکرد با فیلتر تاریخ شمسی</li>
                        <li>مشاهده و پاسخ به نظرات مشتری‌ها</li>
                        <li>اعلان‌های لحظه‌ای نوبت جدید، لغو و تسویه</li>
                        <li>ویرایش اطلاعات حساب: نام، موبایل و رمز عبور</li>
                    </ul>
                </div>

                <div class="aud-col aud-admin">
                    <h3>پنل مدیریت سالن</h3>
                    <p class="who">برای شما و منشی سالن</p>
                    <ul>
                        <li>داشبورد درآمد، نوبت‌ها و متخصص‌های فعال با نمودار</li>
                        <li>ثبت نوبت دستی (تلفنی/حضوری) بدون تداخل با نوبت آنلاین</li>
                        <li>مدیریت خدمات، دسته‌بندی‌ها، قیمت و مدت هر خدمت</li>
                        <li>تعریف متخصص با کمیسیون اختصاصی و برنامه‌ی کاری</li>
                        <li>تسویه‌ی کیف پول متخصص‌ها؛ دستی یا آنلاین با زرین‌پال</li>
                        <li>گزارش مالی و خروجی Excel و PDF</li>
                        <li>مدیریت کد تخفیف، باشگاه مشتریان، گالری، وبلاگ و اطلاعیه</li>
                        <li>چند مدیر برای یک سالن: مالک و منشی با دسترسی محدودتر</li>
                        <li>تنظیمات پیامک‌ها، پنل امنیت و تیکت پشتیبانی با تیم {{ config('brand.name') }}</li>
                    </ul>
                </div>
            </div>

            <div class="platform">
                <div>
                    <strong>آدرس اختصاصی</strong>
                    <span>هر سالن آدرس جدا دارد و داده‌هایش کاملاً از سالن‌های دیگر جداست.</span>
                </div>
                <div>
                    <strong>تا {{ to_persian_num((string) $maxSpecialists) }} متخصص</strong>
                    <span>برای شروع؛ برای سالن‌های بزرگ‌تر از طریق پشتیبانی افزایش می‌یابد.</span>
                </div>
                <div>
                    <strong>{{ to_persian_num(number_format($smsQuota)) }} پیامک در ماه</strong>
                    <span>پیامک‌های تایید، یادآوری و ورود؛ بدون هزینه‌ی جداگانه.</span>
                </div>
                <div>
                    <strong>پرداخت امن</strong>
                    <span>درگاه زرین‌پال و تایید دومرحله‌ای برای پرداخت‌ها.</span>
                </div>
            </div>
        </div>
    </section>

    {{-- ================= PREVIEW ================= --}}
    <section id="preview" class="dark" aria-labelledby="preview-title">
        <div class="wrap">
            <div class="sec-head">
                <h2 id="preview-title">قبل از خرید ببینید</h2>
                <p>سه پنلی که بعد از ساخت سالن در اختیار شما، متخصص‌ها و مشتری‌هایتان قرار می‌گیرد.</p>
            </div>

            <div class="tabs" role="tablist" aria-label="پیش‌نمایش پنل‌ها">
                <button class="tab" role="tab" id="tab-customer" aria-controls="pane-customer" aria-selected="true">سایت مشتری</button>
                <button class="tab" role="tab" id="tab-specialist" aria-controls="pane-specialist" aria-selected="false" tabindex="-1">پنل متخصص</button>
                <button class="tab" role="tab" id="tab-admin" aria-controls="pane-admin" aria-selected="false" tabindex="-1">پنل مدیریت</button>
            </div>

            <div class="stage">
                <div class="browser-bar" aria-hidden="true"><i></i><i></i><i></i></div>

                {{-- سایت مشتری --}}
                <div class="pane" id="pane-customer" role="tabpanel" aria-labelledby="tab-customer">
                    <div class="mock mock-cust">
                        <div class="cust-nav">
                            <b>سالن زیبایی شما</b>
                            <nav><span>خدمات</span><span>متخصص‌ها</span><span>گالری</span><span>ورود</span></nav>
                        </div>
                        <div class="cust-hero">
                            <strong>زیبایی، با وقت قبلی</strong>
                            خدمت و متخصص دلخواهتان را انتخاب کنید و همین حالا نوبت بگیرید.
                        </div>
                        <div class="cust-grid">
                            <div class="cust-card"><div class="ph"></div>رنگ و لایت مو<div class="price">از ۲,۵۰۰,۰۰۰ تومان</div></div>
                            <div class="cust-card"><div class="ph"></div>کاشت ناخن<div class="price">از ۹۰۰,۰۰۰ تومان</div></div>
                            <div class="cust-card"><div class="ph"></div>پاکسازی پوست<div class="price">از ۱,۲۰۰,۰۰۰ تومان</div></div>
                            <div class="cust-card"><div class="ph"></div>میکاپ و شینیون<div class="price">از ۳,۸۰۰,۰۰۰ تومان</div></div>
                        </div>
                    </div>
                </div>

                {{-- پنل متخصص --}}
                <div class="pane" id="pane-specialist" role="tabpanel" aria-labelledby="tab-specialist" hidden>
                    <div class="mock mock-spec">
                        <div class="main">
                            <h5>سلام نرگس، امروز ۶ نوبت دارید</h5>
                            <div class="stats">
                                <div class="stat"><small>نوبت‌های امروز</small><b>۶</b></div>
                                <div class="stat"><small>درآمد این ماه</small><b>۱۲,۵۸۰,۰۰۰</b></div>
                                <div class="stat"><small>موجودی کیف پول</small><b>۸,۷۶۰,۰۰۰</b></div>
                                <div class="stat"><small>امتیاز مشتری‌ها</small><b>۴.۹</b></div>
                            </div>
                            <div class="panels">
                                <div class="panel">
                                    <h6>نوبت‌های امروز</h6>
                                    <table class="mt">
                                        <tr><td>۱۰:۰۰</td><td>سارا احمدی</td><td>رنگ و لایت</td><td><span class="chip c-ok">تایید شده</span></td></tr>
                                        <tr><td>۱۲:۳۰</td><td>مریم کریمی</td><td>کوتاهی</td><td><span class="chip c-ok">تایید شده</span></td></tr>
                                        <tr><td>۱۵:۰۰</td><td>الهام رضایی</td><td>براشینگ</td><td><span class="chip c-wait">در انتظار</span></td></tr>
                                        <tr><td>۱۷:۳۰</td><td>نگار حسینی</td><td>کراتین</td><td><span class="chip c-wait">در انتظار</span></td></tr>
                                    </table>
                                </div>
                                <div class="panel">
                                    <h6>درآمد هفته</h6>
                                    <div class="bars" aria-hidden="true">
                                        <span style="height:45%"></span><span style="height:62%"></span><span style="height:38%"></span>
                                        <span style="height:80%"></span><span style="height:55%"></span><span style="height:92%"></span><span style="height:70%"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="side">
                            <div class="logo">پنل متخصص</div>
                            <a class="on">داشبورد</a><a>نوبت‌های من</a><a>برنامه‌ی کاری</a><a>مرخصی‌ها</a><a>کیف پول</a><a>نظرات</a><a>گزارش‌ها</a>
                        </div>
                    </div>
                </div>

                {{-- پنل مدیریت --}}
                <div class="pane" id="pane-admin" role="tabpanel" aria-labelledby="tab-admin" hidden>
                    <div class="mock mock-admin">
                        <div class="main">
                            <h5>داشبورد سالن</h5>
                            <div class="stats">
                                <div class="stat"><small>نوبت‌های امروز</small><b>۴۸</b></div>
                                <div class="stat"><small>درآمد ماه (تومان)</small><b>۱۲۸,۷۵۰,۰۰۰</b></div>
                                <div class="stat"><small>متخصص فعال</small><b>۱۲</b></div>
                                <div class="stat"><small>مشتری جدید</small><b>۳۶</b></div>
                            </div>
                            <div class="panels">
                                <div class="panel">
                                    <h6>آخرین نوبت‌ها</h6>
                                    <table class="mt">
                                        <tr><td>سارا محمدی</td><td>فیشیال</td><td>نگین احمدی</td><td><span class="chip c-ok">تکمیل شده</span></td></tr>
                                        <tr><td>مریم حسینی</td><td>لیزر</td><td>تینا کریمی</td><td><span class="chip c-wait">در انتظار</span></td></tr>
                                        <tr><td>فاطمه رضایی</td><td>پاکسازی</td><td>نگین احمدی</td><td><span class="chip c-ok">تکمیل شده</span></td></tr>
                                        <tr><td>لیلا امیری</td><td>طراحی ابرو</td><td>مینا کریمی</td><td><span class="chip c-cancel">لغو شده</span></td></tr>
                                    </table>
                                </div>
                                <div class="panel">
                                    <h6>درآمد ۳۰ روز اخیر</h6>
                                    <div class="bars" aria-hidden="true">
                                        <span style="height:50%"></span><span style="height:68%"></span><span style="height:58%"></span><span style="height:74%"></span>
                                        <span style="height:66%"></span><span style="height:88%"></span><span style="height:60%"></span><span style="height:95%"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="side">
                            <div class="logo">مدیریت سالن</div>
                            <a class="on">داشبورد</a><a>نوبت‌ها</a><a>متخصص‌ها</a><a>خدمات</a><a>مالی و کیف پول</a><a>گزارش‌ها</a><a>باشگاه مشتریان</a><a>کد تخفیف</a><a>اشتراک و صورتحساب</a>
                        </div>
                    </div>
                </div>
            </div>
            <p class="stage-caption" id="stage-caption">صفحه‌ای که مشتری‌های شما روی آدرس اختصاصی سالن می‌بینند؛ روی موبایل هم کامل کار می‌کند.</p>
        </div>
    </section>

    {{-- ================= PRICING ================= --}}
    <section id="pricing" aria-labelledby="pricing-title">
        <div class="wrap">
            <div class="sec-head">
                <h2 id="pricing-title">تعرفه‌ی اشتراک</h2>
                <p>
                    @if ($trialDays > 0)
                        اول {{ to_persian_num((string) $trialDays) }} روز رایگان استفاده کنید و پلن را بعداً از داخل پنل سالن بخرید،
                        یا اگر دوره‌ی رایگان نمی‌خواهید، همین حالا با «خرید فوری» پلن را بخرید و آنلاین پرداخت کنید.
                    @else
                        پلن را بعد از ساخت سالن، از داخل پنل سالن و با درگاه زرین‌پال می‌خرید.
                    @endif
                    هرچه دوره بلندتر، هزینه‌ی هر ماه کمتر.
                </p>
            </div>

            <div class="same-all">
                <span>همه‌ی امکانات سه پنل</span>
                <span>تا {{ to_persian_num((string) $maxSpecialists) }} متخصص</span>
                <span>{{ to_persian_num(number_format($smsQuota)) }} پیامک در هر ماه</span>
                <span>تیکت پشتیبانی</span>
            </div>

            <div class="plans">
                @foreach ($plans as $plan)
                    <article class="plan {{ $plan['type'] === '6m' ? 'best' : '' }}" aria-labelledby="plan-{{ $plan['type'] }}">
                        @if ($plan['type'] === '6m')
                            <span class="ribbon">انتخاب بیشتر سالن‌ها</span>
                        @endif
                        <h3 id="plan-{{ $plan['type'] }}">{{ $plan['label'] }}</h3>
                        <div class="price num">{{ to_persian_num(number_format($plan['price'])) }} <small>تومان</small></div>
                        <div class="per">
                            @if ($plan['months'] > 1)
                                ماهانه حدود {{ to_persian_num(number_format($plan['per_month'])) }} تومان
                            @else
                                پرداخت ماه‌به‌ماه
                            @endif
                        </div>
                        <div class="save">
                            @if ($plan['saving'] > 0)
                                {{ to_persian_num((string) $plan['saving_percent']) }} درصد صرفه‌جویی ({{ to_persian_num(number_format($plan['saving'])) }} تومان)
                            @endif
                        </div>
                        <div class="actions">
                            <button type="button" class="btn btn-preview" data-plan-preview="{{ $plan['type'] }}">پیش‌نمایش پلن</button>
                            @if ($trialDays > 0)
                                <a class="btn {{ $plan['type'] === '6m' ? 'btn-gold' : 'btn-ink' }}" href="{{ $plan['signup_url'] }}" data-plan-link="{{ $plan['type'] }}" data-link-kind="signup">
                                    شروع {{ to_persian_num((string) $trialDays) }} روز رایگان
                                </a>
                                <a class="buy-now" href="{{ $plan['buy_url'] }}" data-plan-link="{{ $plan['type'] }}" data-link-kind="buy">
                                    خرید فوری، بدون دوره‌ی رایگان
                                </a>
                            @else
                                <a class="btn {{ $plan['type'] === '6m' ? 'btn-gold' : 'btn-ink' }}" href="{{ $plan['buy_url'] }}" data-plan-link="{{ $plan['type'] }}" data-link-kind="buy">
                                    خرید و پرداخت آنلاین
                                </a>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ================= FAQ ================= --}}
    <section id="faq" style="padding-top: 1rem;" aria-labelledby="faq-title">
        <div class="wrap faq">
            <div class="sec-head">
                <h2 id="faq-title">سؤالات رایج</h2>
            </div>
            @if ($trialDays > 0)
                <details>
                    <summary>بعد از تمام شدن دوره‌ی رایگان چه می‌شود؟</summary>
                    <p>
                        پنل مدیریت فقط صفحه‌ی خرید اشتراک را نشان می‌دهد و آدرس رزرو سالن برای مشتری‌ها موقتاً بسته می‌شود.
                        هیچ اطلاعاتی پاک نمی‌شود؛ به محض خرید، همه‌چیز همان‌طور که بود ادامه پیدا می‌کند. اگر وسط دوره‌ی رایگان
                        بخرید، اشتراک از همان روز خرید فعال می‌شود و دوره‌ی رایگان همان لحظه به پایان می‌رسد.
                    </p>
                </details>
                <details>
                    <summary>در دوره‌ی رایگان محدودیتی هست؟</summary>
                    <p>
                        همه‌ی امکانات باز است. تنها تفاوت، سقف پیامک است: در دوره‌ی رایگان
                        {{ to_persian_num(number_format($trialSmsQuota)) }} پیامک در ماه و بعد از خرید
                        {{ to_persian_num(number_format($smsQuota)) }} پیامک در ماه.
                    </p>
                </details>
            @endif
            @if ($trialDays > 0)
            <details>
                <summary>اگر دوره‌ی رایگان نخواهم و همین حالا بخواهم اشتراک بخرم؟</summary>
                <p>
                    روی کارت پلن دلخواه، گزینه‌ی «خرید فوری، بدون دوره‌ی رایگان» را بزنید. فرم ساخت سالن را پر می‌کنید، شماره موبایل را
                    با کد پیامکی تایید می‌کنید و بلافاصله به درگاه پرداخت امن زرین‌پال هدایت می‌شوید. بعد از پرداخت موفق،
                    اشتراک از همان لحظه فعال است و آدرس رزرو سالن در پنل به شما نمایش داده می‌شود. اگر پرداخت نیمه‌کاره
                    بماند، سالن شما از بین نمی‌رود و می‌توانید از صفحه‌ی خرید داخل پنل دوباره پرداخت کنید.
                </p>
            </details>
            @endif
            <details>
                <summary>آدرس سالن را از کجا بگیرم؟</summary>
                <p>
                    آدرسی که هنگام ساخت سالن انتخاب می‌کنید، آدرس رزرو شماست. بعد از ورود، در داشبورد پنل مدیریت و صفحه‌ی
                    «اشتراک و صورتحساب» همراه با دکمه‌ی کپی نمایش داده می‌شود. این آدرس بعد از ساخت قابل تغییر نیست تا
                    لینک‌هایی که قبلاً برای مشتری‌ها فرستاده‌اید هیچ‌وقت خراب نشوند.
                </p>
            </details>
            <details>
                <summary>پول پیش‌پرداخت مشتری‌ها به حساب چه کسی واریز می‌شود؟</summary>
                <p>
                    مستقیم به حساب زرین‌پال خود سالن. کد پذیرنده‌ی زرین‌پال (Merchant ID) را موقع ساخت سالن یا بعداً از
                    «پنل مدیریت ← اطلاعات سالن» وارد می‌کنید. تا این کد وارد نشود، هیچ پرداخت آنلاینی (پیش‌پرداخت نوبت،
                    پرداخت باقی‌مانده، شارژ کیف پول) برای مشتری‌های سالن ممکن نیست.
                </p>
            </details>
            <details>
                <summary>اگر بیشتر از {{ to_persian_num((string) $maxSpecialists) }} متخصص داشته باشم؟</summary>
                <p>از پنل مدیریت یک تیکت پشتیبانی ثبت کنید تا سقف متخصص‌های سالن شما افزایش یابد.</p>
            </details>
            <details>
                <summary>می‌توانم برای منشی سالن هم دسترسی بسازم؟</summary>
                <p>بله. مالک سالن می‌تواند مدیر دوم یا منشی اضافه کند؛ منشی به‌صورت پیش‌فرض به بخش‌های مالی دسترسی ندارد.</p>
            </details>
        </div>
    </section>

    {{-- ================= FINAL CTA ================= --}}
    <section class="dark final" aria-labelledby="final-title">
        <div class="wrap">
            <h2 id="final-title">سالنتان را همین امروز آنلاین کنید</h2>
            <p>
                @if ($trialDays > 0)
                    {{ to_persian_num((string) $trialDays) }} روز رایگان، بدون پرداخت در شروع.
                @endif
                ساخت سالن کمتر از پنج دقیقه طول می‌کشد.
            </p>
            <div class="btns">
                <a class="btn btn-gold" href="{{ route('salon-signup.create') }}">ساخت سالن</a>
                <a class="btn btn-line" href="{{ route('login') }}">قبلاً ثبت‌نام کرده‌ام</a>
            </div>
        </div>
    </section>
</main>

<footer>
    <div class="wrap">
        <span><img class="footer-mark" src="{{ asset('brand/mahru-mark.svg') }}" alt="" aria-hidden="true">{{ config('brand.name') }} — نرم‌افزار نوبت‌دهی آنلاین سالن زیبایی</span>
        <span><a href="#pricing">تعرفه</a> · <a href="{{ route('login') }}">ورود مدیر سالن</a></span>
    </div>
</footer>

{{-- پیش‌نمایش پلن — یک dialog مشترک که با داده‌ی همون پلن پر می‌شه --}}
<dialog id="plan-dialog" aria-labelledby="dlg-title">
    <div class="dlg-head">
        <div>
            <h3 id="dlg-title"></h3>
            <div id="dlg-price" style="margin-top:.3rem; opacity:.85;"></div>
        </div>
        <button type="button" class="dlg-close" data-close-dialog aria-label="بستن">✕</button>
    </div>
    <div class="dlg-body">
        <strong>
            @if ($trialDays > 0)
                اگر امروز ({{ $todayLabel }}) سالن بسازید و بعد از دوره‌ی رایگان این پلن را بخرید:
            @else
                اگر امروز ({{ $todayLabel }}) این پلن را بخرید:
            @endif
        </strong>
        <div class="timeline" aria-hidden="true">
            @if ($trialDays > 0)
                <div class="t-trial">{{ to_persian_num((string) $trialDays) }} روز رایگان</div>
            @endif
            <div class="t-paid" id="dlg-paid-bar"></div>
        </div>
        <div class="tl-dates">
            <span>{{ $todayLabel }}</span>
            @if ($trialDays > 0)<span id="dlg-trial-end"></span>@endif
            <span id="dlg-period-end"></span>
        </div>
        <div class="dlg-grid">
            <div><small>مدت اشتراک پولی</small><b id="dlg-months"></b></div>
            <div><small>هزینه‌ی هر ماه</small><b id="dlg-per-month"></b></div>
            <div><small>کل پیامک این دوره</small><b id="dlg-sms"></b></div>
            <div><small>سقف متخصص‌ها</small><b>{{ to_persian_num((string) $maxSpecialists) }} نفر</b></div>
        </div>
        <p class="dlg-note" id="dlg-saving"></p>
        <p class="dlg-note">
            همه‌ی امکانات سایت مشتری، پنل متخصص و پنل مدیریت در این پلن فعال است.
            @if ($trialDays > 0) خرید از داخل پنل سالن انجام می‌شود؛ اگر زودتر از پایان دوره‌ی رایگان بخرید، اشتراک از همان روز خرید شروع می‌شود. @endif
        </p>
        <div class="dlg-actions">
            @if ($trialDays > 0)
                <a class="btn btn-ink" id="dlg-cta" href="#">شروع {{ to_persian_num((string) $trialDays) }} روز رایگان</a>
            @endif
            <a class="btn {{ $trialDays > 0 ? 'btn-preview' : 'btn-ink' }}" id="dlg-buy" href="#">همین حالا بخرم و پرداخت کنم</a>
            <button type="button" class="btn btn-preview" data-close-dialog>بستن</button>
        </div>
    </div>
</dialog>

<script>
    (function () {
        var plans = @json($plans);
        var signupUrl = '{{ route('salon-signup.create') }}';
        var checkUrl = '{{ route('salon-signup.check-slug') }}';
        var faDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];

        function fa(value) {
            return String(value).replace(/[0-9]/g, function (d) { return faDigits[d]; });
        }
        function money(value) {
            return fa(Number(value).toLocaleString('en-US'));
        }

        /* ---------- آدرس دلخواه + چک زنده‌ی یکتایی (همون endpoint فرم ثبت‌نام) ---------- */
        var input = document.getElementById('slug-input');
        var status = document.getElementById('slug-status');
        var cta = document.getElementById('slug-cta');
        var defaultHint = status.textContent;
        var timer;

        function withSlug(url, slug) {
            if (!slug) return url;
            return url + (url.indexOf('?') === -1 ? '?' : '&') + 'slug=' + encodeURIComponent(slug);
        }

        function syncLinks(slug) {
            cta.href = withSlug(signupUrl, slug);
            document.querySelectorAll('[data-plan-link]').forEach(function (a) {
                var plan = plans.find(function (p) { return p.type === a.getAttribute('data-plan-link'); });
                var kind = a.getAttribute('data-link-kind') === 'buy' ? 'buy_url' : 'signup_url';
                if (plan) a.href = withSlug(plan[kind], slug);
            });
        }

        input.addEventListener('input', function () {
            var cleaned = input.value.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9_-]/g, '');
            if (cleaned !== input.value) input.value = cleaned;
            syncLinks(cleaned);

            clearTimeout(timer);
            if (!cleaned) {
                status.textContent = defaultHint;
                status.className = 'composer-status';
                return;
            }
            status.textContent = 'در حال بررسی…';
            status.className = 'composer-status';
            timer = setTimeout(function () {
                fetch(checkUrl + '?slug=' + encodeURIComponent(cleaned), { headers: { 'Accept': 'application/json' } })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (input.value !== cleaned) return;
                        if (data.available) {
                            status.textContent = '✓ این آدرس آزاد است و می‌تواند مال شما باشد';
                            status.className = 'composer-status ok';
                        } else if (data.reason === 'taken') {
                            status.textContent = '✗ این آدرس قبلاً گرفته شده؛ یکی دیگر امتحان کنید';
                            status.className = 'composer-status bad';
                        } else {
                            status.textContent = defaultHint;
                            status.className = 'composer-status';
                        }
                    })
                    .catch(function () {
                        status.textContent = defaultHint;
                        status.className = 'composer-status';
                    });
            }, 450);
        });

        /* ---------- تب‌های پیش‌نمایش (الگوی ARIA tabs با کلیدهای جهت‌دار) ---------- */
        var captions = {
            'tab-customer': 'صفحه‌ای که مشتری‌های شما روی آدرس اختصاصی سالن می‌بینند؛ روی موبایل هم کامل کار می‌کند.',
            'tab-specialist': 'هر متخصص با شماره موبایل خودش وارد می‌شود و فقط نوبت‌ها، درآمد و کیف پول خودش را می‌بیند.',
            'tab-admin': 'پنل شما برای مدیریت کل سالن: نوبت‌ها، متخصص‌ها، خدمات، امور مالی و گزارش‌ها.'
        };
        var tabs = Array.prototype.slice.call(document.querySelectorAll('[role="tab"]'));
        function selectTab(tab) {
            tabs.forEach(function (t) {
                var on = t === tab;
                t.setAttribute('aria-selected', on ? 'true' : 'false');
                t.tabIndex = on ? 0 : -1;
                document.getElementById(t.getAttribute('aria-controls')).hidden = !on;
            });
            document.getElementById('stage-caption').textContent = captions[tab.id];
        }
        tabs.forEach(function (tab, i) {
            tab.addEventListener('click', function () { selectTab(tab); });
            tab.addEventListener('keydown', function (e) {
                var next = null;
                if (e.key === 'ArrowLeft') next = tabs[(i + 1) % tabs.length];
                if (e.key === 'ArrowRight') next = tabs[(i - 1 + tabs.length) % tabs.length];
                if (next) { e.preventDefault(); next.focus(); selectTab(next); }
            });
        });

        /* ---------- پیش‌نمایش پلن ---------- */
        var dialog = document.getElementById('plan-dialog');
        function setText(id, text) {
            var el = document.getElementById(id);
            if (el) el.textContent = text;
        }
        document.querySelectorAll('[data-plan-preview]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var plan = plans.find(function (p) { return p.type === btn.getAttribute('data-plan-preview'); });
                if (!plan) return;
                setText('dlg-title', 'پلن ' + plan.label);
                setText('dlg-price', money(plan.price) + ' تومان');
                setText('dlg-paid-bar', 'اشتراک ' + plan.label);
                setText('dlg-trial-end', plan.trial_end_label);
                setText('dlg-period-end', plan.period_end_label);
                setText('dlg-months', fa(plan.months) + ' ماه');
                setText('dlg-per-month', money(plan.per_month) + ' تومان');
                setText('dlg-sms', money(plan.sms_total) + ' پیامک');
                setText('dlg-saving', plan.saving > 0
                    ? 'نسبت به تمدید ماه‌به‌ماه ' + money(plan.saving) + ' تومان (' + fa(plan.saving_percent) + ' درصد) کمتر پرداخت می‌کنید.'
                    : 'بدون تعهد بلندمدت؛ هر ماه می‌توانید تمدید کنید یا پلن بلندتر بخرید.');
                var trialCta = document.getElementById('dlg-cta');
                if (trialCta) trialCta.href = withSlug(plan.signup_url, input.value);
                document.getElementById('dlg-buy').href = withSlug(plan.buy_url, input.value);
                if (typeof dialog.showModal === 'function') {
                    dialog.showModal();
                } else {
                    window.location.href = plan.signup_url;
                }
            });
        });
        dialog.addEventListener('click', function (e) {
            if (e.target === dialog || e.target.closest('[data-close-dialog]')) dialog.close();
        });
    })();
</script>
</body>
</html>
