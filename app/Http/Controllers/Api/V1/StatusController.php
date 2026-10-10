<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * GET /api/v1/status — بدون ورود؛ اپ با آن رسیدن به سرور را می‌سنجد (مثلاً پیش از نمایش «اتصال برقرار نیست»)
 * و ساعت سرور را برای محاسبه‌ی اختلاف ساعت گوشی می‌گیرد.
 */
class StatusController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return ApiResponse::success([
            'api_version' => 'v1',
            'server_time' => now()->toIso8601String(),
        ]);
    }
}
