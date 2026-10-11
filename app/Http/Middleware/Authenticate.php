<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * مهمانی که صفحه‌ای از یک سالن (/s/{slug}/… یا ساب‌دامین) را می‌خواهد به ورود **همان سالن** می‌رود (۲۰۲۶-۱۰-۱۱).
     * قبلاً همه به /login مرکزی می‌رفتند که از ۲۰۲۶-۱۰-۱۰ فقط کادر را می‌پذیرد — مشتری آن‌جا وارد نمی‌شد.
     */
    protected function redirectTo(Request $request): ?string
    {
        if ($request->expectsJson()) {
            return null;
        }

        $slug = $request->route('salon_slug');

        return is_string($slug) && $slug !== ''
            ? route('salon.login', ['salon_slug' => $slug])
            : route('login');
    }
}
