<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SecurityMiddleware
{
    protected RateLimiter $rateLimiter;

    public function __construct(RateLimiter $rateLimiter)
    {
        $this->rateLimiter = $rateLimiter;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $key = 'security:' . $request->ip();

        if ($this->rateLimiter->tooManyAttempts($key, 60)) {
            Log::warning('Rate limit exceeded', [
                'ip' => $request->ip(),
                'user_id' => auth()->id(),
                'path' => $request->path()
            ]);

            return response()->json([
                'error' => 'Too many requests',
                'retry_after' => $this->rateLimiter->availableIn($key)
            ], 429);
        }

        $this->rateLimiter->hit($key);

        if ($this->isSensitiveOperation($request)) {
            Log::info('Sensitive operation detected', [
                'user_id' => auth()->id(),
                'operation' => $request->route()->getName(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);
        }

        $response = $next($request);
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        if (app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    protected function isSensitiveOperation(Request $request): bool
    {
        $sensitivePaths = [
            'password/*',
            'profile/*',
            'payment/*',
            'admin/*'
        ];

        return $request->is($sensitivePaths);
    }
}
