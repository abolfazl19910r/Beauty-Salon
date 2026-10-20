<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\AppRelease;
use App\Services\Apps\AppReleaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * GET /api/v1/app/latest?variant=customer&version_code=7 — بدون ورود (بسته‌ی ۵). اپ‌ها در مارکت نیستند، پس خودشان می‌پرسند نسخه‌ی تازه
 * آمده یا نه: update_available (پیشنهاد به‌روزرسانی)، update_required (نسخه‌ی اجباری تازه‌تر از نسخه‌ی گوشی منتشر شده).
 */
class AppVersionController extends Controller
{
    public function __invoke(Request $request, AppReleaseService $releases): JsonResponse
    {
        $input = $request->validate([
            'variant' => ['required', Rule::in(array_keys(AppRelease::VARIANTS))],
            'version_code' => ['nullable', 'integer', 'min:0'],
        ]);

        return ApiResponse::success($releases->check(
            $input['variant'],
            isset($input['version_code']) ? (int) $input['version_code'] : null,
        ));
    }
}
