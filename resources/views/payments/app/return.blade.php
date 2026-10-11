<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $title }} — {{ $salonName }}</title>
    <style>
        body { margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center; background:#1A1410; color:#F8F3E9; font-family:Vazirmatn, Tahoma, sans-serif; line-height:1.9; }
        .box { max-width:26rem; text-align:center; padding:2rem 1.5rem; }
        .icon { font-size:2.75rem; margin-bottom:.5rem; color:{{ $state === 'paid' ? '#7FCB97' : ($state === 'pending' ? '#E6CD8A' : '#E8907E') }}; }
        h1 { font-size:1.25rem; margin:.25rem 0 .75rem; color:#E6CD8A; }
        p { margin:.5rem 0; color:rgba(248,243,233,.85); }
        a.btn { display:inline-block; margin-top:1.25rem; padding:.85rem 1.75rem; border-radius:.75rem; background:#C9A24B; color:#1A1410; font-weight:700; text-decoration:none; }
        .hint { font-size:.85rem; color:rgba(248,243,233,.55); margin-top:1rem; }
    </style>
</head>
<body>
    {{-- بازگشت از درگاه به اپ «ماهرو» (بسته‌ی ۳). اپ به پارامترهای لینک اعتماد نمی‌کند و وضعیت را از API می‌پرسد. --}}
    <div class="box">
        <div class="icon" aria-hidden="true">{{ $state === 'paid' ? '✓' : ($state === 'pending' ? '…' : '!') }}</div>
        <h1>{{ $title }}</h1>
        <p>{{ $message }}</p>
        <a class="btn" href="{{ $deepLink }}">بازگشت به اپ ماهرو</a>
        <p class="hint">اگر اپ خودکار باز نشد، دکمه‌ی بالا را بزنید. می‌توانید این صفحه را ببندید.</p>
    </div>
    <script>setTimeout(function () { window.location.href = @json($deepLink); }, 600);</script>
</body>
</html>
