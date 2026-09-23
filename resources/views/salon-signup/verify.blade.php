<!DOCTYPE html>
<html lang="fa" dir="rtl" class="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>تایید شماره موبایل — {{ config('app.name', 'راستا') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --rasta-gold: #C9A24B;
            --rasta-gold-light: #E6CD8A;
            --rasta-cream: #F8F3E9;
            --rasta-dark: #1A1410;
            --rasta-brown: #2E2117;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--rasta-dark);
            color: var(--rasta-cream);
            font-family: 'Vazirmatn', Tahoma, sans-serif;
            padding: 2rem 1rem;
        }
        .card {
            background-color: var(--rasta-brown);
            border: 1px solid rgba(201, 162, 75, 0.15);
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
            background-color: rgba(248, 243, 233, 0.04);
            border: 1px solid rgba(201, 162, 75, 0.25);
            color: var(--rasta-cream);
            border-radius: .5rem;
            padding: .7rem;
            margin: 1.25rem 0;
        }
        input:focus { outline: none; border-color: var(--rasta-gold); box-shadow: 0 0 0 3px rgba(201, 162, 75, 0.2); }
        button {
            width: 100%;
            background: linear-gradient(135deg, var(--rasta-gold-light), var(--rasta-gold));
            color: var(--rasta-dark);
            border: none;
            border-radius: .6rem;
            padding: .8rem;
            font-size: 1rem;
            font-weight: bold;
            font-family: inherit;
            cursor: pointer;
        }
        .flash-err, .flash-ok {
            padding: .7rem 1rem; border-radius: .4rem; margin-bottom: 1rem; font-size: .85rem;
        }
        .flash-err { background: rgba(220,38,38,.15); border-right: 4px solid #dc2626; }
        .flash-ok { background: rgba(34,197,94,.15); border-right: 4px solid #22c55e; }
        form.resend { margin-top: 1.25rem; }
        form.resend button {
            background: none; color: var(--rasta-gold-light);
            text-decoration: underline; font-weight: normal; font-size: .85rem;
        }
    </style>
</head>
<body>
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
