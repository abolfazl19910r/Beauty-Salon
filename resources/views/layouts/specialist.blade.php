<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl" class="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') | پنل کارشناس</title>

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
        /* بسته‌ی ۳ ظاهری: منوی کناری پنل کارشناس */
        .spec-side__link{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;font-size:14px;color:var(--specialist-text);text-decoration:none;transition:background-color .15s}
        .spec-side__link:hover{background:rgb(var(--m-tx) / .05)}
        .spec-side__link.is-active{background:rgb(var(--m-gold) / .1);font-weight:700;box-shadow:inset -3px 0 0 rgb(var(--m-gold))}
        .spec-side__link--quiet{font-size:13.5px;color:var(--specialist-text-dim);padding:8px 12px}
        /* کار اصلی نوبت (partials/primary-action): یک رنگ و یک نام همه‌جا */
        .spec-act{display:inline-flex;align-items:center;justify-content:center;gap:6px;min-height:36px;padding:0 16px;border-radius:999px;border:1px solid transparent;font:inherit;font-size:13.5px;font-weight:800;white-space:nowrap;cursor:pointer;background:rgb(var(--m-tx));color:rgb(var(--m-bg));text-decoration:none;transition:opacity .15s}
        .spec-act:hover{opacity:.88}
        .spec-act--ghost{background:transparent;color:rgb(var(--m-tx));border-color:rgb(var(--m-gold) / .35);font-weight:700}
        .spec-act--ghost:hover{background:rgb(var(--m-tx) / .05);opacity:1}
        .spec-act--lg{min-height:46px;font-size:15px;padding:0 20px}
        /* منوی «⋯» ردیف (components/specialist/row-menu) */
        .spec-menu{position:relative}
        .spec-menu>summary{list-style:none}
        .spec-menu>summary::-webkit-details-marker{display:none}
        .spec-menu__btn{width:36px;height:36px;display:inline-flex;align-items:center;justify-content:center;border-radius:999px;cursor:pointer;border:1px solid var(--specialist-border);background:var(--specialist-surface);color:var(--specialist-text)}
        .spec-menu__btn:hover,.spec-menu[open]>.spec-menu__btn{border-color:rgb(var(--m-gold));background:rgb(var(--m-gold) / .1)}
        .spec-menu__panel{position:absolute;z-index:45;top:calc(100% + 6px);left:0;width:12.5rem;padding:.35rem;display:flex;flex-direction:column;background:var(--specialist-surface);border:1px solid var(--specialist-border);border-radius:12px;box-shadow:0 18px 40px -18px rgba(0,0,0,.45)}
        .spec-menu__item{display:block;width:100%;text-align:right;padding:.55rem .65rem;border-radius:8px;font:inherit;font-size:.86rem;color:var(--specialist-text);background:transparent;border:0;cursor:pointer;text-decoration:none}
        .spec-menu__item:hover,.spec-menu__item:focus-visible{background:rgb(var(--m-gold) / .1);outline:none}
        .spec-menu__item--danger{color:rgb(var(--m-bad));font-weight:700}
        .spec-menu__item--danger:hover,.spec-menu__item--danger:focus-visible{background:rgb(var(--m-bad) / .1)}
        .spec-menu__sep{height:1px;background:var(--specialist-border);margin:.35rem .25rem}
        .spec-badge{display:inline-flex;align-items:center;justify-content:center;min-width:20px;padding:0 7px;border-radius:999px;font-size:12px;font-weight:700;line-height:20px;background:rgb(var(--m-gold) / .18);color:rgb(var(--m-gold2))}
    </style>
    @stack('styles')
    @include('partials.ui-handlers')
</head>
<body class="m-panel text-[var(--specialist-text)]" style="background-color: var(--specialist-bg);">

<div class="flex h-screen overflow-hidden">

    @php
        /*
         * ⭐ بسته‌ی «و»: پنل کارشناس هم قالب سایت سالن را می‌گیرد: منوی کناری (طلایی ماهرو، ویترین) یا نوار بالایی
         * (مجله: زبانه‌ی زیرخط‌دار، آرام: کلید قرصی، بنتو: کاشی). روی موبایل همیشه کشو + منوی پایین.
         * بسته‌ی ۳ ظاهری (۲۰۲۶-۱۱-۲۵): فهرست منو فقط از App\Support\Specialist\SpecialistMenu (با نشان شمار «نوبت‌ها»).
         */
        $__specLayout = app(\App\Support\Themes\CurrentAppearance::class)->get()->layout;
        $__specTop = in_array($__specLayout, ['majalle', 'aram', 'bento'], true);
        $__specSalon = app(\App\Support\CurrentSalon::class)->get();
        $__specialist = $__specSalon ? auth()->user()?->specialist : null;
        $__specNav = \App\Support\Specialist\SpecialistMenu::items($__specialist);
    @endphp
    <aside id="sidebar" data-nav-scroll class="spec-side w-64 min-h-screen overflow-y-auto hidden {{ $__specTop ? '' : 'md:block' }} transition-all duration-300 z-30 border-l" style="background-color: var(--specialist-surface); border-color: var(--specialist-border);">
        <div class="px-4 pt-5 pb-4 border-b" style="border-color: var(--specialist-border);">
            <a href="{{ route('specialist.my-dashboard') }}" class="flex items-center gap-2.5">
                {{-- ⭐ لوگوی سالن کارشناس (۲۰۲۶-۰۹-۲۴)، وگرنه آیکون پیش‌فرض. --}}
                <x-brand-emblem class="w-9 h-9 rounded" pad="3px" />
                <span class="flex flex-col min-w-0">
                    <b class="text-[15px] text-[var(--specialist-text)]">پنل کارشناس</b>
                    @if ($__specSalon)
                        <span class="text-xs truncate text-[var(--specialist-text-dim)]" data-spec-salon>{{ $__specSalon->name }}</span>
                    @endif
                </span>
            </a>
        </div>

        <nav class="mt-3 px-3 space-y-0.5" aria-label="منوی پنل کارشناس" data-spec-nav>
            @foreach ($__specNav as $__item)
                <a href="{{ $__item['url'] }}" data-spec-nav-item="{{ $__item['key'] }}" @if ($__item['active']) aria-current="page" @endif
                   class="spec-side__link {{ $__item['active'] ? 'is-active' : '' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $__item['icon'] }}" />
                    </svg>
                    <span class="flex-1">{{ $__item['label'] }}</span>
                    @if ($__item['badge'] > 0)
                        <span class="spec-badge" data-spec-badge aria-label="{{ fa_num($__item['badge']) }} نوبت منتظر تأیید">{{ fa_num($__item['badge']) }}</span>
                    @endif
                </a>
            @endforeach

            <div class="border-t mt-3 pt-3 space-y-0.5" style="border-color: var(--specialist-border);">
                {{-- ⭐ رزرو برای خودِ کارشناس (۲۰۲۶-۱۰-۲۲) — همان رزرو سایت سالن؛ منوی سایت برای کارشناس «پنل کاری» دارد تا برگردد --}}
                <a href="{{ route('bookings.create') }}" data-book-for-myself class="spec-side__link spec-side__link--quiet">رزرو نوبت برای خودم</a>
                <a href="{{ route('home') }}" class="spec-side__link spec-side__link--quiet">صفحه‌ی سالن</a>
                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <button type="submit" class="spec-side__link spec-side__link--quiet w-full text-m-bad">خروج</button>
                </form>
            </div>
        </nav>
    </aside>

    <div id="sidebar-backdrop" class="md:hidden fixed inset-0 bg-black/60 z-40 hidden" data-ui-click="toggleSidebar"></div>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">

        <header class="border-b sticky top-0 z-10 h-16 flex items-center justify-between px-4" style="background-color: var(--specialist-surface); border-color: var(--specialist-border);">
            <div class="flex items-center">
                <button data-ui-click="toggleSidebar" class="md:hidden text-[var(--specialist-text-dim)] hover:text-[var(--specialist-plum-light)] focus:outline-none ml-3">
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
                        <span id="notification-count" class="absolute top-0 right-0 hidden items-center justify-center px-2 py-1 text-xs font-bold leading-none text-m-onsolid bg-m-bad rounded-full">۰</span>
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
                                <p class="text-xs text-[var(--specialist-plum-muted)] truncate">کارشناس زیبایی</p>
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

                            {{-- ۲۰۲۶-۱۱-۰۶: همیشه در دسترس، حتی پس از بستن کارت داشبورد --}}
                            <a href="{{ route('specialist.install') }}" data-staff-app-menu
                               class="text-[var(--specialist-text-dim)] hover:bg-m-tx/5 hover:text-[var(--specialist-text)] flex items-center px-4 py-2 text-sm"
                               role="menuitem" tabindex="-1">
                                <svg class="w-5 h-5 mr-2 text-[var(--specialist-inactive)]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <rect x="6" y="2" width="12" height="20" rx="3" /><path stroke-linecap="round" d="M11 18h2" />
                                </svg>
                                نصب اپ روی گوشی
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
            <nav class="spec-top spec-top--{{ $__specLayout }} hidden md:flex" aria-label="منوی پنل کارشناس">
                @foreach ($__specNav as $__item)
                    <a href="{{ $__item['url'] }}" @if ($__item['active']) aria-current="page" @endif>{{ $__item['label'] }}@if ($__item['badge'] > 0) <span class="spec-badge" data-spec-badge>{{ fa_num($__item['badge']) }}</span>@endif</a>
                @endforeach
                <a href="{{ route('bookings.create') }}" class="spec-top__site" data-book-for-myself>رزرو برای خودم</a>
                <a href="{{ route('home') }}">صفحه‌ی سالن</a>
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
                    <button data-ui-click="closeNotification" data-ui-args='["success-notification"]' class="text-m-ok hover:text-m-ok transition-colors">
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
                    <button data-ui-click="closeNotification" data-ui-args='["error-notification"]' class="text-m-bad hover:text-m-bad transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            @endif

            <div class="spec-main-inner">
            @yield('content')
            </div>
            {{-- بسته‌ی ۳ ظاهری: پانویس داخل ناحیه‌ی اسکرول تا در لپ‌تاپ ۱۳۶۶×۷۶۸ همیشه جا نگیرد --}}
            <footer class="hidden md:block mt-8 pt-4 border-t text-center text-sm" style="border-color: var(--specialist-border); color: var(--specialist-text-dim);" data-spec-footer>
                © {{ fa_jdate(now(), 'Y') }} ماهرو، پنل کارشناس.
            </footer>
        </main>

    </div>
</div>

<nav class="md:hidden fixed bottom-0 inset-x-0 z-40 border-t grid grid-cols-4" style="background-color: var(--specialist-surface); border-color: var(--specialist-border);" aria-label="منوی پایین" data-spec-bottom>
    @foreach (\App\Support\Specialist\SpecialistMenu::bottom($__specNav) as $__item)
        <a href="{{ $__item['url'] }}" @if ($__item['active']) aria-current="page" @endif
           class="relative flex flex-col items-center justify-center gap-1 py-2.5 text-xs font-medium {{ $__item['active'] ? 'text-[var(--specialist-plum-light)] font-bold' : 'text-[var(--specialist-text-dim)]' }}">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $__item['icon'] }}" />
            </svg>
            {{ $__item['short'] }}
            @if ($__item['badge'] > 0)
                <span class="spec-badge absolute" style="top:2px; right:calc(50% - 30px); min-width:18px; line-height:18px; padding:0 5px; font-size:11px;" data-spec-badge>{{ fa_num($__item['badge']) }}</span>
            @endif
        </a>
    @endforeach
</nav>

<script @nonce>
    // امنیت (H2): متن آزاد کاربر (نام مشتری، نام خدمت، متن اعلان) پیش از innerHTML escape می‌شود
    function escHtml(v) { return String(v ?? '').replace(/[&<>"']/g, (c) => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c])); }
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
                    countElement.textContent = String(count > 99 ? '99+' : count).replace(/\d/g, d => '۰۱۲۳۴۵۶۷۸۹'[d]);
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
                        <span class="truncate w-full">${escHtml(notification.message)}</span>
                        <span class="text-xs ${notification.read_at ? 'text-[var(--specialist-inactive)]' : 'text-[var(--specialist-plum-mid)]'} mt-0.5">${escHtml(notification.time_ago)}</span>
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

    // منوی کشویی موبایل. قبلاً با toggle کلاس‌ها باز می‌شد و چون z-30 از اول روی aside بود، با باز کردن برداشته می‌شد و منو زیر
    // محتوای صفحه می‌ماند (فقط سایه دیده می‌شد — گزارش آیفون ۲۰۲۶-۱۰-۲۱). حالا وضعیت صریح است و منو fixed روی همه‌چیز.
    function toggleSidebar(force) {
        const sidebar = document.getElementById('sidebar');
        const backdrop = document.getElementById('sidebar-backdrop');
        if (!sidebar || !backdrop) return;
        const open = typeof force === 'boolean' ? force : sidebar.classList.contains('hidden');

        sidebar.classList.toggle('hidden', !open);
        ['fixed', 'inset-y-0', 'right-0', 'z-50'].forEach(function (c) { sidebar.classList.toggle(c, open); });
        sidebar.toggleAttribute('data-sidebar-open', open);
        backdrop.classList.toggle('hidden', !open);
    }
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') toggleSidebar(false); });

    // H3 مرحله‌ی ۲: دکمه‌های data-ui-click (partials/ui-handlers)
    uiActions.register({
        toggleSidebar: function () { toggleSidebar(); },
        closeNotification: function (event, id) { closeNotification(id); },
    });

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

{{-- بسته‌ی ۶: PWA (service worker، بنر نصب آیفون) --}}
@include('partials.pwa-client', ['pwaMode' => 'specialist', 'pwaGuideUrl' => route('specialist.install')])
<script @nonce>
    // منوی «⋯» ردیف‌ها (بسته‌ی ۳ ظاهری، همان رفتار پنل مدیر): پنل fixed کنار دکمه، فقط یکی باز، بستن با کلیک بیرون/Escape
    (function () {
        var menus = document.querySelectorAll('[data-spec-menu-root]');
        if (!menus.length) return;
        function closeAll(except) { menus.forEach(function (m) { if (m !== except) m.open = false; }); }
        function place(menu) {
            var r = menu.querySelector('summary').getBoundingClientRect(), panel = menu.querySelector('.spec-menu__panel');
            panel.style.position = 'fixed';
            var w = panel.offsetWidth, h = panel.offsetHeight, top = r.bottom + 6;
            if (top + h > window.innerHeight - 8 && r.top - h - 6 > 8) top = r.top - h - 6;
            panel.style.left = Math.max(8, Math.min(r.left, window.innerWidth - w - 8)) + 'px';
            panel.style.top = top + 'px';
        }
        menus.forEach(function (menu) {
            menu.addEventListener('toggle', function () {
                if (!menu.open) return;
                closeAll(menu); place(menu);
                var first = menu.querySelector('[role=menuitem]');
                if (first) first.focus({ preventScroll: true });
            });
        });
        document.addEventListener('click', function (e) { if (!e.target.closest('[data-spec-menu-root]')) closeAll(null); });
        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Escape') return;
            var open = document.querySelector('[data-spec-menu-root][open]');
            if (open) { open.open = false; open.querySelector('summary').focus(); }
        });
        function follow() {
            var open = document.querySelector('[data-spec-menu-root][open]');
            if (!open) return;
            var r = open.querySelector('summary').getBoundingClientRect();
            if (r.bottom < 0 || r.top > window.innerHeight || r.right < 0 || r.left > window.innerWidth) { open.open = false; return; }
            place(open);
        }
        window.addEventListener('scroll', follow, true);
        window.addEventListener('resize', follow);
    })();
</script>
@stack('scripts')
@include('partials.password-reveal')
@include('partials.nav-reveal-active')
</body>
</html>
