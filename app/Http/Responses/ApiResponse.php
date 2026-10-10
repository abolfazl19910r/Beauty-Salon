<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

/**
 * قالب یکسان پاسخ‌های /api/v1 (بسته‌ی ۱ اپلیکیشن):
 *   موفق: {"success": true, "data": ..., "meta": {...}}
 *   خطا:  {"success": false, "error": {"code": "...", "message": "...", "fields": {...}}, "meta": {...}}
 * «code» برای منطق اپ است (ثابت، انگلیسی snake_case)؛ «message» فارسی و برای نمایش.
 */
class ApiResponse
{
    public static function success(mixed $data = null, array $meta = [], int $status = 200, array $headers = []): JsonResponse
    {
        $payload = ['success' => true, 'data' => $data];
        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        return self::json($payload, $status, $headers);
    }

    public static function error(
        string $code,
        string $message,
        int $status,
        ?array $fields = null,
        array $meta = [],
        array $headers = [],
    ): JsonResponse {
        $error = ['code' => $code, 'message' => $message];
        if ($fields !== null) {
            $error['fields'] = $fields;
        }

        $payload = ['success' => false, 'error' => $error];
        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        return self::json($payload, $status, $headers);
    }

    protected static function json(array $payload, int $status, array $headers): JsonResponse
    {
        return new JsonResponse($payload, $status, $headers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
