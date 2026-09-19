<!DOCTYPE html>
<html lang="fa" dir="rtl" class="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'راستا') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Naskh+Arabic:wght@500;700&display=swap" rel="stylesheet">

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
            font-family: 'Noto Naskh Arabic', 'Vazirmatn', serif;
            text-align: center;
            padding: 2rem;
        }

        .card {
            max-width: 32rem;
        }

        h1 {
            color: var(--rasta-gold-light);
            font-size: 1.75rem;
            margin-bottom: 0.75rem;
        }

        p {
            color: var(--rasta-cream);
            opacity: 0.85;
            line-height: 1.9;
            font-size: 1rem;
        }

        .badge {
            display: inline-block;
            margin-top: 1.5rem;
            padding: 0.4rem 1rem;
            border: 1px solid var(--rasta-gold);
            border-radius: 999px;
            color: var(--rasta-gold-light);
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>{{ config('app.name', 'راستا') }}</h1>
        <p>
            این دامنه‌ی مرکزی سیستم مدیریت سالن‌های زیبایی راستاست.
            برای ورود به پنل سالن خودتان، از آدرس اختصاصی سالن (ساب‌دامین) استفاده کنید.
        </p>
        <span class="badge">به‌زودی: ثبت‌نام آنلاین سالن جدید</span>
    </div>
</body>
</html>
