<?php

/**
 * ⭐ فاز ۲ از ۲، محور «۱. پرداخت آنلاین و صورتحساب». قیمت‌های زیر placeholder هستند — هیچ منبع
 * قیمت‌گذاری واقعی‌ای در پروژه موجود نبود، پس مقادیر پیش‌فرض معقول (قابل‌تغییر با .env) گذاشته
 * شد. ابوالفضل باید این چهار عدد را قبل از رفتن به production با قیمت واقعی جایگزین کند —
 * ساده‌ترین راه: مقداردهی SUBSCRIPTION_PRICE_1M/3M/6M/12M در .env، بدون نیاز به تغییر کد.
 */
return [
    'subscription_prices' => [
        '1m' => (int) env('SUBSCRIPTION_PRICE_1M', 490000),
        '3m' => (int) env('SUBSCRIPTION_PRICE_3M', 1350000),
        '6m' => (int) env('SUBSCRIPTION_PRICE_6M', 2500000),
        '12m' => (int) env('SUBSCRIPTION_PRICE_12M', 4500000),
    ],
];
