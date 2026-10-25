<?php

/*
| احراز سالن‌ها (بسته‌ی مجوز 5د). driver: manual (الان) — بعداً emta وقتی دسترسی سامانه‌ی احراز مشتریان تجارت الکترونیکی گرفته شد.
| App\Contracts\Verification\SalonIdentityVerifier
*/

return [

    'driver' => env('SALON_VERIFIER') ?: 'manual',

    'drivers' => [
        'manual' => \App\Services\Verification\ManualSalonIdentityVerifier::class,
        // 'emta' => \App\Services\Verification\EmtaSalonIdentityVerifier::class,   // مرحله‌ی ب، بعد از دسترسی امتا
    ],

];
