<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $title }} — {{ $salonName }}</title>
    @include('partials.mahru-theme')
    <style>
        body { margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center; background:rgb(var(--m-bg)); color:rgb(var(--m-tx)); font-family:"Mahru", Vazirmatn, Tahoma, sans-serif; line-height:1.9; }
        .box { max-width:26rem; text-align:center; padding:2rem 1.5rem; }
        .icon { font-size:2.75rem; margin-bottom:.5rem; color:{{ $state === 'paid' ? 'rgb(var(--m-ok))' : ($state === 'pending' ? 'rgb(var(--m-gold2))' : 'rgb(var(--m-bad))') }}; }
        h1 { font-size:1.25rem; margin:.25rem 0 .75rem; color:rgb(var(--m-gold2)); }
        p { margin:.5rem 0; color:rgb(var(--m-tx) / .85); }
        a.btn { display:inline-block; margin-top:1.25rem; padding:.85rem 1.75rem; border-radius:999px; background:var(--m-btn); color:var(--m-on-gold); font-weight:700; text-decoration:none; }
        .hint { font-size:.85rem; color:rgb(var(--m-tx) / .55); margin-top:1rem; }
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
