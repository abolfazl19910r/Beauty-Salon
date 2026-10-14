<!DOCTYPE html>
<html lang="fa" dir="rtl" class="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>تایید شماره موبایل — {{ config('brand.name') }}</title>
    @include('partials.platform-icons')


    @include('partials.mahru-theme')
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--rasta-dark);
            color: var(--rasta-cream);
            font-family: "Mahru", 'Vazirmatn', Tahoma, sans-serif;
            padding: 2rem 1rem;
        }
        .card {
            background-color: var(--rasta-brown);
            border: 1px solid rgb(var(--m-gold) / 0.15);
            border-radius: 1rem;
            padding: 2rem;
            max-width: 26rem;
            width: 100%;
            text-align: center;
        }
        h1 { color: var(--rasta-gold-light); font-size: 1.3rem; margin-bottom: .5rem; }
        p { opacity: .8; line-height: 1.9; font-size: .9rem; }
        input[type=text] {
            width: 100%;
            text-align: center;
            letter-spacing: .5rem;
            font-size: 1.4rem;
            background-color: rgb(var(--m-tx) / 0.04);
            border: 1px solid rgb(var(--m-gold) / 0.25);
            color: var(--rasta-cream);
            border-radius: .5rem;
            padding: .7rem;
            margin: 1.25rem 0;
        }
        input:focus { outline: none; border-color: var(--rasta-gold); box-shadow: 0 0 0 3px rgb(var(--m-gold) / 0.2); }
        button {
            width: 100%;
            background: var(--m-btn);
            color: var(--m-on-gold);
            border: none;
            border-radius: 999px;
            padding: .8rem;
            font-size: 1rem;
            font-weight: bold;
            font-family: inherit;
            cursor: pointer;
        }
        .flash-err, .flash-ok {
            padding: .7rem 1rem; border-radius: .4rem; margin-bottom: 1rem; font-size: .85rem;
        }
        .flash-err { background: rgb(var(--m-bad) / .12); border-right: 4px solid rgb(var(--m-bad)); }
        .flash-ok { background: rgb(var(--m-ok) / .12); border-right: 4px solid rgb(var(--m-ok)); }
        form.resend { margin-top: 1.25rem; }
        form.resend button {
            background: none; color: var(--rasta-gold-light);
            text-decoration: underline; font-weight: normal; font-size: .85rem;
        }
    </style>
</head>
<body>
    <div style="position:fixed;top:1rem;left:1rem;z-index:50">@include('partials.mahru-theme-toggle')</div>
    <div class="card">
        <h1>تایید شماره موبایل</h1>
        <p>
            کد ۶ رقمی ارسال‌شده به شماره‌ی <strong dir="ltr">{{ $owner->phone }}</strong>
            را برای فعال‌سازی سالن «{{ $salon->name }}» وارد کنید.
        </p>

        @if (session('success'))
            <div class="flash-ok">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="flash-err">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('salon-signup.verify.store') }}">
            @csrf
            <input type="text" name="code" inputmode="numeric" maxlength="6" required autofocus dir="ltr" placeholder="——————">
            <button type="submit">تایید و ورود به پنل</button>
        </form>

        <form class="resend" method="POST" action="{{ route('salon-signup.resend-code') }}">
            @csrf
            <button type="submit">ارسال مجدد کد</button>
        </form>
    </div>
</body>
</html>
