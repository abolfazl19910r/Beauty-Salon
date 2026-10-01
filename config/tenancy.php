<?php

return [
    // حالت سخت‌گیر BelongsToSalon (۲۰۲۶-۱۰-۰۱): کوئری روی مدل سالن‌دار بدون سالن جاری و بدون CurrentSalon::allSalons()
    // - throw (پیش‌فرض): MissingSalonContextException
    // - log: فقط هشدار در لاگ و رفتار قدیمی (همه‌ی سالن‌ها) — فقط راه فرار اضطراری، نه حالت عادی
    'strict' => env('TENANCY_STRICT') ?: 'throw',
];
