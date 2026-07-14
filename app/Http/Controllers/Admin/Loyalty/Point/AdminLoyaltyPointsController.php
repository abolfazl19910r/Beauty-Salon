<?php

namespace App\Http\Controllers\Admin\Loyalty\Point;

use App\Exceptions\InsufficientLoyaltyPointsException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Loyalty\Point\AddUserPointsRequest;
use App\Http\Requests\Admin\Loyalty\Point\DeductUserPointsRequest;
use App\Models\LoyaltyPoint;
use App\Models\User;
use App\Services\Admin\Loyalty\LoyaltyAdminService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Responsible for managing user privileges from the admin panel.
 * Derived from AdminLoyaltyController (R-AdminLoyalty phase).
 */
class AdminLoyaltyPointsController extends Controller
{
    public function __construct(
        protected LoyaltyAdminService $loyaltyService,
    ) {}

    public function getPoints(Request $request): JsonResponse
    {
        try {
            $query = LoyaltyPoint::with('user:id,name,phone')
                ->orderByDesc('created_at');

            if ($request->filled('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            if ($request->filled('type')) {
                $query->where('type', $request->type);
            }

            $points = $query->paginate(20);

            return response()->json($points);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت امتیازات: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getHistory(Request $request): JsonResponse
    {
        try {
            $history = $this->loyaltyService->getHistory($request->only(
                'user_id', 'type', 'from', 'to'
            ));

            return response()->json($history);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت تاریخچه: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getStatistics(): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data'    => $this->loyaltyService->getStatistics(),
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت آمار: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getUserPoints(User $user): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data'    => $this->loyaltyService->getUserPoints($user),
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت امتیازات کاربر: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function addUserPoints(AddUserPointsRequest $request, User $user): JsonResponse
    {
        try {
            $point = $this->loyaltyService->addPoints(
                user: $user,
                points: $request->points,
                description: $request->description,
                expiresAt: $request->expires_at,
            );

            return response()->json([
                'success' => true,
                'message' => 'امتیاز با موفقیت اضافه شد.',
                'data'    => $point,
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در افزودن امتیاز: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function deductUserPoints(DeductUserPointsRequest $request, User $user): JsonResponse
    {
        try {
            $point = $this->loyaltyService->deductPoints(
                user: $user,
                points: $request->points,
                description: $request->description,
            );

            return response()->json([
                'success' => true,
                'message' => 'امتیاز با موفقیت کسر شد.',
                'data'    => $point,
            ]);

        } catch (InsufficientLoyaltyPointsException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getUserMessage(),
            ], $e->getHttpStatus());

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در کسر امتیاز: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function export(Request $request): JsonResponse
    {
        try {
            $data = $this->loyaltyService->getExportData(
                $request->input('type', 'points')
            );

            return response()->json([
                'success' => true,
                'data'    => $data,
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در خروجی گرفتن: ' . $e->getMessage(),
            ], 500);
        }
    }
}
