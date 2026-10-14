<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>انصراف از پیامک‌ها — {{ $salon->name }}</title>
    <style>
        body { margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center; background:#1A1410; color:#F8F3E9; font-family:Vazirmatn, Tahoma, sans-serif; line-height:1.9; }
        .box { max-width:26rem; text-align:center; padding:2rem 1.5rem; }
        h1 { font-size:1.25rem; margin:.25rem 0 .75rem; color:#E6CD8A; }
        p { margin:.5rem 0; color:rgba(248,243,233,.85); }
        button { margin-top:1.25rem; padding:.85rem 1.75rem; border:0; border-radius:.75rem; background:#C9A24B; color:#1A1410; font:inherit; font-weight:700; cursor:pointer; }
        a { color:#E6CD8A; }
        .hint { font-size:.85rem; color:rgba(248,243,233,.55); margin-top:1rem; }
    </style>
</head>
<body>
    {{-- انصراف از پیامک‌های پیگیری سالن (۲۰۲۶-۱۰-۱۴). پیامک‌های نوبت خودِ مشتری (تأیید، یادآوری) قطع نمی‌شوند. --}}
    <div class="box">
        @if ($done)
            <h1>انصراف ثبت شد</h1>
            <p>دیگر پیامک پیگیری و پیشنهاد از {{ $salon->name }} دریافت نمی‌کنید. پیامک‌های تأیید و یادآوری نوبت‌هایتان مثل قبل می‌رسد.</p>
            <p class="hint">برای دریافت دوباره، از بخش «ویرایش پروفایل» در صفحه‌ی سالن روشنش کنید. <a href="{{ $salon->publicUrl() }}">صفحه‌ی سالن</a></p>
        @else
            <h1>انصراف از پیامک‌های {{ $salon->name }}</h1>
            <p>با زدن دکمه‌ی زیر، پیامک‌های پیگیری و پیشنهاد این سالن برای شما فرستاده نمی‌شود. پیامک‌های تأیید و یادآوری نوبت‌هایتان قطع نمی‌شود.</p>
            <form method="post" action="{{ $action }}">
                @csrf
                <button type="submit">دیگر پیامک نفرست</button>
            </form>
        @endif
    </div>
</body>
</html>
