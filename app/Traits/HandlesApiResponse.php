<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

/**
 * ⭐ R-Traits: Unification of the simple JSON response success/message that was exactly repeated
 * in SpecialistNotificationController, AdminNotificationController,
 * User\NotificationController and part of BookingDiscountController.
 *
 * ⚠️ Intentional scope of this Trait: only covers responses with exactly the same
 * structure `{success: bool, message?: string, ...}`. There are 150+
 * other `response()->json()` calls in the whole project (e.g. the endpoints
 * of `BookingAvailabilityController` that return `slots`/`dates`/`error` keys,
 * or `LoyaltyController` that returns raw `points`/`history`) — these were deliberately *not* moved to
 * this Trait, as their data format is really different, not a copy of the same
 * template; Forcing them to have the same format would be a real behavior/API contract change
 * (risking breaking existing consumer JavaScript), not just removing duplicate code.
 */
trait HandlesApiResponse
{
    /**
     * @param  array<string,mixed>  $extra  Extra keys (e.g. ['status' => 'read'])
     */
    protected function successResponse(?string $message = null, array $extra = [], int $status = 200): JsonResponse
    {
        return response()->json(array_merge(
            ['success' => true],
            $message !== null ? ['message' => $message] : [],
            $extra
        ), $status);
    }

    /**
     * @param  array<string,mixed>  $extra  Extra keys
     */
    protected function errorResponse(string $message, int $status = 422, array $extra = []): JsonResponse
    {
        return response()->json(array_merge(
            ['success' => false, 'message' => $message],
            $extra
        ), $status);
    }
}
