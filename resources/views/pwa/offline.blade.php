{{-- صفحه‌ی «اینترنت قطع است» سایت سالن/پنل متخصص نصب‌شده (بسته‌ی ۶) — service worker آن را نگه می‌دارد؛ بدون فایل بیرونی --}}
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>اتصال برقرار نیست</title>
    <style>
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #15110E; color: #F4EDE0;
               font-family: Vazirmatn, Tahoma, sans-serif; text-align: center; padding: 24px; box-sizing: border-box; }
        h1 { font-size: 20px; color: #E3C985; margin: 16px 0 8px; }
        p { font-size: 14px; opacity: .75; line-height: 1.9; margin: 0 0 20px; }
        button { background: linear-gradient(90deg, #E6CD8A, #C9A24B); color: #221A0C; border: 0; border-radius: 999px; padding: 12px 28px;
                 font: inherit; font-weight: 700; cursor: pointer; }
        svg { width: 56px; height: 56px; color: #D8B96A; }
    </style>
</head>
<body>
<main>
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
        <path d="M2 8.8a15 15 0 0 1 20 0"/><path d="M5 12.6a10 10 0 0 1 14 0"/><path d="M8.5 16.3a5 5 0 0 1 7 0"/><path d="M12 20h.01"/><path d="M3 3l18 18"/>
    </svg>
    <h1>اتصال به اینترنت برقرار نیست</h1>
    <p>برای دیدن نوبت‌ها و رزرو، اینترنت گوشی را بررسی کنید و دوباره امتحان کنید.</p>
    <button type="button" onclick="location.reload()">تلاش دوباره</button>
</main>
</body>
</html>
