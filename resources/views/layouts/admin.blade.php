<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl" class="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') | پنل مدیریت سالن زیبایی</title>

    @include('partials.favicons')
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx', 'resources/js/vendor/sweetalert.js'])
    @include('partials.mahru-theme', ['themeScope' => 'admin'])
    @include('partials.mahru-panel')
    @stack('styles')
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
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .notification-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            min-width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: bold;
            padding: 0 4px;
            border-radius: 9px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        /* ⭐ بسته‌ی «و»: قالب‌های پنل مدیر */
        .adm-drawer{width:16rem}
        /* بسته‌ی ۲ ظاهری (۲۰۲۶-۱۱-۲۴): منوی گروه‌بندی‌شده، نشان شمار و دکمه‌ی «ثبت نوبت» */
        .adm-drawer__site{display:block;margin-top:.5rem;font-size:.75rem;color:var(--admin-accent);text-align:right;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
        .adm-drawer__glabel{font-size:.72rem;font-weight:600;padding:0 .75rem .35rem;color:var(--admin-text-light)}
        .adm-drawer__sub{margin:.75rem;padding:.75rem;border-radius:12px;background:var(--admin-accent-light);font-size:.78rem;line-height:1.9;color:var(--admin-text-dim)}
        .adm-group{margin:.15rem 0}
        .adm-group>summary{list-style:none}
        .adm-group>summary::-webkit-details-marker{display:none}
        .adm-group__head{display:flex;align-items:center;gap:.5rem;padding:.6rem .75rem;border-radius:10px;cursor:pointer;font-size:.86rem;font-weight:600;color:var(--admin-text)}
        .adm-group__head:hover{background:var(--admin-accent-light)}
        .adm-group__head::after{content:"";width:.4rem;height:.4rem;border:solid var(--admin-text-light);border-width:0 0 2px 2px;transform:rotate(-45deg);transition:transform .15s;margin-inline-start:.25rem}
        .adm-group[open]>.adm-group__head::after{transform:rotate(135deg)}
        .adm-group__n{font-size:.72rem;font-weight:400;color:var(--admin-text-light)}
        .adm-count{display:inline-flex;align-items:center;justify-content:center;min-width:1.4rem;padding:0 .45rem;border-radius:999px;font-size:.72rem;font-weight:700;background:rgb(var(--m-gold) / .18);color:var(--admin-accent-hover, var(--admin-accent))}
        .adm-new-booking{display:inline-flex;align-items:center;gap:.35rem;padding:.5rem .95rem;border-radius:999px;font-size:.85rem;font-weight:700;background:var(--admin-accent);color:rgb(var(--m-on-solid))}
        .adm-new-booking:hover{background:var(--admin-accent-hover)}
        /* میانبرهای صفحه‌کلید (بسته‌ی ۲ب) */
        kbd{display:inline-flex;align-items:center;justify-content:center;min-width:1.5rem;padding:0 .35rem;border-radius:6px;border:1px solid var(--admin-border);border-bottom-width:2px;background:var(--admin-bg);font:600 .72rem/1.6 inherit;color:var(--admin-text)}
        .adm-kbd-then{font-size:.68rem;color:var(--admin-text-light);margin:0 .15rem}
        .adm-shortcuts{width:min(46rem,calc(100vw - 2rem));max-height:calc(100vh - 3rem);padding:0;border:1px solid var(--admin-border);border-radius:18px;background:var(--admin-surface);color:var(--admin-text);box-shadow:0 30px 60px -20px rgba(0,0,0,.5)}
        .adm-shortcuts::backdrop{background:rgba(0,0,0,.45)}
        .adm-shortcuts__head{display:flex;align-items:center;justify-content:space-between;padding:1rem 1.25rem;border-bottom:1px solid var(--admin-border)}
        .adm-shortcuts__head h2{margin:0;font-size:1.05rem;font-weight:800}
        .adm-shortcuts__close{width:2rem;height:2rem;border-radius:8px;color:var(--admin-text-dim)}
        .adm-shortcuts__close:hover{background:var(--admin-accent-light)}
        .adm-shortcuts__intro{margin:0;padding:.85rem 1.25rem 0;font-size:.85rem;line-height:1.9;color:var(--admin-text-dim)}
        .adm-shortcuts__toggle{display:flex;align-items:center;gap:.5rem;margin:.75rem 1.25rem 0;padding:.6rem .8rem;border-radius:10px;background:var(--admin-accent-light);font-size:.88rem;font-weight:600}
        .adm-shortcuts__hint{font-size:.75rem;font-weight:400;color:var(--admin-text-light)}
        .adm-shortcuts__grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(16rem,1fr));gap:.25rem 1.5rem;padding:.75rem 1.25rem 1.25rem}
        .adm-shortcuts h3{margin:.75rem 0 .35rem;font-size:.78rem;font-weight:700;color:var(--admin-text-light)}
        .adm-shortcuts dl{margin:0}
        .adm-shortcuts dl>div{display:flex;align-items:center;justify-content:space-between;gap:.75rem;padding:.3rem 0;border-bottom:1px dashed var(--admin-border);font-size:.85rem}
        .adm-shortcuts dt{order:2;white-space:nowrap}
        .adm-shortcuts dd{margin:0}
        .adm-seq{position:fixed;bottom:1.25rem;left:1.25rem;z-index:60;padding:.4rem .8rem;border-radius:10px;background:var(--admin-text);color:var(--admin-surface);font-weight:700;font-size:.85rem;direction:ltr}
        .adm-seq[hidden]{display:none}
        .adm-shortcut-hint{margin-inline-start:auto;font-size:.68rem;color:var(--admin-text-light);direction:ltr;letter-spacing:.04em}
        /* کار ردیف و منوی «⋯» (بسته‌ی ۲ ظاهری) */
        .adm-act{display:inline-flex;align-items:center;white-space:nowrap;padding:.42rem .85rem;border-radius:999px;font-size:.8rem;font-weight:700;border:1px solid var(--admin-border);background:var(--admin-surface);color:var(--admin-text)}
        .adm-act:hover{border-color:var(--admin-accent)}
        .adm-act--primary{background:var(--admin-text);border-color:var(--admin-text);color:var(--admin-surface)}
        .adm-act--primary:hover{opacity:.9}
        .adm-act--ghost{border-color:transparent;background:transparent;color:var(--admin-accent);font-weight:600}
        .adm-act--ghost:hover{background:var(--admin-accent-light);border-color:transparent}
        .adm-menu{position:relative}
        .adm-menu>summary{list-style:none}
        .adm-menu>summary::-webkit-details-marker{display:none}
        .adm-menu__btn{width:34px;height:34px;display:inline-flex;align-items:center;justify-content:center;border-radius:10px;cursor:pointer;border:1px solid var(--admin-border);background:var(--admin-surface);color:var(--admin-text)}
        .adm-menu__btn:hover,.adm-menu[open]>.adm-menu__btn{border-color:var(--admin-accent);background:var(--admin-accent-light)}
        .adm-menu__panel{position:absolute;z-index:45;top:calc(100% + 6px);left:0;width:12.5rem;padding:.35rem;display:flex;flex-direction:column;background:var(--admin-surface);border:1px solid var(--admin-border);border-radius:12px;box-shadow:0 18px 40px -18px rgba(0,0,0,.45)}
        .adm-menu__item{display:block;width:100%;text-align:right;padding:.55rem .65rem;border-radius:8px;font-size:.86rem;color:var(--admin-text);background:transparent}
        .adm-menu__item:hover,.adm-menu__item:focus-visible{background:var(--admin-accent-light);outline:none}
        .adm-menu__item--danger{color:rgb(var(--m-bad));font-weight:700}
        .adm-menu__item--danger:hover,.adm-menu__item--danger:focus-visible{background:rgb(var(--m-bad) / .1)}
        .adm-menu__sep{height:1px;background:var(--admin-border);margin:.35rem .25rem}
        .adm-link{color:var(--admin-text-dim)}
        .adm-link:not(.sidebar-active):hover{background-color:var(--admin-accent-light)}
        .adm-logout{color:var(--admin-text-dim)}
        .adm-logout:hover{background-color:rgb(var(--m-bad) / .1);color:rgb(var(--m-bad))}
        /* ریل آیکونی (بسته‌ی ۲ب): ریل ثابت ۷۶px + پنل کشویی گروه‌ها */
        .adm-rail-slot{position:relative;width:76px;flex:0 0 76px}
        .adm-rail{position:absolute;top:0;right:0;bottom:0;width:76px;z-index:45;overflow-y:auto;overflow-x:hidden;background:var(--admin-surface);border-left:1px solid var(--admin-border);padding:14px 0;display:flex;flex-direction:column;align-items:center;gap:6px}
        .adm-rail__brand{display:flex;align-items:center;justify-content:center;margin-bottom:8px}
        .adm-rail__mark{width:44px;height:44px;border-radius:14px;background:var(--m-btn);color:var(--m-on-gold);display:flex;align-items:center;justify-content:center;font-weight:900}
        .adm-rail__icon{position:relative;width:46px;height:46px;flex:none;border-radius:14px;display:flex;align-items:center;justify-content:center;color:var(--admin-text-dim);background:transparent;transition:background .15s,color .15s}
        .adm-rail__icon:hover,.adm-rail__icon.is-open{background:var(--admin-accent-light);color:var(--admin-text)}
        .adm-rail__icon.is-active{background:rgb(var(--m-gold));color:rgb(var(--m-on-solid))}
        .adm-rail__icon.is-current{box-shadow:inset -3px 0 0 rgb(var(--m-gold));color:var(--admin-text)}
        .adm-rail__icon:focus-visible{outline:2px solid rgb(var(--m-gold));outline-offset:2px}
        .adm-rail__count{position:absolute;top:3px;left:3px;min-width:17px;height:17px;padding:0 4px;border-radius:999px;font-size:10.5px;font-weight:700;background:rgb(var(--m-bad));color:#fff;display:flex;align-items:center;justify-content:center}
        .adm-rail__count.hidden{display:none}
        .adm-rail__dot{position:absolute;top:9px;left:9px;width:8px;height:8px;border-radius:50%;background:rgb(var(--m-gold))}
        .adm-rail__sep{width:28px;height:1px;background:var(--admin-border);margin:8px 0}
        .adm-rail__logout:hover{background:rgb(var(--m-bad) / .1);color:rgb(var(--m-bad))}
        /* صفحه‌ی کوتاه (لپ‌تاپ ۷۶۸px با نوار ابزار مرورگر): ریل فشرده‌تر تا نشان و کار روزانه همیشه دیده شوند */
        @media (max-height:760px){.adm-rail{padding:10px 0;gap:3px}.adm-rail__icon{width:40px;height:40px;border-radius:12px}.adm-rail__brand{margin-bottom:4px}.adm-rail__brand>*{width:2.25rem;height:2.25rem}.adm-rail__sep{margin:5px 0}}
        .adm-rail-panel{position:fixed;z-index:60;width:16rem;padding:10px;border-radius:18px;background:var(--admin-surface);border:1px solid var(--admin-border);box-shadow:0 30px 60px -20px rgba(0,0,0,.55)}
        .adm-rail-panel[hidden]{display:none}
        .adm-rail-panel__head{display:flex;justify-content:space-between;align-items:baseline;padding:6px 12px 10px}
        .adm-rail-panel__head b{font-size:15px;color:var(--admin-text)}
        .adm-rail-panel__head span{font-size:12px;color:var(--admin-text-light)}
        .adm-rail-panel__item{display:flex;align-items:center;gap:8px;padding:9px 12px;border-radius:10px;font-size:14px;color:var(--admin-text)}
        .adm-rail-panel__item:hover,.adm-rail-panel__item:focus-visible{background:var(--admin-accent-light);outline:none}
        .adm-rail-panel__item.is-active{background:rgb(var(--m-gold) / .14);color:rgb(var(--m-gold));font-weight:700}
        .adm-rail-panel__kbd{font-size:10.5px;color:var(--admin-text-light)}
        .adm-rail-panel__note{margin:8px 12px 4px;font-size:12px;line-height:1.8;color:var(--admin-text-light)}
        .adm-cmd{align-items:center;gap:10px;min-width:22rem;min-height:40px;padding:0 14px;border-radius:10px;background:var(--admin-accent-light);color:var(--admin-text-dim);font-size:14px}
        .adm-cmd kbd{margin-inline-start:auto;font-size:11px;border:1px solid var(--admin-border);border-radius:6px;padding:1px 7px;font-family:inherit}
        .adm--rail .admin-btn-primary,.adm--rail button,.adm--rail .rounded-xl{border-radius:10px}
        /* منوی بالایی */
        /* منوی بالایی (بسته‌ی ۲ب): ردیف دوم هدر + کارت‌های کشویی دوستونه */
        .adm-top{gap:2px;padding:0 16px;border-top:1px solid var(--admin-border);align-items:stretch;flex-wrap:wrap}
        .adm-top__link{display:flex;align-items:center;gap:6px;padding:12px 14px;font-size:14px;color:var(--admin-text-dim);border-bottom:2px solid transparent;cursor:pointer;list-style:none;white-space:nowrap}
        .adm-top__link::-webkit-details-marker{display:none}
        .adm-top__link:hover{color:var(--admin-text)}
        .adm-top__link:focus-visible{outline:2px solid rgb(var(--m-gold));outline-offset:-4px;border-radius:8px}
        .adm-top__link.is-active{color:var(--admin-text);font-weight:700;border-bottom-color:rgb(var(--m-gold))}
        .adm-top__group[open] .adm-top__chev{transform:rotate(180deg)}
        .adm-top__sep{width:1px;margin:12px 8px;background:var(--admin-border)}
        .adm-top__group{position:relative}
        .adm-top__panel{position:absolute;top:calc(100% + 4px);right:0;z-index:50;width:min(38rem,calc(100vw - 2rem));padding:12px;border-radius:18px;background:var(--admin-surface);border:1px solid var(--admin-border);box-shadow:0 30px 60px -20px rgba(0,0,0,.45)}
        .adm-top__panel-head{display:flex;justify-content:space-between;align-items:baseline;padding:4px 10px 10px}
        .adm-top__panel-head b{font-size:15px;color:var(--admin-text)}
        .adm-top__panel-head span{font-size:12px;color:var(--admin-text-light)}
        .adm-top__cards{display:grid;grid-template-columns:1fr 1fr;gap:4px}
        .adm-top__item{display:flex;align-items:flex-start;gap:10px;padding:10px 12px;border-radius:12px;color:var(--admin-text)}
        .adm-top__item:hover,.adm-top__item:focus-visible{background:var(--admin-accent-light);outline:none}
        .adm-top__item.is-active{background:rgb(var(--m-gold) / .12)}
        .adm-top__item.is-active .adm-top__name{color:rgb(var(--m-gold))}
        .adm-top__ico{width:34px;height:34px;flex:none;border-radius:10px;display:flex;align-items:center;justify-content:center;background:var(--admin-accent-light);color:var(--admin-text-dim)}
        .adm-top__item.is-active .adm-top__ico{background:rgb(var(--m-gold));color:rgb(var(--m-on-solid))}
        .adm-top__name{display:flex;align-items:center;gap:6px;font-size:14px;font-weight:700}
        .adm-top__hint{display:block;margin-top:2px;font-size:12px;line-height:1.7;color:var(--admin-text-light)}
        .adm-top__kbd{margin-top:2px;font-size:10.5px;color:var(--admin-text-light)}
        .adm-sub-pill{display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:999px;font-size:12px;background:var(--admin-accent-light);color:var(--admin-text-dim);white-space:nowrap}
        .adm-sub-pill.is-soon{background:rgb(var(--m-bad) / .12);color:rgb(var(--m-bad))}
        .adm--top main > *{max-width:1360px;margin-left:auto;margin-right:auto}
        .adm--top .rounded-lg,.adm--top .rounded-xl{border-radius:16px}
        /* فشرده‌ی پرتراکم */
        /* میز پذیرش (بسته‌ی ۲ب): منوی ۲۰۰px شب‌آبی، فقط متن ۱۳px، گروه‌های جمع‌شو (کار روزانه باز) */
        .adm--dense .adm-drawer{width:200px}
        @media (min-width:768px){
            .adm--dense .adm-drawer{--dk-bg:#0F1724;--dk-line:#1E2A3B;--dk-text:#D4DCE6;--dk-dim:#8FA0B5;--dk-accent:#5FE0B7;background:var(--dk-bg)!important;border-color:var(--dk-line)!important}
            .adm--dense .adm-drawer>div:first-child{border-color:var(--dk-line)!important;padding:.85rem .75rem .7rem}
            .adm--dense .adm-drawer b,.adm--dense .adm-drawer h2{color:#fff!important}
            .adm--dense .adm-drawer .adm-drawer__site{color:var(--dk-accent)}
            .adm--dense .adm-drawer .adm-drawer__glabel,.adm--dense .adm-drawer .adm-group__head{font-size:11.5px;font-weight:500;color:var(--dk-dim);padding:.35rem .6rem}
            .adm--dense .adm-drawer .adm-group__head:hover{background:transparent;color:#fff}
            .adm--dense .adm-drawer .adm-group__head::after{border-color:var(--dk-dim)}
            .adm--dense .adm-drawer .adm-group__n{display:none}
            .adm--dense .adm-drawer .adm-link{padding:.3rem .6rem;font-size:13px;margin-bottom:1px;border-radius:8px;color:var(--dk-text)}
            .adm--dense .adm-drawer .adm-link svg{display:none}
            .adm--dense .adm-drawer .adm-link:not(.sidebar-active):hover{background:var(--dk-line);color:#fff}
            .adm--dense .adm-drawer .adm-link.sidebar-active{background:var(--dk-line)!important;color:#fff!important;box-shadow:inset -2px 0 0 var(--dk-accent)}
            .adm--dense .adm-drawer .adm-count{background:transparent;color:var(--dk-accent);font-size:11px;padding:0}
            .adm--dense .adm-drawer .adm-drawer__sub{background:var(--dk-line);color:var(--dk-dim);font-size:12px}
            .adm--dense .adm-drawer .adm-drawer__sub a{color:var(--dk-accent)!important}
            .adm--dense .adm-drawer nav{margin-top:.25rem;padding-inline:.4rem}
        }
        .adm--dense main{padding:.6rem .8rem;font-size:13px}
        .adm--dense main table th,.adm--dense main table td{padding-top:.4rem!important;padding-bottom:.4rem!important}
        .adm--dense main .p-6{padding:.9rem}
        .adm--dense main .p-5{padding:.8rem}
        .adm--dense main .p-4{padding:.7rem}
        .adm--dense main .gap-6{gap:.8rem}
        .adm--dense main .mb-6{margin-bottom:.8rem}
        .adm--dense .rounded-xl,.adm--dense .rounded-2xl,.adm--dense .admin-card{border-radius:8px}
        .adm--dense header .py-3{padding-top:.4rem;padding-bottom:.4rem}

        .notification-item-unread {
            background: var(--admin-accent-light);
            border-right: 3px solid var(--admin-accent);
        }

    </style>
    @include('partials.ui-handlers')
</head>
@php
    // ⭐ بسته‌ی «و»: قالب پنل مدیر (side / rail / top / dense) و منوی یکتای AdminMenu
    $adminSalon = app(\App\Support\CurrentSalon::class)->get();
    $adminShell = app(\App\Support\Themes\CurrentAppearance::class)->get()->admin;
    $adminMenuAll = \App\Support\Admin\AdminMenu::groups(auth()->user(), $adminSalon);
    // بسته‌ی ۲ ظاهری: پشتیبانی و پروفایل در منوی حساب نوار بالا، بقیه در منوی پوسته
    $adminMenu = \App\Support\Admin\AdminMenu::sideGroups($adminMenuAll);
    $adminAccountItems = \App\Support\Admin\AdminMenu::accountItems($adminMenuAll);
@endphp
<body class="rtl m-panel adm adm--{{ $adminShell }}" data-admin-shell="{{ $adminShell }}" style="background-color: var(--admin-bg); color: var(--admin-text);">
<div class="flex h-screen overflow-hidden">
    @include('layouts.admin-shell.drawer', ['menu' => $adminMenu, 'desktop' => in_array($adminShell, ['side', 'dense'], true)])
    @if ($adminShell === 'rail')
        @include('layouts.admin-shell.rail', ['menu' => $adminMenu])
    @endif

    <div id="sidebar-backdrop" class="md:hidden fixed inset-0 bg-gray-900 bg-opacity-50 z-40 hidden" data-ui-click="toggleSidebar"></div>

    <div class="flex-1 flex flex-col relative overflow-hidden">
        @if (\App\Services\SuperAdmin\SuperAdminImpersonation::active())
            {{-- ⭐ «ورود اضطراری» ابرمدیر (۲۰۲۶-۱۰-۱۵) --}}
            <div data-impersonation-banner style="background: #0D4B5C; color: #FFFFFF;" class="px-4 py-2 text-sm flex flex-wrap items-center justify-between gap-2">
                <span>حالت پشتیبانی ابرمدیر: با حساب <b>{{ auth()->user()->name }}</b> در پنل این سالن هستید. هر کاری به نام مالک انجام می‌شود.</span>
                <form method="POST" action="{{ route('impersonation.leave') }}">
                    @csrf
                    <button type="submit" class="px-3 py-1 rounded-full font-bold" style="background: #FFFFFF; color: #0D4B5C;">بازگشت به پنل ابرمدیر</button>
                </form>
            </div>
        @endif
        <!-- Header -->
        <header class="sticky top-0 z-30 shadow-sm" style="background: var(--admin-surface); border-bottom: 1px solid var(--admin-border);">
            <div class="px-4 py-3 flex justify-between items-center">
                <button type="button" class="md:hidden focus:outline-none" style="color: var(--admin-text-dim);" data-ui-click="toggleSidebar">
                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="3" y1="12" x2="21" y2="12"></line>
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="3" y1="18" x2="21" y2="18"></line>
                    </svg>
                </button>

                <h1 class="text-lg font-bold md:hidden" style="color: var(--admin-text);">@yield('title')</h1>
                @if ($adminShell === 'top')
                    <a href="{{ route('admin.dashboard') }}" class="hidden md:flex items-center gap-2 font-bold" style="color: var(--admin-text);">
                        @if ($adminSalon)<x-brand-emblem class="w-9 h-9 rounded" pad="3px" />@endif
                        {{ $adminSalon?->name ?? 'پنل مدیریت' }}
                        @if ($adminSalon?->subscription_ends_at)
                            @php $subSoon = $adminSalon->subscription_ends_at->lt(now()->addDays(7)); @endphp
                            <span class="adm-sub-pill {{ $subSoon ? 'is-soon' : '' }}" data-sub-pill>اشتراک تا {{ fa_date($adminSalon->subscription_ends_at) }}</span>
                        @endif
                    </a>
                @endif
                @if (in_array($adminShell, ['rail', 'top'], true))
                    <button type="button" class="adm-cmd hidden md:flex" data-ui-click="toggleSearchModal">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">{!! \App\Support\Admin\AdminMenu::ICONS['search'] !!}</svg>
                        <span>جستجوی خدمت، کارشناس یا کاربر…</span>
                        <kbd dir="ltr">Ctrl K</kbd>
                    </button>
                @endif

                <div class="flex items-center space-x-3 space-x-reverse">
                    @permission('create-bookings')
                        <a href="{{ route('admin.bookings.create') }}" class="adm-new-booking" data-new-booking>
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                            <span class="hidden sm:inline">ثبت نوبت</span>
                        </a>
                    @endpermission
                    @include('partials.admin-salon-switcher')
                    @include('partials.mahru-theme-toggle')
                    <!-- Search button -->
                    <button type="button"
                            id="search-button"
                            class="p-2 rounded-lg focus:outline-none transition-colors"
                            style="color: var(--admin-text-dim);"
                            data-ui-hover="background-color: var(--admin-accent-light)"
                            data-ui-unhover="background-color: "
                            data-ui-click="toggleSearchModal">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                    </button>

                    <!-- Announcements dropdown -->
                    <div class="relative inline-block text-left">
                        <button type="button"
                                id="notification-button"
                                class="p-2 rounded-lg focus:outline-none transition-colors relative"
                                style="color: var(--admin-text-dim);"
                                data-ui-hover="background-color: var(--admin-accent-light)"
                                data-ui-unhover="background-color: "
                                aria-expanded="false" aria-haspopup="true">
                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                                <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                            </svg>
                            <span id="notification-count"
                                  class="absolute top-0 right-0 hidden items-center justify-center px-1.5 py-0.5 text-xs font-bold leading-none rounded-full"
                                  style="background: rgb(var(--m-bad)); color: rgb(var(--m-on-solid)); min-width: 18px; text-align: center;">۰</span>
                        </button>

                        <div id="notification-dropdown"
                             class="hidden absolute right-0 z-50 mt-2 w-80 rounded-xl shadow-lg ring-1 focus:outline-none"
                             style="background: var(--admin-surface); border-color: var(--admin-border); border: 1px solid var(--admin-border);"
                             role="menu" aria-orientation="vertical" aria-labelledby="notification-button" tabindex="-1">
                            <div class="px-4 py-3 border-b" style="border-color: var(--admin-border);">
                                <p class="text-sm font-bold" style="color: var(--admin-text);">اعلانات جدید</p>
                            </div>
                            <div id="notification-list-container" class="py-1 max-h-72 overflow-y-auto" role="none">
                                <p id="loading-notifications-message" class="px-4 py-3 text-sm" style="color: var(--admin-text-dim);">در حال بارگذاری...</p>
                                <p id="no-notifications-message" class="px-4 py-3 text-sm hidden" style="color: var(--admin-text-dim);">اعلانی برای نمایش وجود ندارد.</p>
                            </div>
                            <div class="border-t" style="border-color: var(--admin-border);">
                                <a href="{{ route('admin.notifications.index') }}"
                                   class="w-full text-center block px-4 py-2.5 text-sm font-medium transition-colors"
                                   style="color: var(--admin-accent);"
                                   role="menuitem" tabindex="-1">
                                    مشاهده همه اعلانات
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- User menu -->
                    <div class="relative inline-block text-left">
                        <button type="button"
                                class="inline-flex items-center justify-center w-9 h-9 rounded-full font-medium text-sm focus:outline-none transition-colors"
                                style="background: var(--admin-accent-light); color: var(--admin-accent);"
                                id="user-menu-button" aria-expanded="false" aria-haspopup="true">
                            {{ mb_substr(optional(auth()->user())->name ?? 'کاربر', 0, 1) }}
                        </button>

                        <div id="user-menu-dropdown"
                             class="hidden absolute right-0 z-50 mt-2 w-56 rounded-xl shadow-lg focus:outline-none"
                             style="background: var(--admin-surface); border: 1px solid var(--admin-border);"
                             role="menu" aria-orientation="vertical" aria-labelledby="user-menu-button" tabindex="-1">
                            <div class="py-1" role="none">
                                <div class="px-4 py-3 border-b" style="border-color: var(--admin-border);">
                                    <p class="text-sm font-medium truncate" style="color: var(--admin-text);">{{ auth()->user()->name ?? 'کاربر' }}</p>
                                    <p class="text-xs truncate mt-0.5" style="color: var(--admin-text-dim);">{{ auth()->user()->email ?? '' }}</p>
                                </div>

                                <a href="{{ route('admin.profile.show') }}"
                                   class="flex items-center px-4 py-2.5 text-sm transition-colors"
                                   style="color: var(--admin-text-dim);"
                                   data-ui-hover="background-color: var(--admin-accent-light); color: var(--admin-text)"
                                   data-ui-unhover="background-color: ; color: var(--admin-text-dim)"
                                   role="menuitem" tabindex="-1">
                                    <svg class="w-4 h-4 ml-2 opacity-60" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zM8 12a6 6 0 00-5.43 3.59A8 8 0 0118 10a8 8 0 01-4.57 7.59A6 6 0 008 12z" clip-rule="evenodd" />
                                    </svg>
                                    نمایش پروفایل
                                </a>
                                <a href="{{ route('admin.profile.edit') }}" data-menu-key="profile" data-shortcut="g y"
                                   class="flex items-center px-4 py-2.5 text-sm transition-colors"
                                   style="color: var(--admin-text-dim);"
                                   data-ui-hover="background-color: var(--admin-accent-light); color: var(--admin-text)"
                                   data-ui-unhover="background-color: ; color: var(--admin-text-dim)"
                                   role="menuitem" tabindex="-1">
                                    <svg class="w-4 h-4 ml-2 opacity-60" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z" />
                                        <path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd" />
                                    </svg>
                                    ویرایش پروفایل
                                </a>

                                {{-- بسته‌ی ۲ ظاهری: «پشتیبانی» از منوی کناری به منوی حساب آمد --}}
                                @foreach ($adminAccountItems as $accountItem)
                                    @continue($accountItem['key'] === 'profile')
                                    <a href="{{ $accountItem['url'] }}" data-menu-key="{{ $accountItem['key'] }}" data-shortcut="{{ $accountItem['shortcut'] }}"
                                       class="flex items-center px-4 py-2.5 text-sm transition-colors"
                                       style="color: var(--admin-text-dim);"
                                       data-ui-hover="background-color: var(--admin-accent-light); color: var(--admin-text)"
                                       data-ui-unhover="background-color: ; color: var(--admin-text-dim)"
                                       role="menuitem" tabindex="-1">
                                        <svg class="w-4 h-4 ml-2 opacity-60" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $accountItem['icon'] !!}</svg>
                                        {{ $accountItem['label'] }}
                                    </a>
                                @endforeach

                                <a href="#" data-adm-shortcuts-open
                                   class="flex items-center px-4 py-2.5 text-sm transition-colors"
                                   style="color: var(--admin-text-dim);"
                                   data-ui-hover="background-color: var(--admin-accent-light); color: var(--admin-text)"
                                   data-ui-unhover="background-color: ; color: var(--admin-text-dim)"
                                   role="menuitem" tabindex="-1">
                                    <svg class="w-4 h-4 ml-2 opacity-60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><rect x="2" y="6" width="20" height="12" rx="2"/><path d="M6 10h.01M10 10h.01M14 10h.01M18 10h.01M7 14h10"/></svg>
                                    میانبرهای صفحه‌کلید <kbd class="mr-auto text-xs opacity-70">?</kbd>
                                </a>

                                {{-- سالن دوم (تصمیم ۵، آزمون ۲۰۲۶-۱۰-۲۲: مالک راهی برای فهمیدنش نداشت) — فقط مالک سالن جاری، نه هنگام ورود ابرمدیر به‌جای سالن --}}
                                @if (app(\App\Support\CurrentSalon::class)->id() !== null && auth()->user()->salonRoleIn(app(\App\Support\CurrentSalon::class)->id()) === 'owner' && ! \App\Services\SuperAdmin\SuperAdminImpersonation::active())
                                    <a href="{{ route('salon-signup.create') }}" data-add-salon
                                       class="flex items-center px-4 py-2.5 text-sm transition-colors"
                                       style="color: var(--admin-accent);"
                                       data-ui-hover="background-color: var(--admin-accent-light)"
                                       data-ui-unhover="background-color: "
                                       role="menuitem" tabindex="-1">
                                        <svg class="w-4 h-4 ml-2 opacity-70" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                                        </svg>
                                        افزودن سالن دیگر
                                    </a>
                                @endif

                                <form method="POST" action="{{ route('logout') }}" role="none" class="border-t" style="border-color: var(--admin-border);">
                                    @csrf
                                    <button type="submit"
                                            class="w-full text-right flex items-center px-4 py-2.5 text-sm transition-colors"
                                            style="color: rgb(var(--m-bad));"
                                            data-ui-hover="background-color: rgb(var(--m-bad) / .1)"
                                            data-ui-unhover="background-color: "
                                            role="menuitem" tabindex="-1">
                                        <svg class="w-4 h-4 ml-2 opacity-70" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m-3 0l3-3m0 0l-3-3m3 3H9" />
                                        </svg>
                                        خروج
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if ($adminShell === 'top')
                @include('layouts.admin-shell.top', ['menu' => $adminMenu])
            @endif
            <!-- Breadcrumb -->
            <div class="hidden md:flex px-4 py-2 border-t text-sm" style="background: var(--admin-accent-light); border-color: var(--admin-border);">
                <a href="{{ route('admin.dashboard') }}" class="transition-colors" style="color: var(--admin-accent);"
                   data-ui-hover="color: var(--admin-accent-hover)"
                   data-ui-unhover="color: var(--admin-accent)">پنل مدیریت</a>
                <span class="mx-2" style="color: var(--admin-text-light);">/</span>
                <span style="color: var(--admin-text-dim);">@yield('title')</span>
            </div>
        </header>
        @include('partials.platform-announcements', ['panel' => 'admins'])
        @include('partials.booking-paused-banner', ['panel' => 'admins'])
        {{-- بسته‌ی ۷: نوار احراز برداشته شد؛ احراز فقط پیش‌نیاز درگاه است و کارت خود صفحه‌ی درگاه‌ها همان را می‌گوید --}}
        @include('admin.terms-update._banner')

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto p-4" style="background: var(--admin-bg);">
            @if(session('success'))
                <div id="flash-success" class="mb-4 border-r-4 border-m-ok p-4 fade-in rounded-lg shadow-sm flex items-center justify-between"
                     style="background: rgb(var(--m-ok) / .1); color: rgb(var(--m-ok));" role="alert">
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-m-ok flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        <span class="mr-3">{{ session('success') }}</span>
                    </div>
                    <button data-ui-remove="#flash-success" class="mr-2 opacity-60 hover:opacity-100 transition-opacity">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <script @nonce>
                    setTimeout(function() {
                        const el = document.getElementById('flash-success');
                        if (el) { el.style.transition = 'opacity 0.5s'; el.style.opacity = '0'; setTimeout(() => el.remove(), 500); }
                    }, 4000);
                </script>
            @endif

            {{-- اطلاع (۲۰۲۶-۱۰-۳۱): with('info') در پنل مدیر جایی نشان داده نمی‌شد (مثلاً «این قسط قبلاً پرداخت و ثبت شده است») --}}
            @if(session('info'))
                <div id="flash-info" class="mb-4 border-r-4 p-4 fade-in rounded-lg shadow-sm" role="status"
                     style="border-color: var(--admin-accent); background: var(--admin-accent-light); color: var(--admin-text);">
                    {{ session('info') }}
                </div>
            @endif

            {{-- بسته‌ی 5ه: هشدار بی‌مانع (مثلاً «این مشتری امروز نوبت دارد») — خودکار بسته نمی‌شود --}}
            @if(session('warning'))
                <div id="flash-warning" class="mb-4 border-r-4 p-4 fade-in rounded-lg shadow-sm" role="alert"
                     style="border-color: rgb(var(--m-warn)); background: rgb(var(--m-warn) / .12); color: rgb(var(--m-warn));">
                    {{ session('warning') }}
                </div>
            @endif

            @if(session('error'))
                <div id="flash-error" class="mb-4 border-r-4 border-m-bad p-4 fade-in rounded-lg shadow-sm flex items-center justify-between"
                     style="background: rgb(var(--m-bad) / .1); color: rgb(var(--m-bad));" role="alert">
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-m-bad flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                        <span class="mr-3">{{ session('error') }}</span>
                    </div>
                    <button data-ui-remove="#flash-error" class="mr-2 opacity-60 hover:opacity-100 transition-opacity">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <script @nonce>
                    setTimeout(function() {
                        const el = document.getElementById('flash-error');
                        if (el) { el.style.transition = 'opacity 0.5s'; el.style.opacity = '0'; setTimeout(() => el.remove(), 500); }
                    }, 5000);
                </script>
            @endif

            <div class="fade-in">
                @yield('content')
            </div>
        </main>

        <footer class="py-3 px-4 text-center text-xs border-t"
                style="background: var(--admin-surface); border-color: var(--admin-border); color: var(--admin-text-light);">
            <p>© {{ fa_jdate(now(), 'Y') }} سیستم مدیریت سالن زیبایی. تمامی حقوق محفوظ است.</p>
        </footer>
    </div>
</div>

<!-- Search modal -->
<div id="search-modal"
     class="fixed inset-0 z-[100] hidden flex items-start justify-center pt-16"
     style="background: rgba(10, 8, 6, 0.55);"
     aria-modal="true" role="dialog"
     data-ui-click="closeSearchModalIfClickOutside">
    <div class="rounded-xl shadow-xl w-full max-w-lg mx-4 overflow-hidden fade-in"
         style="background: var(--admin-surface);"
         role="document"
         id="search-modal-content">
        <div class="p-5">
            <h3 class="text-base font-semibold border-b pb-3 mb-4" style="color: var(--admin-text); border-color: var(--admin-border);">جستجوی سریع در پنل</h3>
            <form action="{{ route('admin.search.index') }}" method="GET">
                <div class="relative">
                    <input type="search" name="q" placeholder="جستجو بین خدمات، کارشناسان، کاربران..."
                           class="w-full py-2.5 px-4 pr-10 text-sm rounded-lg outline-none transition"
                           style="border: 1px solid var(--admin-border); background: var(--admin-bg); color: var(--admin-text);"
                           data-ui-focus="border-color: var(--admin-accent)"
                           data-ui-blur="border-color: var(--admin-border)"
                           autofocus>
                    <div class="absolute right-3 top-0 bottom-0 flex items-center pointer-events-none" style="color: var(--admin-text-light);">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                    </div>
                </div>
                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" data-ui-click="toggleSearchModal"
                            class="px-4 py-2 text-sm font-medium rounded-lg transition-colors"
                            style="background: var(--admin-accent-light); color: var(--admin-text-dim);"
                            data-ui-hover="background: var(--admin-border)"
                            data-ui-unhover="background: var(--admin-accent-light)">
                        انصراف
                    </button>
                    <button type="submit"
                            class="admin-btn-primary px-4 py-2 text-sm font-medium rounded-lg">
                        جستجو
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .fade-in-up {
        animation: fadeInUp 0.3s ease-out;
    }

    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<script @nonce>
    // امنیت (H2): متن آزاد کاربر (نام مشتری، نام خدمت، متن اعلان) پیش از innerHTML escape می‌شود
    function escHtml(v) { return String(v ?? '').replace(/[&<>"']/g, (c) => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c])); }
    window.AdminRoutes = {
        notificationsIndex: '{{ route("admin.notifications.index") }}',
        notificationsLatest: '{{ route("admin.notifications.latest") }}',
        notificationsCount: '{{ route("admin.notifications.count") }}',
        // «:id» مستقیم در route؛ قبلاً «000» جایگزین می‌شد و اولین «000» پورت 8000 بود ← آدرس خراب و اعلان خوانده نمی‌شد (بسته‌ی «ح»، ۶)
        notificationsRead: '{{ route("admin.notifications.read", ":id") }}',
        csrfToken: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
    };

    function fetchUnreadCount() {
        fetch(window.AdminRoutes.notificationsCount, {
            // بدون این دو هدر، اگر نشست تمام شده باشد لاراول آدرس همین درخواست را «مقصد بعد از ورود» ذخیره می‌کرد
            // و ورود بعدی (حتی مشتری در صفحه‌ی سالن) به admin/notifications/count با خطای ۴۰۳ می‌رفت.
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(response => response.ok ? response.json() : Promise.reject(new Error('HTTP ' + response.status)))
            .then(data => {
                const countElement = document.getElementById('notification-count');
                // همه‌ی شمارنده‌های منو (کشو، ریل، منوی بالایی) — بسته‌ی «و»
                const menuBadges = document.querySelectorAll('[data-notif-badge]');
                const count = data.count;

                if (count > 0) {
                    const label = String(count > 99 ? '99+' : count).replace(/\d/g, d => '۰۱۲۳۴۵۶۷۸۹'[d]);
                    countElement.textContent = label;
                    countElement.classList.remove('hidden');
                    countElement.classList.add('flex');
                    menuBadges.forEach(function (badge) {
                        badge.textContent = label;
                        badge.classList.remove('hidden');
                    });
                } else {
                    countElement.classList.add('hidden');
                    countElement.classList.remove('flex');
                    menuBadges.forEach(function (badge) { badge.classList.add('hidden'); });
                }
            })
            .catch(error => console.error('Error fetching notification count:', error));
    }

    // ⭐ The admin/notifications/index.blade.php and show.blade.php pages already called
    // window.refreshNotificationCount() after each operation (read/toggle) to update the header counter badge without requiring a full page refresh — but this
    // function was not defined anywhere in the project, so the badge update only happened on a full page refresh (or the 60-second timer
    // below). It is now connected to fetchUnreadCount with an alias.
    window.refreshNotificationCount = fetchUnreadCount;

    // ⭐ The same pages also used a localStorage.setItem('notification_updated', ...)
    // (presumably to coordinate between multiple open browser tabs) but no listener was defined for this
    // key — this section was also completed so that if the admin has multiple open tabs,
    // marking a tab will update the badge in other tabs as well.
    window.addEventListener('storage', function (e) {
        if (e.key === 'notification_updated') fetchUnreadCount();
    });

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

        fetch(window.AdminRoutes.notificationsLatest, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(data => {
                loadingMessage.classList.add('hidden');

                if (data.notifications && data.notifications.length > 0) {
                    data.notifications.forEach(notification => {
                        const link = document.createElement('a');
                        link.href = notification.link;
                        link.classList.add('flex', 'flex-col', 'items-start', 'px-4', 'py-2.5', 'text-sm', 'border-b', 'transition-colors');
                        link.style.borderColor = 'var(--admin-border)';
                        link.style.color = 'var(--admin-text-dim)';
                        link.setAttribute('role', 'menuitem');
                        link.setAttribute('tabindex', '-1');

                        if (!notification.read_at) {
                            link.classList.add('notification-item-unread');
                            link.style.fontWeight = '600';
                        }

                        link.addEventListener('mouseover', () => {
                            if (!notification.read_at) return;
                            link.style.backgroundColor = 'var(--admin-accent-light)';
                        });
                        link.addEventListener('mouseout', () => {
                            if (!notification.read_at) return;
                            link.style.backgroundColor = '';
                        });

                        link.innerHTML = `
                            <span class="truncate w-full" style="color: var(--admin-text);">${escHtml(notification.message)}</span>
                            <span class="text-xs mt-0.5" style="color: ${notification.read_at ? 'var(--admin-text-light)' : 'var(--admin-accent)'};">${escHtml(notification.time_ago)}</span>
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
                console.error('Error fetching latest notifications:', error);
            });
    }

    function markNotificationAsRead(id, redirectLink) {
        const url = window.AdminRoutes.notificationsRead.replace(':id', id);

        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': window.AdminRoutes.csrfToken,
                'Content-Type': 'application/json'
            }
        })
            .then(response => {
                if (response.ok) {
                    fetchUnreadCount();
                    window.location.href = redirectLink;
                } else {
                    window.location.href = redirectLink;
                }
            })
            .catch(() => {
                window.location.href = redirectLink;
            });
    }

    function toggleSidebar() {
        const sidebar = document.getElementById('admin-drawer');
        const backdrop = document.getElementById('sidebar-backdrop');
        if (sidebar) sidebar.classList.toggle('hidden');
        if (backdrop) backdrop.classList.toggle('hidden');
    }

    function toggleSearchModal() {
        const modal = document.getElementById('search-modal');
        if (modal) {
            modal.classList.toggle('hidden');
            if (!modal.classList.contains('hidden')) {
                const searchInput = modal.querySelector('input[name="q"]');
                if (searchInput) setTimeout(() => searchInput.focus(), 100);
            }
        }
    }

    function closeSearchModalIfClickOutside(event) {
        const modal = document.getElementById('search-modal');
        const content = document.getElementById('search-modal-content');
        if (modal && content && event.target === modal) toggleSearchModal();
    }

    // H3 مرحله‌ی ۲: دکمه‌های data-ui-click (partials/ui-handlers)
    uiActions.register({
        toggleSidebar: function () { toggleSidebar(); },
        toggleSearchModal: function () { toggleSearchModal(); },
        closeSearchModalIfClickOutside: function (event) { closeSearchModalIfClickOutside(event); },
    });

    document.addEventListener('DOMContentLoaded', function() {
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
                const notificationDropdown = document.getElementById('notification-dropdown');
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

        // SweetAlert2 — Confirm deletion
        const deleteButtons = document.querySelectorAll('[data-confirm-delete]');
        deleteButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                if (button.disabled) return;
                const message = button.getAttribute('data-confirm-message') || 'آیا از حذف این آیتم اطمینان دارید؟';

                Swal.fire({
                    title: 'هشدار',
                    text: message,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#334155',
                    cancelButtonColor: '#94A3B8',
                    confirmButtonText: 'بله، حذف شود',
                    cancelButtonText: 'انصراف'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = button.closest('form');
                        if (form && !form.dataset.submitted) {
                            form.dataset.submitted = '1';
                            button.disabled = true;
                            form.submit();
                        }
                    }
                });
            });
        });

        // SweetAlert2 — Confirm sensitive but non-destructive actions (e.g. manual settlement)
        const confirmActionButtons = document.querySelectorAll('[data-confirm-action]');
        confirmActionButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                if (button.disabled) return;
                const message = button.getAttribute('data-confirm-message') || 'آیا از انجام این عملیات اطمینان دارید؟';
                const confirmText = button.getAttribute('data-confirm-text') || 'بله، انجام شود';

                Swal.fire({
                    title: 'تایید عملیات',
                    text: message,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#16A34A',
                    cancelButtonColor: '#94A3B8',
                    confirmButtonText: confirmText,
                    cancelButtonText: 'انصراف'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = button.closest('form');
                        if (form && !form.dataset.submitted) {
                            form.dataset.submitted = '1';
                            button.disabled = true;
                            form.submit();
                        }
                    }
                });
            });
        });

        // بسته‌ی «و»: منوی بالایی — با باز شدن یک گروه بقیه بسته شوند؛ کلیک بیرون همه را می‌بندد
        const topGroups = document.querySelectorAll('[data-adm-top-group]');
        topGroups.forEach(function (group) {
            group.addEventListener('toggle', function () {
                if (group.open) topGroups.forEach(function (other) { if (other !== group) other.open = false; });
            });
        });
        document.addEventListener('click', function (event) {
            topGroups.forEach(function (group) { if (group.open && !group.contains(event.target)) group.open = false; });
        });
        document.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape') return;
            topGroups.forEach(function (group) {
                if (group.open) { group.open = false; group.querySelector('summary').focus({ preventScroll: true }); }
            });
        });

        // Ctrl+K / ⌘K: جستجوی سریع پنل
        document.addEventListener('keydown', function (e) {
            // e.code تا روی صفحه‌کلید فارسی هم کار کند (بسته‌ی ۲ب)
            if ((e.ctrlKey || e.metaKey) && (e.code === 'KeyK' || e.key === 'k' || e.key === 'K')) {
                e.preventDefault();
                toggleSearchModal();
            }
        });

        // Close the search modal with Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const modal = document.getElementById('search-modal');
                if (modal && !modal.classList.contains('hidden')) toggleSearchModal();
            }
        });
    });
</script>

@include('layouts.admin-shell.shortcuts', ['menuAll' => $adminMenuAll, 'shell' => $adminShell])
<script @nonce>
    // منوی «⋯» ردیف‌ها (بسته‌ی ۲ ظاهری): پنل fixed کنار دکمه (ظرف اسکرول جدول نبُرد)، فقط یکی باز، بستن با کلیک بیرون/Escape
    (function () {
        var menus = document.querySelectorAll('[data-adm-menu-root]');
        if (!menus.length) return;
        function closeAll(except) { menus.forEach(function (m) { if (m !== except) m.open = false; }); }
        function place(menu) {
            var r = menu.querySelector('summary').getBoundingClientRect(), panel = menu.querySelector('.adm-menu__panel');
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
        document.addEventListener('click', function (e) { if (!e.target.closest('[data-adm-menu-root]')) closeAll(null); });
        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Escape') return;
            var open = document.querySelector('[data-adm-menu-root][open]');
            if (open) { open.open = false; open.querySelector('summary').focus(); }
        });
        function follow() {
            var open = document.querySelector('[data-adm-menu-root][open]');
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
