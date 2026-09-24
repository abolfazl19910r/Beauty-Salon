<?php

/*
 * ⭐ برند خودِ پلتفرم (۲۰۲۶-۰۹-۲۵) — «ماهرو» (Mahru)، تصمیم ابوالفضل؛ لوگو: جهت «الف» (هلال + ستاره).
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
];
