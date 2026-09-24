<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>در حال انتقال به درگاه پرداخت…</title>
    <style>
        body { margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center; background:#1A1410; color:#F8F3E9; font-family:Vazirmatn, Tahoma, sans-serif; }
        .box { text-align:center; padding:2rem; }
        button { margin-top:1rem; padding:.75rem 1.5rem; border:0; border-radius:.75rem; background:#C9A24B; color:#1A1410; font-weight:700; cursor:pointer; }
    </style>
</head>
<body>
    {{-- ⭐ App\Payments\GatewayRedirect — فرم POST خودکار برای درگاه‌هایی که توکن را فقط با POST می‌پذیرند. --}}
    <div class="box">
        <p>در حال انتقال به درگاه امن پرداخت…</p>
        <form id="gateway-form" method="POST" action="{{ $url }}">
            @foreach ($fields as $name => $value)
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            @endforeach
            <noscript><p>اگر منتقل نشدید، دکمه‌ی زیر را بزنید.</p></noscript>
            <button type="submit">ورود به درگاه پرداخت</button>
        </form>
    </div>
    <script>document.getElementById('gateway-form').submit();</script>
</body>
</html>
