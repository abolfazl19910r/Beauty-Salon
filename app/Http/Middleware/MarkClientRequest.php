<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * درخواست واقعی HTTP را علامت می‌زند تا ثبت کار کلیدی (SecurityLogService::logAction) فقط IP واقعی مشتری را بنویسد. درخواستی
 * که Laravel برای دستورهای console، صف و scheduler می‌سازد هم REMOTE_ADDR=127.0.0.1 دارد و نباید به‌نام مشتری ثبت شود.
 */
class MarkClientRequest
{
    public const ATTRIBUTE = 'mahru.client_request';

    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set(self::ATTRIBUTE, true);

        return $next($request);
    }
}
