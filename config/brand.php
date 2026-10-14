<?php

/*
 * ⭐ برند خودِ پلتفرم (۲۰۲۶-۰۹-۲۵) — «ماهرو» (Mahru)، تصمیم ابوالفضل؛ نشان تازه از ۲۰۲۶-۱۰-۱۴: هلال دوگانه‌ی طلایی، سه چهره و صورت فلکی (docs/brand/kit؛ نام فارسی همان «ماهرو»).
 * جدا از APP_NAME: اسم کوکی session و پیشوند cache از APP_NAME ساخته می‌شن، پس عوض کردنش همه رو
 * logout می‌کرد. این فقط نام نمایشی صفحه‌های خودِ پلتفرم (صفحه‌ی فروش، ثبت‌نام سالن، سوپرادمین)
 * و پیش‌فرض وقتی سالنی در کار نیست. هر سالن نام و لوگوی خودش رو داره (salons.name / logo_path).
 * فایل‌های لوگو: public/brand/ — سازنده: docs/brand/generate_brand_assets.py
 */
return [
    'name' => env('BRAND_NAME', 'ماهرو'),
    'name_en' => env('BRAND_NAME_EN', 'Mahru'),
    'tagline' => 'نوبت‌دهی آنلاین سالن‌های زیبایی',
    'domain' => env('BRAND_DOMAIN', 'mahru.ir'),
    'theme_color' => '#1A1410',
    // راه‌های تماس صفحه‌ی فروش (لندینگ ۲۰۲۶-۱۰-۱۴): فقط پیش‌فرض — سوپرادمین از «تنظیمات پلتفرم» عوض می‌کند (App\Support\PlatformSettings)؛
    // خالی = آن لینک اصلاً نشان داده نمی‌شود (نه شماره‌ی نمونه)
    'support_phone' => env('BRAND_SUPPORT_PHONE') ?: null,
    'email' => env('BRAND_EMAIL') ?: null,
    'instagram_url' => env('BRAND_INSTAGRAM_URL') ?: null,
    'linkedin_url' => env('BRAND_LINKEDIN_URL') ?: null,
    'telegram_url' => env('BRAND_TELEGRAM_URL') ?: null,
    'bale_url' => env('BRAND_BALE_URL') ?: null,
    // گیرنده‌ی ایمیل درخواست‌های دمو (۲۰۲۶-۱۰-۱۴)؛ ارسال واقعی به MAIL_* نیاز دارد (پیش‌فرض MAIL_MAILER=log)
    'demo_notify_email' => env('BRAND_DEMO_NOTIFY_EMAIL') ?: null,
];
