<!DOCTYPE html>
<html lang="fa" dir="rtl" class="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>پنل مدیریت کل {{ config('brand.name') }} | @yield('title')</title>
    @include('partials.platform-icons')

    @vite(['resources/css/app.css'])
    @stack('styles')
    <script>
    (function(){var d=document.documentElement,t=null;try{t=new URLSearchParams(location.search).get('theme');if(t!=='light'&&t!=='dark')t=localStorage.getItem('mahru-theme')}catch(e){}
    if(t!=='light'&&t!=='dark'){t=(window.matchMedia&&matchMedia('(prefers-color-scheme: dark)').matches)?'dark':'light'}d.dataset.theme=t})();
    </script>
    <link rel="preload" href="{{ asset('fonts/mahru/Vazirmatn-Regular.woff2') }}" as="font" type="font/woff2" crossorigin>
    <style>
        @font-face{font-family:"Mahru";src:url("{{ asset('fonts/mahru/Vazirmatn-Regular.woff2') }}") format("woff2");font-weight:400;font-display:swap}
        @font-face{font-family:"Mahru";src:url("{{ asset('fonts/mahru/Vazirmatn-Medium.woff2') }}") format("woff2");font-weight:500;font-display:swap}
        @font-face{font-family:"Mahru";src:url("{{ asset('fonts/mahru/Vazirmatn-SemiBold.woff2') }}") format("woff2");font-weight:600;font-display:swap}
        @font-face{font-family:"Mahru";src:url("{{ asset('fonts/mahru/Vazirmatn-Bold.woff2') }}") format("woff2");font-weight:700;font-display:swap}
        @font-face{font-family:"Mahru";src:url("{{ asset('fonts/mahru/Vazirmatn-ExtraBold.woff2') }}") format("woff2");font-weight:800 900;font-display:swap}

        /* پنل ابرمدیر (۲۰۲۶-۱۰-۱۵): سفید + آبی کم‌رنگ + آبی نفتی، با تم تیره‌ی «نفتی شب» — عمداً جدا از طلایی پنل سالن‌ها
           تا معلوم باشد کجایی. همان کلید localStorage «mahru-theme» و پیش‌فرض تنظیم دستگاه. */
        :root, html[data-theme=light] {
            color-scheme: light;
            --sa-bg: #F2F7FA;          /* آبی خیلی کم‌رنگ */
            --sa-surface: #FFFFFF;
            --sa-soft: #E4F0F6;        /* آبی کم‌رنگ: هاور، ردیف فعال، سرستون، برچسب */
            --sa-border: #D3E2EA;
            --sa-text: #0F2F3A;
            --sa-text-dim: #557280;
            --sa-accent: #0E5A6E;      /* آبی نفتی */
            --sa-accent-hover: #0A4757;
            --sa-on-accent: #FFFFFF;
            --sa-side: #0D4B5C;        /* منوی کناری نفتی */
            --sa-side-text: #CFE6EE;
            --sa-danger: #B4372A;
            --sa-success: #23775A;
            --sa-warn: #A86410;
            --sa-danger-bg: #FBECEA;
            --sa-success-bg: #E6F4EE;
            --sa-shadow: 0 10px 30px -22px rgba(14, 90, 110, .45);
        }
        html[data-theme=dark] {
            color-scheme: dark;
            --sa-bg: #0A1D24;          /* نفتی شب */
            --sa-surface: #0F2A33;
            --sa-soft: #15404C;
            --sa-border: #1E4754;
            --sa-text: #E4F1F5;
            --sa-text-dim: #8DB0BC;
            --sa-accent: #63B7CE;      /* نفتی روشن روی زمینه‌ی تیره */
            --sa-accent-hover: #82C8DB;
            --sa-on-accent: #05222A;
            --sa-side: #072630;
            --sa-side-text: #A7C9D4;
            --sa-danger: #E99083;
            --sa-success: #72C9A2;
            --sa-warn: #E5B262;
            --sa-danger-bg: rgba(233, 144, 131, .12);
            --sa-success-bg: rgba(114, 201, 162, .12);
            --sa-shadow: 0 18px 40px -24px rgba(0, 0, 0, .8);
        }

        html { background: var(--sa-bg); }
        body { background: var(--sa-bg); color: var(--sa-text); font-family: "Mahru", "Vazirmatn", system-ui, sans-serif; -webkit-font-smoothing: antialiased; transition: background-color .3s ease, color .3s ease; }

        .sa-card { background: var(--sa-surface); border: 1px solid var(--sa-border); border-radius: 16px; box-shadow: var(--sa-shadow); }
        .sa-input {
            background: var(--sa-surface); border: 1px solid var(--sa-border); color: var(--sa-text);
            border-radius: 10px; padding: 0.6rem 0.9rem; width: 100%;
        }
        .sa-input:focus { outline: none; border-color: var(--sa-accent); box-shadow: 0 0 0 3px rgba(14, 90, 110, .14); }
        .sa-label { color: var(--sa-text-dim); font-size: 0.85rem; margin-bottom: 0.3rem; display: block; }
        .sa-btn {
            background: var(--sa-accent); color: var(--sa-on-accent); font-weight: 700; padding: 0.6rem 1.5rem;
            border-radius: 10px; transition: background 0.15s; display: inline-block;
        }
        .sa-btn:hover { background: var(--sa-accent-hover); }
        .sa-side { background: var(--sa-side); }
        /* منو هم‌ارتفاع صفحه و با اسکرول خودش؛ پیش‌تر همراه صفحه بالا می‌رفت و آیتم‌های پایین (تنظیمات، گزارش کارها) دیده نمی‌شدند (بسته‌ی «ح»، ۷) */
        @media (min-width: 768px) { .sa-side { position: sticky; top: 0; height: 100vh; overflow-y: auto; } }
        .sa-nav-link { color: var(--sa-side-text); padding: 0.7rem 1rem; display: block; border-radius: 10px; margin-bottom: 2px; transition: background .15s, color .15s; }
        .sa-nav-link:hover { color: #FFFFFF; background: rgba(255, 255, 255, .08); }
        .sa-nav-link.active { color: var(--sa-accent); background: var(--sa-soft); font-weight: 700; }
        /* دکمه‌هایی که با رنگ اصلی پر شده‌اند: متن سفید */
        [style*="background: var(--sa-accent)"], [style*="background:var(--sa-accent)"] { color: var(--sa-on-accent) !important; }
        table thead { background: var(--sa-soft); }
        .sa-nav-group { font-size: .72rem; letter-spacing: .04em; color: var(--sa-side-text); opacity: .65; padding: .9rem 1rem .3rem; }
        .sa-badge { border-radius: 999px; padding: 0 .5rem; font-size: .75rem; font-weight: 700; margin-inline-start: .35rem; background: var(--sa-soft); color: var(--sa-accent); }
        .sa-badge-bad { background: var(--sa-danger); color: #FFFFFF; }
        /* ورودی‌های بی‌کلاس (پلاگین forms پس‌زمینه‌ی سفید ثابت می‌دهد) */
        input:not([type=checkbox]):not([type=radio]):not([type=file]), select, textarea { background-color: var(--sa-surface); color: var(--sa-text); border-color: var(--sa-border); }
        select option { background: var(--sa-surface); color: var(--sa-text); }
        input[type=checkbox], input[type=radio] { accent-color: var(--sa-accent); color: var(--sa-accent); }
        input::placeholder, textarea::placeholder { color: var(--sa-text-dim); opacity: .7; }
        /* دکمه‌ی تم (همان partials/mahru-theme-toggle) */
        .m-theme-btn { display: inline-flex; align-items: center; justify-content: center; width: 38px; height: 38px; border-radius: 999px;
            border: 1px solid var(--sa-border); color: var(--sa-accent); background: var(--sa-soft); transition: transform .25s ease; }
        .m-theme-btn:hover { transform: rotate(-12deg); }
        .m-theme-btn svg { width: 18px; height: 18px; }
        html[data-theme=dark] .m-theme-btn .m-sun, html:not([data-theme=dark]) .m-theme-btn .m-moon { display: none; }
        ::selection { background: rgba(14, 90, 110, .2); }

        /* بسته‌ی «ح»، ۲ (۲۰۲۶-۱۰-۱۹): ظاهر یک‌دست کادرها */
        .sa-card-pad { padding: 1.5rem; }
        .sa-card-title { font-weight: 700; font-size: 1rem; color: var(--sa-text); }
        .sa-card-desc { font-size: .8rem; line-height: 1.7; color: var(--sa-text-dim); margin-top: .25rem; }
        .sa-card-head { padding-bottom: 1rem; margin-bottom: 1.25rem; border-bottom: 1px solid var(--sa-border); }
        .sa-help { font-size: .75rem; line-height: 1.7; color: var(--sa-text-dim); margin-top: .35rem; }
        .sa-stat { padding: 1.1rem 1.25rem; display: flex; flex-direction: column; gap: .35rem; min-height: 6.5rem; }
        .sa-stat__label { font-size: .8rem; color: var(--sa-text-dim); line-height: 1.6; }
        .sa-stat__value { font-size: 1.6rem; font-weight: 800; line-height: 1.2; margin-top: auto; }
        .sa-stat__unit { font-size: .72rem; color: var(--sa-text-dim); }
        .sa-ltr { direction: ltr; unicode-bidi: isolate; display: inline-block; font-family: ui-monospace, Menlo, Consolas, monospace; font-size: .85em; }
        .sa-table-wrap { overflow-x: auto; }
        /* فرزند grid پیش‌فرض min-width: auto دارد؛ یک جدول عریض کل ستون را پهن و صفحه‌ی گوشی را افقی می‌کرد */
        main .grid > * { min-width: 0; }
        .sa-table-wrap table { min-width: 40rem; }
        .sa-menu-btn { display: none; }
        @media (max-width: 767px) {
            .sa-menu-btn { display: inline-flex; }
            .sa-side.is-open { display: block; position: fixed; top: 0; bottom: 0; right: 0; width: 16rem; z-index: 60; overflow-y: auto; box-shadow: -12px 0 32px rgba(0, 0, 0, .25); }
            .sa-backdrop.is-open { display: block; }
            main { padding: 1rem !important; }
        }
        input[type=file] { font-size: .8rem; color: var(--sa-text-dim); max-width: 100%; }
        input[type=file]::file-selector-button { margin-inline-end: .75rem; padding: .45rem .9rem; border-radius: 10px; border: 1px solid var(--sa-border); background: var(--sa-soft); color: var(--sa-accent); font-family: inherit; font-weight: 600; cursor: pointer; }
        .sa-backdrop { display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, .45); z-index: 55; }
    </style>
</head>
<body>
    <div class="flex min-h-screen">
        <aside id="sa-side" class="w-64 flex-shrink-0 hidden md:block sa-side" data-nav-scroll>
            <div class="p-5 border-b" style="border-color: rgba(255,255,255,.12);">
                <img src="{{ asset('brand/mahru-logo-horizontal-on-dark.svg') }}" alt="{{ config('brand.name') }}" style="height: 40px; width: auto; display: block; margin-bottom: .5rem;">
                <div class="text-lg font-bold" style="color: #FFFFFF;">پنل مدیریت کل</div>
            </div>
            <nav class="p-3">
                @php
                    $saOverview = app(\App\Services\SuperAdmin\PlatformOverview::class);
                    $saAttention = $saOverview->attentionQuery()->count();
                    $saFailedJobs = $saOverview->failedJobsCount();
                    $saUnread = \App\Http\Controllers\SuperAdmin\NotificationInboxController::query(auth()->id())->whereNull('read_at')->count();
                    $openDemoRequests = \App\Models\DemoRequest::query()->open()->count();
                    // بسته‌ی مجوز 5د: شکایت‌های بی‌پاسخ (قرمز اگر مهلت ۷۲ ساعته‌ی یکی گذشته)
                    $saAwaiting = \App\Models\SupportTicket::query()->awaitingResponse();
                    $saOverdue = (clone $saAwaiting)->where('response_due_at', '<', now())->count();
                    $saComplaints = $saOverdue > 0 ? -$saOverdue : $saAwaiting->count();
                    $saNav = [
                        'سالن‌ها' => [
                            ['superadmin.dashboard', 'superadmin.dashboard', 'داشبورد', 0],
                            ['superadmin.salons.index', 'superadmin.salons.*', 'مدیریت سالن‌ها', 0],
                            ['superadmin.search.index', 'superadmin.search.*', 'جستجوی شماره', 0],
                            ['superadmin.plans.index', 'superadmin.plans.*', 'پلن‌ها و امکانات', 0],
                            ['superadmin.templates.index', 'superadmin.templates.*', 'قالب‌ها', 0],
                        ],
                        'مالی و پیامک' => [
                            ['superadmin.payments.index', 'superadmin.payments.*', 'کیف پول و درآمد اشتراک', 0],
                            ['superadmin.sms.index', 'superadmin.sms.*', 'پیامک سالن‌ها', 0],
                            ['superadmin.push.index', 'superadmin.push.*', 'اعلان اپ (پوش)', 0],
                            ['superadmin.app-releases.index', 'superadmin.app-releases.*', 'نسخه‌های اپ', 0],
                            ['superadmin.payment-attention.index', 'superadmin.payment-attention.*', 'پرداخت‌های نیازمند بررسی', -$saAttention],
                        ],
                        'ارتباط' => [
                            ['superadmin.notifications.index', 'superadmin.notifications.*', 'اعلان‌ها', $saUnread],
                            ['superadmin.support-tickets.index', 'superadmin.support-tickets.*', 'تیکت‌ها و شکایت‌ها', $saComplaints],
                            ['superadmin.demo-requests.index', 'superadmin.demo-requests.*', 'درخواست‌های دمو', $openDemoRequests],
                            ['superadmin.announcements.index', 'superadmin.announcements.*', 'اطلاعیه‌های سراسری', 0],
                        ],
                        'سیستم' => [
                            ['superadmin.failed-jobs.index', 'superadmin.failed-jobs.*', 'کارهای ناموفق صف', -$saFailedJobs],
                            ['superadmin.audit-log.index', 'superadmin.audit-log.*', 'گزارش کارهای ابرمدیر', 0],
                            ['superadmin.retained-data.index', 'superadmin.retained-data.*', 'داده‌های نگهداری‌شده', 0],
                            ['superadmin.settings.edit', 'superadmin.settings.*', 'تنظیمات پلتفرم', 0],
                        ],
                    ];
                @endphp
                @foreach ($saNav as $group => $items)
                    <div class="sa-nav-group">{{ $group }}</div>
                    @foreach ($items as [$route, $pattern, $label, $count])
                        <a href="{{ route($route) }}" class="sa-nav-link {{ request()->routeIs($pattern) ? 'active' : '' }}" @if (request()->routeIs($pattern)) aria-current="page" @endif>
                            {{ $label }}
                            {{-- عدد منفی = هشدار قرمز --}}
                            @if ($count !== 0)
                                <span class="sa-badge {{ $count < 0 ? 'sa-badge-bad' : '' }}"
                                      @if ($route === 'superadmin.demo-requests.index') data-demo-requests-badge @elseif ($route === 'superadmin.payment-attention.index') data-attention-badge @elseif ($route === 'superadmin.failed-jobs.index') data-failed-jobs-badge @elseif ($route === 'superadmin.notifications.index') data-inbox-badge @elseif ($route === 'superadmin.support-tickets.index') data-complaints-badge @endif
                                >{{ to_persian_num((string) abs($count)) }}</span>
                            @endif
                        </a>
                    @endforeach
                @endforeach
            </nav>
        </aside>

        <div class="flex-1 flex flex-col min-w-0">
            <header class="flex items-center justify-between gap-3 px-4 md:px-6 py-4" style="background: var(--sa-surface); border-bottom: 1px solid var(--sa-border);">
                <div class="flex items-center gap-3 min-w-0">
                    {{-- موبایل: پیش‌تر منو اصلاً نبود (aside فقط md:block) --}}
                    <button type="button" class="sa-menu-btn w-[38px] h-[38px] items-center justify-center rounded-full shrink-0" data-sa-menu
                            style="border: 1px solid var(--sa-border); color: var(--sa-accent); background: var(--sa-soft);" aria-label="منو" aria-controls="sa-side" aria-expanded="false">
                        <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <h1 class="text-lg font-bold truncate">@yield('title')</h1>
                </div>
                <div class="flex items-center gap-3 md:gap-4 shrink-0">
                    @include('partials.mahru-theme-toggle')
                    <a href="{{ route('superadmin.notifications.index') }}" class="relative inline-flex w-[38px] h-[38px] items-center justify-center rounded-full" style="border: 1px solid var(--sa-border); color: var(--sa-accent); background: var(--sa-soft);" aria-label="اعلان‌ها">
                        <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/></svg>
                        @if ($saUnread > 0)<span class="absolute -top-1 -right-1 sa-badge sa-badge-bad" style="margin: 0; padding: 0 .35rem;">{{ to_persian_num((string) $saUnread) }}</span>@endif
                    </a>
                    <span class="text-sm hidden md:inline" style="color: var(--sa-text-dim);">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-sm" style="color: var(--sa-danger);">خروج</button>
                    </form>
                </div>
            </header>

            <main class="flex-1 p-6">
                @if (session('success'))
                    <div class="mb-4 p-4 rounded-lg" style="background: var(--sa-success-bg); border: 1px solid var(--sa-success); color: var(--sa-success);">
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('warning'))
                    <div class="mb-4 p-4 rounded-lg" style="background: var(--sa-soft); border: 1px solid var(--sa-border); color: var(--sa-text);" data-flash-warning>
                        {{ session('warning') }}
                    </div>
                @endif

                @if (session('error') || $errors->any())
                    <div class="mb-4 p-4 rounded-lg" style="background: var(--sa-danger-bg); border: 1px solid var(--sa-danger); color: var(--sa-danger);">
                        @if (session('error'))
                            {{ session('error') }}
                        @endif
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <div class="sa-backdrop" data-sa-backdrop></div>
    <script>
        // منوی موبایل
        (function () {
            var btn = document.querySelector('[data-sa-menu]'), side = document.getElementById('sa-side'), back = document.querySelector('[data-sa-backdrop]');
            if (!btn || !side) return;
            function toggle(open) {
                side.classList.toggle('is-open', open);
                back.classList.toggle('is-open', open);
                btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            }
            btn.addEventListener('click', function () { toggle(!side.classList.contains('is-open')); });
            back.addEventListener('click', function () { toggle(false); });
            document.addEventListener('keydown', function (e) { if (e.key === 'Escape') toggle(false); });
        })();

        // جدول‌هایی که در کادر خودشان اسکرول ندارند، روی گوشی بریده می‌شدند (کادر overflow-hidden): هر جدول یک ظرف اسکرول افقی می‌گیرد
        document.querySelectorAll('main table').forEach(function (table) {
            if (table.closest('.sa-table-wrap')) return;
            var wrap = document.createElement('div');
            wrap.className = 'sa-table-wrap';
            table.parentNode.insertBefore(wrap, table);
            wrap.appendChild(table);
        });

        // ارقام فارسی در متن صفحه (پیش‌تر برخی عددها لاتین و برخی فارسی بودند). ورودی‌ها، کد، و هر چیز dir="ltr" (آدرس، شناسه، کد پیگیری) دست نمی‌خورند.
        (function () {
            var FA = '۰۱۲۳۴۵۶۷۸۹';
            var SKIP = 'input,textarea,select,option,script,style,code,pre,kbd,[dir="ltr"],[data-latin],.sa-ltr';
            function walk(node) {
                if (node.nodeType === 3) {
                    if (/[0-9]/.test(node.nodeValue)) node.nodeValue = node.nodeValue.replace(/[0-9]/g, function (d) { return FA[d]; });
                    return;
                }
                if (node.nodeType !== 1 || node.matches(SKIP)) return;
                for (var i = 0; i < node.childNodes.length; i++) walk(node.childNodes[i]);
            }
            document.querySelectorAll('main, header h1, .sa-side').forEach(walk);
        })();
    </script>
    @stack('scripts')
@include('partials.password-reveal')
@include('partials.nav-reveal-active')
</body>
</html>
