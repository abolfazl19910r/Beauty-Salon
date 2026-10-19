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
    </style>
</head>
<body>
    <div class="flex min-h-screen">
        <aside class="w-64 flex-shrink-0 hidden md:block sa-side" data-nav-scroll>
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
                            ['superadmin.payment-attention.index', 'superadmin.payment-attention.*', 'پرداخت‌های نیازمند بررسی', -$saAttention],
                        ],
                        'ارتباط' => [
                            ['superadmin.notifications.index', 'superadmin.notifications.*', 'اعلان‌ها', $saUnread],
                            ['superadmin.support-tickets.index', 'superadmin.support-tickets.*', 'تیکت‌های پشتیبانی', 0],
                            ['superadmin.demo-requests.index', 'superadmin.demo-requests.*', 'درخواست‌های دمو', $openDemoRequests],
                            ['superadmin.announcements.index', 'superadmin.announcements.*', 'اطلاعیه‌های سراسری', 0],
                        ],
                        'سیستم' => [
                            ['superadmin.failed-jobs.index', 'superadmin.failed-jobs.*', 'کارهای ناموفق صف', -$saFailedJobs],
                            ['superadmin.audit-log.index', 'superadmin.audit-log.*', 'گزارش کارهای ابرمدیر', 0],
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
                                      @if ($route === 'superadmin.demo-requests.index') data-demo-requests-badge @elseif ($route === 'superadmin.payment-attention.index') data-attention-badge @elseif ($route === 'superadmin.failed-jobs.index') data-failed-jobs-badge @elseif ($route === 'superadmin.notifications.index') data-inbox-badge @endif
                                >{{ to_persian_num((string) abs($count)) }}</span>
                            @endif
                        </a>
                    @endforeach
                @endforeach
            </nav>
        </aside>

        <div class="flex-1 flex flex-col">
            <header class="flex items-center justify-between px-6 py-4" style="background: var(--sa-surface); border-bottom: 1px solid var(--sa-border);">
                <h1 class="text-lg font-bold">@yield('title')</h1>
                <div class="flex items-center gap-4">
                    @include('partials.mahru-theme-toggle')
                    <a href="{{ route('superadmin.notifications.index') }}" class="relative inline-flex w-[38px] h-[38px] items-center justify-center rounded-full" style="border: 1px solid var(--sa-border); color: var(--sa-accent); background: var(--sa-soft);" aria-label="اعلان‌ها">
                        <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/></svg>
                        @if ($saUnread > 0)<span class="absolute -top-1 -right-1 sa-badge sa-badge-bad" style="margin: 0; padding: 0 .35rem;">{{ to_persian_num((string) $saUnread) }}</span>@endif
                    </a>
                    <span class="text-sm" style="color: var(--sa-text-dim);">{{ auth()->user()->name }}</span>
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

    @stack('scripts')
@include('partials.password-reveal')
@include('partials.nav-reveal-active')
</body>
</html>
