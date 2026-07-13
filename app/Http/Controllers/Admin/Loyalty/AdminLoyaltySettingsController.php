<?php

namespace App\Http\Controllers\Admin\Loyalty;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Loyalty\UpdateLoyaltySettingsRequest;
use App\Models\Setting;
use Exception;
use Illuminate\Http\JsonResponse;

/**
 * مسئول تنظیمات برنامه‌ی وفاداری از پنل ادمین.
 * از AdminLoyaltyController استخراج شد (فاز R-AdminLoyalty).
 */
class AdminLoyaltySettingsController extends Controller
{
    private array $loyaltySettingKeys = [
        'points_per_booking',
        'points_per_referral',
        'points_expiry_days',
        'min_points_to_redeem',
        'loyalty_program_enabled',
    ];

    public function getSettings(): JsonResponse
    {
        try {
            $settings = Setting::whereIn('key', $this->loyaltySettingKeys)
                ->pluck('value', 'key');

            return response()->json([
                'success' => true,
                'data'    => $settings,
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت تنظیمات: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function updateSettings(UpdateLoyaltySettingsRequest $request): JsonResponse
    {
        try {
            foreach ($request->validated() as $key => $value) {
                Setting::updateOrCreate(
                    ['key' => $key],
                    ['value' => $value]
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'تنظیمات با موفقیت به‌روزرسانی شد.',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در به‌روزرسانی تنظیمات: ' . $e->getMessage(),
            ], 500);
        }
    }
}
