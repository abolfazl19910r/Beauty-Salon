<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\Api\ApiAccountPresenter;
use App\Support\CurrentSalon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET /api/v1/me و POST /api/v1/logout (هر دو اپ؛ پشت auth:sanctum + api.audience).
 */
class AccountController extends Controller
{
    public function me(Request $request, CurrentSalon $currentSalon): JsonResponse
    {
        $token = $request->user()->currentAccessToken();

        return ApiResponse::success(array_merge(
            ApiAccountPresenter::present(
                $request->user(),
                $request->attributes->get('api_audience'),
                $currentSalon->get(),
                $request->attributes->get('api_specialist'),
            ),
            ['token' => ['id' => $token->id, 'name' => $token->name, 'expires_at' => $token->expires_at?->toIso8601String()]],
        ));
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return ApiResponse::success(null);
    }
}
