<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl" class="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') | پنل متخصصین</title>

    @include('partials.favicons')

    @vite(['resources/css/app.css', 'resources/js/app.jsx'])

    @include('partials.mahru-theme')
    @include('partials.mahru-panel')

    <style>
        .persian-number {
            -moz-font-feature-settings: "ss02";
            -webkit-font-feature-settings: "ss02";
            font-feature-settings: "ss02";
        }

        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-out {
            animation: fadeOut 0.3s ease-out;
        }

        @keyframes fadeOut {
            from {
                opacity: 1;
                transform: translateY(0);
            }
            to {
                opacity: 0;
                transform: translateY(-10px);
            }
        }

        /* بسته‌ی «و»: منوی بالایی پنل متخصص */
        .spec-top{gap:4px;align-items:center;padding:10px 16px;border-bottom:1px solid var(--specialist-border);background:var(--specialist-surface);overflow-x:auto;white-space:nowrap}
        .spec-top a{font-size:14px;color:var(--specialist-text-dim);padding:8px 14px;text-decoration:none}
        .spec-top a[aria-current=page]{color:var(--specialist-text);font-weight:700}
        .spec-top .spec-top__site{margin-inline-start:auto}
        .spec-top--majalle a{border-bottom:2px solid transparent;padding:8px 6px;margin:0 8px}
        .spec-top--majalle a[aria-current=page]{border-bottom-color:rgb(var(--m-gold))}
        .spec-top--aram{justify-content:center;background:transparent;border:0;padding-top:14px}
        .spec-top--aram a{border-radius:12px;background:rgb(var(--m-tx) / .04)}
        .spec-top--aram a[aria-current=page]{background:rgb(var(--m-bg2));box-shadow:0 1px 3px rgba(0,0,0,.12)}
        .spec-top--bento{margin:12px 16px 0;border:1px solid var(--specialist-border);border-radius:24px}
        .spec-top--bento a{border-radius:999px;background:rgb(var(--m-tx) / .05)}
        .spec-top--bento a[aria-current=page]{background:rgb(var(--m-gold) / .14);color:rgb(var(--m-gold))}
        html[data-layout=aram] .spec-main-inner{max-width:960px;margin:0 auto}
    </style>
    @stack('styles')
</head>
<body class="m-panel text-[var(--specialist-text)]" style="background-color: var(--specialist-bg);">

<div class="flex h-screen overflow-hidden">

    @php
        /*
         * ⭐ بسته‌ی «و»: پنل متخصص هم قالب سایت سالن را می‌گیرد. فهرست منو یک‌جا (همین آرایه) و دو شکل نمایش:
         * منوی کناری (طلایی ماهرو، ویترین) یا نوار بالایی (مجله: زبانه‌ی زیرخط‌دار، آرام: کلید قرصی، بنتو: کاشی). روی موبایل همیشه کشو + نوار پایین.
         */
        $__specLayout = app(\App\Support\Themes\CurrentAppearance::class)->get()->layout;
        $__specTop = in_array($__specLayout, ['majalle', 'aram', 'bento'], true);
        $__specNav = [
            ['specialist.my-dashboard', 'specialist.my-dashboard', 'داشبورد', 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z'],
            ['specialist.profile.show', 'specialist.profile*', 'پروفایل', 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
            ['specialist.bookings.index', 'specialist.bookings*', 'نوبت‌ها', 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
            ['specialist.reviews.index', 'specialist.reviews*', 'نظرات و ارزیابی‌ها', 'M11.48 3.5a.56.56 0 011.04 0l2.13 5.11a.56.56 0 00.47.35l5.52.44c.5.04.7.66.32.99l-4.2 3.6a.56.56 0 00-.18.56l1.28 5.38a.56.56 0 01-.84.61l-4.72-2.88a.56.56 0 00-.59 0l-4.72 2.88a.56.56 0 01-.84-.61l1.28-5.38a.56.56 0 00-.18-.56l-4.2-3.6a.56.56 0 01.32-.99l5.52-.44a.56.56 0 00.47-.35z'],
            ['specialist.wallet.index', 'specialist.wallet*', 'کیف پول', 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z'],
            ['specialist.schedule', 'specialist.schedule*', 'ساعات کاری هفتگی', 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
            ['specialist.leaves.index', 'specialist.leaves*', 'مرخصی‌ها', 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
            ['specialist.reports.index', 'specialist.reports*', 'گزارش', 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
            ['specialist.qr', 'specialist.qr*', 'QR من', 'M3 3h7v7H3zM14 3h7v7h-7zM3 14h7v7H3zM14 14h3v3h-3zM20 14v.01M14 20h.01M17 17h3v3'],
        ];
    @endphp
    <aside id="sidebar" data-nav-scroll class="w-64 min-h-screen overflow-y-auto hidden {{ $__specTop ? '' : 'md:block' }} transition-all duration-300 z-30 border-l" style="background-color: var(--specialist-surface); border-color: var(--specialist-border);">
        <div class="p-5 border-b flex items-center justify-between" style="border-color: var(--specialist-border);">
            <a href="{{ route('home') }}" class="flex items-center gap-2">
                {{-- ⭐ لوگوی سالن متخصص (۲۰۲۶-۰۹-۲۴)، وگرنه آیکون پیش‌فرض. --}}
                <x-brand-emblem class="w-9 h-9 rounded" pad="3px" />
                <span class="text-lg font-bold font-serif-fa" style="color: var(--specialist-plum-light);">پنل متخصص</span>
            </a>
        </div>

        <nav class="mt-6 px-3 space-y-2">
            @foreach ($__specNav as [$__route, $__pattern, $__label, $__icon])
                <a href="{{ route($__route) }}"
                   class="flex items-center px-4 py-3 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs($__pattern) ? 'sidebar-active' : 'text-[var(--specialist-text-dim)] hover:bg-m-tx/5 hover:text-[var(--specialist-plum-light)]' }}">
                    <svg class="w-5 h-5 ml-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $__icon }}" />
                    </svg>
                    {{ $__label }}
                </a>
            @endforeach

            <div class="border-t my-4 pt-4" style="border-color: var(--specialist-border);">
                <a href="{{ route('home') }}" class="flex items-center px-4 py-3 text-sm font-medium text-[var(--specialist-inactive)] hover:bg-m-tx/5 hover:text-[var(--specialist-text)] rounded-lg transition-colors">
                    <svg class="w-5 h-5 ml-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    بازگشت به سایت
                </a>

                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <button type="submit" class="w-full flex items-center px-4 py-3 text-sm font-medium text-m-bad hover:bg-m-bad/10 rounded-lg transition-colors">
                        <svg class="w-5 h-5 ml-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        خروج
                    </button>
                </form>
            </div>
        </nav>
    </aside>

    <div id="sidebar-backdrop" class="md:hidden fixed inset-0 bg-black/60 z-20 hidden" onclick="toggleSidebar()"></div>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">

        <header class="border-b sticky top-0 z-10 h-16 flex items-center justify-between px-4" style="background-color: var(--specialist-surface); border-color: var(--specialist-border);">
            <div class="flex items-center">
                <button onclick="toggleSidebar()" class="md:hidden text-[var(--specialist-text-dim)] hover:text-[var(--specialist-plum-light)] focus:outline-none ml-3">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <h1 class="text-lg font-bold text-[var(--specialist-text)]">@yield('title')</h1>
            </div>

            <div class="flex items-center space-x-4 space-x-reverse">
                @include('partials.mahru-theme-toggle')
                <div class="relative inline-block text-left">
                    <button type="button"
                            id="notification-button"
                            class="p-1 rounded-full text-[var(--specialist-text-dim)] hover:text-[var(--specialist-plum-light)] hover:bg-m-tx/5 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[var(--specialist-plum-mid)] relative transition-colors"
                            aria-expanded="false" aria-haspopup="true">
                        <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                        </svg>
                        <span id="notification-count" class="absolute top-0 right-0 hidden items-center justify-center px-2 py-1 text-xs font-bold leading-none text-m-onsolid bg-m-bad rounded-full">0</span>
                    </button>

                    <div id="notification-dropdown"
                         class="hidden absolute left-0 z-50 mt-2 w-72 origin-top-left rounded-md shadow-lg ring-1 ring-black/20 focus:outline-none border"
                         style="background-color: var(--specialist-surface); border-color: var(--specialist-border);"
                         role="menu" aria-orientation="vertical" aria-labelledby="notification-button" tabindex="-1">
                        <div class="px-4 py-2 border-b" style="border-color: var(--specialist-border);">
                            <p class="text-sm font-bold text-[var(--specialist-text)]">اعلانات جدید</p>
                        </div>
                        <div id="notification-list-container" class="py-1 max-h-64 overflow-y-auto" role="none">
                            <p id="loading-notifications-message" class="px-4 py-2 text-sm text-[var(--specialist-text-dim)]">در حال بارگذاری...</p>
                            <p id="no-notifications-message" class="px-4 py-2 text-sm text-[var(--specialist-text-dim)] hidden">اعلانی برای نمایش وجود ندارد.</p>
                        </div>
                        <div class="border-t" style="border-color: var(--specialist-border);">
                            <a href="{{ route('specialist.notifications.index') }}"
                               class="w-full text-center text-[var(--specialist-plum-mid)] hover:bg-m-tx/5 block px-4 py-2 text-sm"
                               role="menuitem"
                               tabindex="-1">
                                مشاهده همه اعلانات
                            </a>
                        </div>
                    </div>
                </div>

                <div class="relative inline-block text-left">
                    <button type="button"
                            class="inline-flex items-center justify-center w-10 h-10 rounded-full specialist-cta focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[var(--specialist-plum-mid)] transition-all"
                            id="user-menu-button"
                            aria-expanded="false"
                            aria-haspopup="true">
                        <span class="text-sm font-bold">
                            {{ mb_substr(optional(auth()->user())->name ?? 'کاربر', 0, 1) }}
                        </span>
                    </button>

                    <div id="user-menu-dropdown"
                         class="hidden absolute left-0 z-50 mt-2 w-56 origin-top-left rounded-md shadow-lg ring-1 ring-black/20 focus:outline-none border"
                         style="background-color: var(--specialist-surface); border-color: var(--specialist-border);"
                         role="menu" aria-orientation="vertical" aria-labelledby="user-menu-button" tabindex="-1">
                        <div class="py-1" role="none">

                            <div class="px-4 py-2 border-b" style="border-color: var(--specialist-border);">
                                <p class="text-sm font-medium text-[var(--specialist-text)] truncate">{{ auth()->user()->name ?? 'کاربر' }}</p>
                                <p class="text-xs text-[var(--specialist-plum-muted)] truncate">متخصص زیبایی</p>
                            </div>

                            <a href="{{ route('specialist.profile.show') }}"
                               class="text-[var(--specialist-text-dim)] hover:bg-m-tx/5 hover:text-[var(--specialist-text)] flex items-center px-4 py-2 text-sm"
                               role="menuitem" tabindex="-1">
                                <svg class="w-5 h-5 mr-2 text-[var(--specialist-inactive)]" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zM8 12a6 6 0 00-5.43 3.59A8 8 0 0118 10a8 8 0 01-4.57 7.59A6 6 0 008 12z" clip-rule="evenodd" />
                                </svg>
                                نمایش پروفایل
                            </a>

                            <a href="{{ route('specialist.profile.edit') }}"
                               class="text-[var(--specialist-text-dim)] hover:bg-m-tx/5 hover:text-[var(--specialist-text)] flex items-center px-4 py-2 text-sm"
                               role="menuitem" tabindex="-1">
                                <svg class="w-5 h-5 mr-2 text-[var(--specialist-inactive)]" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z" />
                                    <path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd" />
                                </svg>
                                ویرایش پروفایل
                            </a>

                            <form method="POST" action="{{ route('logout') }}" role="none" class="border-t" style="border-color: var(--specialist-border);">
                                @csrf
                                <button type="submit"
                                        class="w-full text-right text-m-bad hover:bg-m-bad/10 flex items-center px-4 py-2 text-sm"
                                        role="menuitem" tabindex="-1">
                                    <svg class="w-5 h-5 mr-2 text-m-bad" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m-3 0l3-3m0 0l-3-3m3 3H9" />
                                    </svg>
                                    خروج
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        @if ($__specTop)
            {{-- بسته‌ی «و»: منوی بالایی پنل متخصص در چیدمان‌های مجله/آرام/بنتو (روی موبایل همان کشو و نوار پایین) --}}
            <nav class="spec-top spec-top--{{ $__specLayout }} hidden md:flex" aria-label="منوی پنل متخصص">
                @foreach ($__specNav as [$__route, $__pattern, $__label])
                    <a href="{{ route($__route) }}" @if (request()->routeIs($__pattern)) aria-current="page" @endif>{{ $__label }}</a>
                @endforeach
                <a href="{{ route('home') }}" class="spec-top__site">بازگشت به سایت</a>
            </nav>
        @endif
        @include('partials.platform-announcements', ['panel' => 'specialists'])
        @include('partials.booking-paused-banner', ['panel' => 'specialists'])

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6 pb-24 md:pb-6" style="background-color: var(--specialist-bg);">
            @if(session('success'))
                <div id="success-notification" class="mb-4 border-r-4 border-m-ok bg-m-ok/10 p-4 rounded-lg flex items-center justify-between fade-in">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-m-ok ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="text-m-ok">{{ session('success') }}</span>
                    </div>
                    <button onclick="closeNotification('success-notification')" class="text-m-ok hover:text-m-ok transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            @endif

            @if(session('error'))
                <div id="error-notification" class="mb-4 border-r-4 border-m-bad bg-m-bad/10 p-4 rounded-lg flex items-center justify-between fade-in">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-m-bad ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="text-m-bad">{{ session('error') }}</span>
                    </div>
                    <button onclick="closeNotification('error-notification')" class="text-m-bad hover:text-m-bad transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            @endif

            <div class="spec-main-inner">
            @yield('content')
            </div>
        </main>

        <footer class="hidden md:block border-t p-4 text-center text-sm" style="background-color: var(--specialist-surface); border-color: var(--specialist-border); color: var(--specialist-text-dim);">
            © {{ date('Y') }} پنل متخصصین سالن زیبایی.
        </footer>
    </div>
</div>

<nav class="md:hidden fixed bottom-0 inset-x-0 z-40 border-t grid grid-cols-4" style="background-color: var(--specialist-surface); border-color: var(--specialist-border);">
    <a href="{{ route('specialist.my-dashboard') }}" class="flex flex-col items-center justify-center gap-1 py-2.5 text-xs font-medium {{ request()->routeIs('specialist.my-dashboard') ? 'text-[var(--specialist-plum-light)]' : 'text-[var(--specialist-inactive)]' }}">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7m-9-2v10m4-10v10m6-8v8a2 2 0 01-2 2H5a2 2 0 01-2-2v-8" />
        </svg>
        خانه
    </a>
    <a href="{{ route('specialist.bookings.index') }}" class="flex flex-col items-center justify-center gap-1 py-2.5 text-xs font-medium {{ request()->routeIs('specialist.bookings*') ? 'text-[var(--specialist-plum-light)]' : 'text-[var(--specialist-inactive)]' }}">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        تقویم
    </a>
    <a href="{{ route('specialist.reports.index') }}" class="flex flex-col items-center justify-center gap-1 py-2.5 text-xs font-medium {{ request()->routeIs('specialist.reports*') ? 'text-[var(--specialist-plum-light)]' : 'text-[var(--specialist-inactive)]' }}">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
        </svg>
        آمار
    </a>
    <a href="{{ route('specialist.profile.show') }}" class="flex flex-col items-center justify-center gap-1 py-2.5 text-xs font-medium {{ request()->routeIs('specialist.profile*') ? 'text-[var(--specialist-plum-light)]' : 'text-[var(--specialist-inactive)]' }}">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
        </svg>
        پروفایل
    </a>
</nav>

<script>
    window.SpecialistRoutes = {
        notificationsIndex: '{{ route("specialist.notifications.index") }}',
        notificationsLatest: '{{ route("specialist.notifications.latest") }}',
        notificationsCount: '{{ route("specialist.notifications.count") }}',
        // «:id» مستقیم در route؛ قبلاً «000» جایگزین می‌شد و اولین «000» پورت 8000 بود ← آدرس خراب و اعلان خوانده نمی‌شد (بسته‌ی «ح»، ۶)
        notificationsRead: '{{ route("specialist.notifications.read", ":id") }}',
        csrfToken: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
    };

    function fetchUnreadCount() {
        fetch(window.SpecialistRoutes.notificationsCount, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => response.json())
            .then(data => {
                const countElement = document.getElementById('notification-count');
                const count = data.count;

                if (count > 0) {
                    countElement.textContent = count > 99 ? '99+' : count;
                    countElement.classList.remove('hidden');
                    countElement.classList.add('flex');
                } else {
                    countElement.classList.add('hidden');
                    countElement.classList.remove('flex');
                }
            })
            .catch(error => console.error('خطا در دریافت تعداد اعلانات:', error));
    }

    // صفحه‌ای که با «برگشت» مرورگر از حافظه (bfcache، به‌خصوص فایرفاکس) برمی‌گردد اسکریپت‌هایش را
    // دوباره اجرا نمی‌کند: شمارنده‌ی زنگوله و فهرست اعلان‌ها وضعیت پیش از خواندن اعلان را نشان می‌دادند.
    // در این حالت شمارنده دوباره گرفته می‌شود و صفحه‌ی فهرست اعلان‌ها تازه می‌شود.
    window.addEventListener('pageshow', function (event) {
        if (!event.persisted) return;
        fetchUnreadCount();
        if (document.querySelector('[data-refresh-on-restore]')) window.location.reload();
    });

    function fetchLatestNotifications() {
        const listContainer = document.getElementById('notification-list-container');
        const noMessage = document.getElementById('no-notifications-message');
        const loadingMessage = document.getElementById('loading-notifications-message');

        if (!listContainer || !noMessage || !loadingMessage) return;

        listContainer.innerHTML = '';
        loadingMessage.classList.remove('hidden');
        listContainer.appendChild(loadingMessage);
        noMessage.classList.add('hidden');

        fetch(window.SpecialistRoutes.notificationsLatest, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => {
                if (!response.ok) throw new Error('خطا در دریافت اعلانات');
                return response.json();
            })
            .then(data => {
                loadingMessage.classList.add('hidden');

                if (data.notifications && data.notifications.length > 0) {
                    data.notifications.forEach(notification => {
                        const link = document.createElement('a');
                        link.href = notification.link;
                        link.classList.add('text-[var(--specialist-text-dim)]', 'hover:bg-m-tx/5', 'flex', 'flex-col', 'items-start', 'px-4', 'py-2', 'text-sm', 'border-b', 'border-[var(--specialist-border)]');
                        link.setAttribute('role', 'menuitem');
                        link.setAttribute('tabindex', '-1');

                        if (!notification.read_at) {
                            link.classList.add('bg-m-tx/5', 'font-semibold');
                        }

                        link.innerHTML = `
                        <span class="truncate w-full">${notification.message}</span>
                        <span class="text-xs ${notification.read_at ? 'text-[var(--specialist-inactive)]' : 'text-[var(--specialist-plum-mid)]'} mt-0.5">${notification.time_ago}</span>
                    `;

                        if (!notification.read_at) {
                            link.addEventListener('click', (e) => {
                                e.preventDefault();
                                markNotificationAsRead(notification.id, notification.link);
                            });
                        }

                        listContainer.appendChild(link);
                    });
                } else {
                    noMessage.classList.remove('hidden');
                    listContainer.appendChild(noMessage);
                }
            })
            .catch(error => {
                loadingMessage.classList.add('hidden');
                noMessage.textContent = 'خطا در بارگذاری اعلانات.';
                noMessage.classList.remove('hidden');
                listContainer.appendChild(noMessage);
                console.error('خطا در دریافت اعلانات:', error);
            });
    }

    function markNotificationAsRead(id, redirectLink) {
        const url = window.SpecialistRoutes.notificationsRead.replace(':id', id);

        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': window.SpecialistRoutes.csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => {
                if (response.ok) {
                    fetchUnreadCount();
                    window.location.href = redirectLink;
                } else {
                    console.error('خطا در علامت‌گذاری اعلان');
                    window.location.href = redirectLink;
                }
            })
            .catch(error => {
                console.error('خطای شبکه:', error);
                window.location.href = redirectLink;
            });
    }

    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const backdrop = document.getElementById('sidebar-backdrop');

        sidebar.classList.toggle('hidden');
        sidebar.classList.toggle('absolute');
        sidebar.classList.toggle('inset-y-0');
        sidebar.classList.toggle('right-0');
        sidebar.classList.toggle('z-30');

        backdrop.classList.toggle('hidden');
    }

    function closeNotification(id) {
        const notification = document.getElementById(id);
        if (notification) {
            notification.style.transition = 'all 0.3s ease-out';
            notification.style.opacity = '0';
            notification.style.transform = 'translateY(-10px)';
            setTimeout(() => {
                notification.remove();
            }, 300);
        }
    }

    function startNotificationTimeout(id) {
        const notification = document.getElementById(id);
        if (notification) {
            setTimeout(() => {
                closeNotification(id);
            }, 5000);
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        startNotificationTimeout('success-notification');
        startNotificationTimeout('error-notification');

        fetchUnreadCount();
        setInterval(fetchUnreadCount, 60000);

        const notificationButton = document.getElementById('notification-button');
        const notificationDropdown = document.getElementById('notification-dropdown');

        if (notificationButton && notificationDropdown) {
            notificationButton.addEventListener('click', function(event) {
                const userMenuDropdown = document.getElementById('user-menu-dropdown');
                if (userMenuDropdown && !userMenuDropdown.classList.contains('hidden')) {
                    userMenuDropdown.classList.add('hidden');
                }
                notificationDropdown.classList.toggle('hidden');
                event.stopPropagation();

                if (!notificationDropdown.classList.contains('hidden')) {
                    fetchLatestNotifications();
                }
            });

            document.addEventListener('click', function(event) {
                if (!notificationDropdown.classList.contains('hidden')) {
                    if (!notificationDropdown.contains(event.target) && !notificationButton.contains(event.target)) {
                        notificationDropdown.classList.add('hidden');
                    }
                }
            });

            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape' && !notificationDropdown.classList.contains('hidden')) {
                    notificationDropdown.classList.add('hidden');
                }
            });
        }

        const userMenuButton = document.getElementById('user-menu-button');
        const userMenuDropdown = document.getElementById('user-menu-dropdown');

        if (userMenuButton && userMenuDropdown) {
            userMenuButton.addEventListener('click', function(event) {
                if (notificationDropdown && !notificationDropdown.classList.contains('hidden')) {
                    notificationDropdown.classList.add('hidden');
                }
                userMenuDropdown.classList.toggle('hidden');
                event.stopPropagation();
            });

            document.addEventListener('click', function(event) {
                if (!userMenuDropdown.classList.contains('hidden')) {
                    if (!userMenuDropdown.contains(event.target) && !userMenuButton.contains(event.target)) {
                        userMenuDropdown.classList.add('hidden');
                    }
                }
            });

            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape' && !userMenuDropdown.classList.contains('hidden')) {
                    userMenuDropdown.classList.add('hidden');
                }
            });
        }
    });
</script>

@stack('scripts')
@include('partials.password-reveal')
@include('partials.nav-reveal-active')
</body>
</html>
