<!DOCTYPE html>
<html lang="fa" dir="rtl" class="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>ثبت‌نام سالن جدید — {{ config('app.name', 'راستا') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Naskh+Arabic:wght@500;700&display=swap" rel="stylesheet">

    {{--
        ⭐ عمداً یک صفحه‌ی کاملاً self-contained (بدون @vite، بدون x-guest-layout) — دقیقاً
        هم‌الگو با resources/views/central/placeholder.blade.php. x-guest-layout به route('home')
        و $currentSalonName وابسته‌ست که هر دو فقط داخل یک درخواست /s/{slug} معنی دارن؛ این صفحه
        global است (بیرون از هر سالن)، پس اون layout اینجا کرش می‌کنه.
    --}}
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
            background-color: var(--rasta-dark);
            color: var(--rasta-cream);
            font-family: 'Noto Naskh Arabic', 'Vazirmatn', serif;
            padding: 2.5rem 1rem;
        }
        .wrap { max-width: 40rem; margin: 0 auto; }
        h1 { color: var(--rasta-gold-light); font-size: 1.6rem; text-align: center; margin-bottom: .5rem; }
        .sub { text-align: center; opacity: .75; margin-bottom: 2rem; line-height: 1.8; }
        .card {
            background-color: var(--rasta-brown);
            border: 1px solid rgba(201, 162, 75, 0.15);
            border-radius: 1rem;
            padding: 1.75rem;
        }
        fieldset { border: none; padding: 0; margin: 0 0 1.5rem; }
        legend { color: var(--rasta-gold); font-weight: bold; margin-bottom: .75rem; padding: 0; }
        label { display: block; font-size: .875rem; margin-bottom: .35rem; opacity: .9; }
        .row { margin-bottom: 1rem; }
        input[type=text], input[type=password], input[type=tel] {
            width: 100%;
            background-color: rgba(248, 243, 233, 0.04);
            border: 1px solid rgba(201, 162, 75, 0.25);
            color: var(--rasta-cream);
            border-radius: .5rem;
            padding: .6rem .75rem;
            font-size: .95rem;
        }
        input:focus { outline: none; border-color: var(--rasta-gold); box-shadow: 0 0 0 3px rgba(201, 162, 75, 0.2); }
        .plans { display: grid; grid-template-columns: repeat(2, 1fr); gap: .6rem; }
        .plan {
            border: 1px solid rgba(201, 162, 75, 0.25);
            border-radius: .6rem;
            padding: .7rem;
            cursor: pointer;
            position: relative;
        }
        .plan input { position: absolute; top: .6rem; left: .6rem; }
        .plan .title { font-weight: bold; color: var(--rasta-gold-light); }
        .plan .price { font-size: .85rem; opacity: .8; margin-top: .2rem; }
        .err { color: #ff8a8a; font-size: .8rem; margin-top: .3rem; }
        .flash-err {
            background: rgba(220,38,38,.15); border-right: 4px solid #dc2626;
            padding: .75rem 1rem; border-radius: .4rem; margin-bottom: 1.25rem; font-size: .875rem;
        }
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
    </style>
</head>
<body>
    <div class="wrap">
        <h1>ساخت سالن خودتان روی راستا</h1>
        <p class="sub">در چند دقیقه، پنل مدیریت اختصاصی سالن خودتان را راه‌اندازی کنید.</p>

        <div class="card">
            @if ($errors->any())
                <div class="flash-err">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('salon-signup.store') }}">
                @csrf

                <fieldset>
                    <legend>مشخصات سالن</legend>
                    <div class="row">
                        <label for="name">نام سالن</label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" required maxlength="255">
                    </div>
                    <div class="row">
                        <label for="slug">آدرس اختصاصی سالن (بعداً غیرقابل‌تغییر)</label>
                        <input type="text" id="slug" name="slug" value="{{ old('slug') }}" required
                               maxlength="100" pattern="[a-zA-Z0-9_-]+" placeholder="مثلاً: almas-beauty" dir="ltr">
                    </div>
                </fieldset>

                <fieldset>
                    <legend>پلن اشتراک</legend>
                    <div class="plans">
                        @foreach (['1m' => '۱ ماهه', '3m' => '۳ ماهه', '6m' => '۶ ماهه', '12m' => '۱۲ ماهه'] as $type => $label)
                            <label class="plan">
                                <input type="radio" name="subscription_type" value="{{ $type }}"
                                       @checked(old('subscription_type', '1m') === $type) required>
                                <div class="title">{{ $label }}</div>
                                <div class="price">{{ number_format($prices[$type]) }} تومان</div>
                            </label>
                        @endforeach
                    </div>
                    <p class="sub" style="margin:.75rem 0 0; text-align:right; font-size:.8rem;">
                        همین حالا نیازی به پرداخت نیست — بعد از تایید شماره موبایل، از پنل ادمین پرداخت می‌کنید.
                    </p>
                </fieldset>

                <fieldset>
                    <legend>مشخصات مدیر سالن (شما)</legend>
                    <div class="row">
                        <label for="owner_name">نام و نام خانوادگی</label>
                        <input type="text" id="owner_name" name="owner_name" value="{{ old('owner_name') }}" required maxlength="255">
                    </div>
                    <div class="row">
                        <label for="owner_phone">شماره موبایل</label>
                        <input type="tel" id="owner_phone" name="owner_phone" value="{{ old('owner_phone') }}"
                               required maxlength="11" dir="ltr" placeholder="09xxxxxxxxx">
                    </div>
                    <div class="row">
                        <label for="owner_password">رمز عبور</label>
                        <input type="password" id="owner_password" name="owner_password" required minlength="8">
                    </div>
                    <div class="row">
                        <label for="owner_password_confirmation">تکرار رمز عبور</label>
                        <input type="password" id="owner_password_confirmation" name="owner_password_confirmation" required minlength="8">
                    </div>
                </fieldset>

                <button type="submit">ادامه و ارسال کد تایید</button>
            </form>
        </div>
    </div>
</body>
</html>
