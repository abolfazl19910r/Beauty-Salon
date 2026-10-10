<?php

namespace App\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * /api/v1 همیشه JSON است، حتی اگر اپ هدر Accept نفرستد؛ بدون این، خطای اعتبارسنجی به‌جای 422
 * به redirect و نبودِ توکن به‌جای 401 به صفحه‌ی ورود وب تبدیل می‌شد (Authenticate::redirectTo).
 */
class ForceJsonResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
