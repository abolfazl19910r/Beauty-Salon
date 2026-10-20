<?php

/*
 * بسته‌ی ۶ (۲۰۲۶-۱۰-۲۰): نصب سایت سالن و پنل متخصص روی آیفون با «افزودن به صفحه‌ی اصلی» (PWA).
 * الگوی env('X') ?: پیش‌فرض عمداً (کلید خالی در .env نباید چیزی را خاموش کند).
 */
return [
    // service worker (فقط فایل‌های ثابت و صفحه‌ی «اینترنت قطع است»). با `npm run dev` (فایل public/hot) خودکار ثبت نمی‌شود.
    'service_worker' => filter_var(env('PWA_SERVICE_WORKER') ?: 'true', FILTER_VALIDATE_BOOLEAN),
];
