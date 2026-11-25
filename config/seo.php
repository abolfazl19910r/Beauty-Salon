<?php

/*
 * سئو (۲۰۲۶-۱۱-۲۳): کدام صفحه‌ها ایندکس شوند، robots.txt و sitemap.xml پویا.
 *
 * - فقط routeهای نام‌برده‌ی زیر ایندکس‌پذیرند؛ هر پاسخ دیگر (پنل‌ها، ورود، API، پرداخت، …) هدر
 *   «X-Robots-Tag: noindex, nofollow» می‌گیرد (App\Http\Middleware\AddRobotsTag) — پیش‌فرض بسته، نه باز.
 * - SEO_INDEXING=false (سرور آزمایشی/staging): همه‌چیز noindex و robots.txt ← «Disallow: /».
 */

$indexing = env('SEO_INDEXING');

return [

    'indexing' => $indexing === null || $indexing === '' ? true : filter_var($indexing, FILTER_VALIDATE_BOOL),

    // صفحه‌های عمومی دامنه‌ی اصلی (لندینگ، دانلود اپ‌ها، صفحه‌های حقوقی)
    'central_routes' => [
        'central.home',
        'app-download.show',
        'app-download.staff',
        'legal.terms',
        'legal.privacy',
        'legal.contact',
    ],

    // صفحه‌های عمومی سالن (زیردامنه یا /s/{slug} بی CENTRAL_DOMAIN)؛ فقط سالن تأییدشده، فعال و بی پیش‌نمایش
    'salon_routes' => [
        'home',
        'services.index',
        'services.show',
        'specialists.show',
        'blog.index',
        'blog.show',
        'courses.index',
        'courses.show',
        // بسته‌ی ۵الف: صفحه‌ی «نظرها»ی سالن (canonical بی فیلتر)
        'salon.reviews',
    ],

    // مسیرهایی که خزنده اصلاً لازم نیست ببیند (robots.txt). صفحه‌های ورود عمداً اینجا نیستند: باید خزیده شوند تا
    // noindex آن‌ها دیده شود.
    'disallow' => [
        'central' => [
            '/admin',
            '/superadmin',
            '/specialist/',
            '/specialist$',
            '/api/',
            '/payments/',
            '/app/download/',
            '/b/',
            '/csp-report',
            '/telescope',
        ],
        'salon' => [
            '/api/',
            '/payments/',
            '/b/',
            '/payment/',
            '/bookings',
            '/wallet',
            '/profile',
            '/loyalty',
            '/security',
            '/my-courses',
            '/notifications',
            '/complaints',
            '/terms-update',
        ],
    ],

];
