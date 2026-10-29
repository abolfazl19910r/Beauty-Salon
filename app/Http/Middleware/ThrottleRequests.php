<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\DetectsConcurrencyErrors;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests as BaseThrottleRequests;
use Symfony\Component\HttpFoundation\Response;

/**
 * محدودیت نرخ با تکرار در بن‌بست شمارنده (لاگ داکر ۲۰۲۶-۱۰-۲۹): در کش database (تک‌کانتینری، MariaDB) درخواست‌های هم‌زمان اپ روی
 * «insert ignore into cache» شمارنده‌ی RateLimiter به Deadlock (1213) می‌خوردند و کاربر ۵۰۰ می‌گرفت. فقط خود شمارش تکرار می‌شود:
 * اگر خطا بعد از رسیدن به برنامه (پشت $next) رخ داده باشد، دست نخورده بالا می‌رود تا درخواست دو بار اجرا نشود.
 */
class ThrottleRequests extends BaseThrottleRequests
{
    use DetectsConcurrencyErrors;

    private const ATTEMPTS = 3;

    public function handle($request, Closure $next, $maxAttempts = 60, $decayMinutes = 1, $prefix = ''): Response
    {
        // همان تعداد آرگومان: والد limiter نام‌دار («throttle:api-v1») را فقط با func_num_args() === 3 می‌شناسد
        $args = func_get_args();

        for ($attempt = 1; ; $attempt++) {
            $passed = false;
            $through = function (Request $request) use ($next, &$passed) {
                $passed = true;

                return $next($request);
            };

            try {
                $args[1] = $through;

                return parent::handle(...$args);
            } catch (QueryException $e) {
                if ($passed || $attempt >= self::ATTEMPTS || ! $this->causedByConcurrencyError($e)) {
                    throw $e;
                }

                usleep(random_int(10_000, 50_000));
            }
        }
    }
}
