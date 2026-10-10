<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Exceptions\Api\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * «دستگاه‌های من»: فهرست توکن‌های معتبر همین حساب، خروج یک دستگاه، خروج از همه‌ی دستگاه‌های دیگر.
 * حساب مشتری هر سالن و حساب کادر ردیف‌های جدای users هستند، پس هر فهرست فقط دستگاه‌های همان حساب است.
 */
class TokenController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $currentId = $request->user()->currentAccessToken()->id;

        $tokens = $request->user()->tokens()
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->orderByDesc('last_used_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (PersonalAccessToken $token) => [
                'id' => $token->id,
                'name' => $token->name,
                'current' => $token->id === $currentId,
                'created_at' => $token->created_at?->toIso8601String(),
                'last_used_at' => $token->last_used_at?->toIso8601String(),
                'expires_at' => $token->expires_at?->toIso8601String(),
            ])
            ->values();

        return ApiResponse::success($tokens);
    }

    public function destroy(Request $request, int $tokenId): JsonResponse
    {
        $token = $request->user()->tokens()->whereKey($tokenId)->first();

        if (! $token) {
            throw new ApiException('not_found', 'این دستگاه پیدا نشد.', 404);
        }

        $token->delete();

        return ApiResponse::success(null);
    }

    public function revokeOthers(Request $request): JsonResponse
    {
        $revoked = $request->user()->tokens()
            ->whereKeyNot($request->user()->currentAccessToken()->id)
            ->delete();

        return ApiResponse::success(['revoked' => $revoked]);
    }
}
