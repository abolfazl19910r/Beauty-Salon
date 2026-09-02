<!DOCTYPE html>
<html lang="fa" dir="rtl" class="rtl">
<head>
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazir-font@v30.1.0/dist/font-face.css" rel="stylesheet" type="text/css" />
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>پنل مدیریت کل | @yield('title')</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    @vite(['resources/css/app.css'])
    @stack('styles')
    <style>
        :root {
            /* ⭐ Design tokens from Rasta_unified_prompt.md's SaaS section — deliberately dark +
               gold, distinct from the admin panel's light theme, so nobody mistakes which panel
               they're in. */
            --sa-bg: #0F172A;
            --sa-surface: #1E293B;
            --sa-border: #334155;
            --sa-text: #F1F5F9;
            --sa-text-dim: #94A3B8;
            --sa-accent: #C9A24B;
            --sa-accent-hover: #E6CD8A;
            --sa-danger: #EF4444;
            --sa-success: #22C55E;
        }

        body { background: var(--sa-bg); color: var(--sa-text); font-family: 'Vazir', sans-serif; }

        .sa-card { background: var(--sa-surface); border: 1px solid var(--sa-border); border-radius: 0.75rem; }
        .sa-input {
            background: var(--sa-bg); border: 1px solid var(--sa-border); color: var(--sa-text);
            border-radius: 0.5rem; padding: 0.6rem 0.9rem; width: 100%;
        }
        .sa-input:focus { outline: none; border-color: var(--sa-accent); }
        .sa-label { color: var(--sa-text-dim); font-size: 0.85rem; margin-bottom: 0.3rem; display: block; }
        .sa-btn {
            background: var(--sa-accent); color: #0F172A; font-weight: 600; padding: 0.6rem 1.5rem;
            border-radius: 0.5rem; transition: background 0.15s; display: inline-block;
        }
        .sa-btn:hover { background: var(--sa-accent-hover); }
        .sa-nav-link { color: var(--sa-text-dim); padding: 0.7rem 1rem; display: block; border-right: 3px solid transparent; }
        .sa-nav-link:hover, .sa-nav-link.active { color: var(--sa-text); border-right-color: var(--sa-accent); background: rgba(201,162,75,0.08); }
    </style>
</head>
<body>
    <div class="flex min-h-screen">
        <aside class="w-64 flex-shrink-0 hidden md:block" style="background: var(--sa-surface); border-left: 1px solid var(--sa-border);">
            <div class="p-5 border-b" style="border-color: var(--sa-border);">
                <div class="text-lg font-bold" style="color: var(--sa-accent);">پنل مدیریت کل</div>
                <div class="text-xs" style="color: var(--sa-text-dim);">راستا SaaS</div>
            </div>
            <nav class="p-3">
                <a href="{{ route('superadmin.dashboard') }}" class="sa-nav-link {{ request()->routeIs('superadmin.dashboard') ? 'active' : '' }}">داشبورد</a>
                <a href="{{ route('superadmin.salons.index') }}" class="sa-nav-link {{ request()->routeIs('superadmin.salons.*') ? 'active' : '' }}">مدیریت سالن‌ها</a>
            </nav>
        </aside>

        <div class="flex-1 flex flex-col">
            <header class="flex items-center justify-between px-6 py-4" style="background: var(--sa-surface); border-bottom: 1px solid var(--sa-border);">
                <h1 class="text-lg font-bold">@yield('title')</h1>
                <div class="flex items-center gap-4">
                    <span class="text-sm" style="color: var(--sa-text-dim);">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-sm" style="color: var(--sa-danger);">خروج</button>
                    </form>
                </div>
            </header>

            <main class="flex-1 p-6">
                @if (session('success'))
                    <div class="mb-4 p-4 rounded-lg" style="background: rgba(34,197,94,0.1); border: 1px solid var(--sa-success); color: var(--sa-success);">
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('error') || $errors->any())
                    <div class="mb-4 p-4 rounded-lg" style="background: rgba(239,68,68,0.1); border: 1px solid var(--sa-danger); color: #FCA5A5;">
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
</body>
</html>
