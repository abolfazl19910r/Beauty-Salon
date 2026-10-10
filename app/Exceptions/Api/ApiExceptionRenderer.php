<?php

namespace App\Exceptions\Api;

use App\Exceptions\DomainException;
use App\Http\Responses\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Exceptions\MissingAbilityException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * همه‌ی خطاهای /api/v1 را به قالب یکسان ApiResponse::error تبدیل می‌کند (بسته‌ی ۱ اپلیکیشن).
 * در bootstrap/app.php پیش از رندرکننده‌های عمومی ثبت می‌شود؛ برای مسیرهای دیگر null برمی‌گرداند
 * تا رفتار وب و /api قدیمی دست نخورد. هیچ‌وقت جزئیات خطای داخلی را برنمی‌گرداند (حتی با APP_DEBUG).
 */
class ApiExceptionRenderer
{
    public const PATH_PATTERN = 'api/v1/*';

    protected const DEFAULT_MESSAGES = [
        400 => 'درخواست نامعتبر است.',
        401 => 'ابتدا وارد حساب خود شوید.',
        403 => 'اجازه‌ی این کار را ندارید.',
        404 => 'مورد درخواستی پیدا نشد.',
        405 => 'این روش درخواست برای این آدرس پشتیبانی نمی‌شود.',
        409 => 'درخواست با وضعیت فعلی تداخل دارد.',
        410 => 'مهلت این درخواست تمام شده است.',
        419 => 'جلسه منقضی شده است.',
        422 => 'اطلاعات ارسالی معتبر نیست.',
        429 => 'تعداد تلاش‌های شما بیش از حد مجاز است. لطفاً کمی بعد دوباره تلاش کنید.',
        500 => 'خطای داخلی سرور. لطفاً کمی بعد دوباره تلاش کنید.',
        503 => 'سرویس موقتاً در دسترس نیست.',
    ];

    protected const CODES = [
        400 => 'bad_request',
        401 => 'unauthenticated',
        403 => 'forbidden',
        404 => 'not_found',
        405 => 'method_not_allowed',
        409 => 'conflict',
        410 => 'gone',
        419 => 'session_expired',
        422 => 'validation_failed',
        429 => 'too_many_attempts',
        503 => 'service_unavailable',
    ];

    public static function handles(Request $request): bool
    {
        return $request->is(self::PATH_PATTERN);
    }

    public static function render(Throwable $e, Request $request): ?JsonResponse
    {
        if (! self::handles($request)) {
            return null;
        }

        return match (true) {
            $e instanceof ApiException => ApiResponse::error($e->errorCode, $e->getMessage(), $e->status, null, $e->meta, $e->headers),
            $e instanceof ValidationException => ApiResponse::error('validation_failed', self::DEFAULT_MESSAGES[422], $e->status, $e->errors()),
            $e instanceof AuthenticationException => ApiResponse::error('unauthenticated', self::DEFAULT_MESSAGES[401], 401),
            $e instanceof ThrottleRequestsException => self::throttled($e),
            $e instanceof DomainException => ApiResponse::error(
                Str::snake(Str::beforeLast(class_basename($e), 'Exception')),
                $e->getUserMessage(),
                $e->getHttpStatus(),
            ),
            $e instanceof HttpExceptionInterface => self::http($e),
            default => ApiResponse::error('server_error', self::DEFAULT_MESSAGES[500], 500),
        };
    }

    protected static function throttled(ThrottleRequestsException $e): JsonResponse
    {
        $headers = $e->getHeaders();
        $retryAfter = (int) ($headers['Retry-After'] ?? 60);

        return ApiResponse::error('too_many_attempts', self::DEFAULT_MESSAGES[429], 429, null, ['retry_after' => $retryAfter], $headers);
    }

    protected static function http(HttpExceptionInterface $e): JsonResponse
    {
        $status = $e->getStatusCode();

        // ability توکن Sanctum (اگر جایی middleware ability/abilities به کار رود)
        if ($e->getPrevious() instanceof MissingAbilityException) {
            $wrongApp = ApiException::wrongApp();

            return ApiResponse::error($wrongApp->errorCode, $wrongApp->getMessage(), $wrongApp->status);
        }

        return ApiResponse::error(
            self::CODES[$status] ?? ($status >= 500 ? 'server_error' : 'http_'.$status),
            self::messageFor($e, $status),
            $status,
            null,
            [],
            $e->getHeaders(),
        );
    }

    /**
     * پیام‌های abort() خود پروژه فارسی‌اند و نمایش داده می‌شوند؛ پیام‌های انگلیسی فریم‌ورک
     * («The route ... could not be found.»، «No query results for model ...»، «This action is
     * unauthorized.») نه — هم برای کاربر بی‌معنی‌اند و هم ساختار داخلی را لو می‌دهند.
     */
    protected static function messageFor(HttpExceptionInterface $e, int $status): string
    {
        $message = $e->getMessage();

        if ($status < 500 && $message !== '' && preg_match('/[^\x00-\x7F]/', $message)) {
            return $message;
        }

        return self::DEFAULT_MESSAGES[$status] ?? ($status >= 500 ? self::DEFAULT_MESSAGES[500] : self::DEFAULT_MESSAGES[400]);
    }
}
