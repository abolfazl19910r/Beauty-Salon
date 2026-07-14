<?php

namespace App\Http\Controllers\Admin\Loyalty;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Loyalty\UpdateLoyaltySettingsRequest;
use App\Models\LoyaltySetting;
use Illuminate\Http\JsonResponse;
use Throwable;

/**
 * Responsible for the loyalty program settings from the admin panel.
 *
 * ⚠️ Integration (R-AdminLoyalty phase, after approval): Previously there were three separate sources for these
 * settings (loyalty_settings table, global settings table with group=loyalty,
 * and global settings table without group). The dedicated `loyalty_settings` table (model
 * LoyaltySetting) was chosen as the single source because it was created for this purpose
 * and the initial migration seeded it. The actual points calculation formula
 * (App\Models\LoyaltyPoint::calculatePointsForBooking and
 * App\Services\LoyaltyService::calculatePointsForBooking) now read this same source.
 */
class AdminLoyaltySettingsController extends Controller
{
    /**
     * Valid loyalty settings keys — exactly the same ones seeded in the initial migration
     * * (loyalty_settings).
     */
    private const SETTING_KEYS = [
        'points_per_amount',
        'points_expiry_months',
        'minimum_points_for_discount',
    ];

    public function getSettings(): JsonResponse
    {
        try {
            $settings = LoyaltySetting::whereIn('key', self::SETTING_KEYS)
                ->get(['key', 'value', 'description'])
                ->keyBy('key');

            return response()->json([
                'success' => true,
                'data' => $settings,
            ]);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت تنظیمات: '.$e->getMessage(),
            ], 500);
        }
    }

    public function updateSettings(UpdateLoyaltySettingsRequest $request): JsonResponse
    {
        try {
            // These three rows are always pre-seeded by the initial migration;
            // update() is intentionally used instead of updateOrCreate() to
            // avoid accidentally creating an unknown key in the table.
            foreach ($request->validated() as $key => $value) {
                LoyaltySetting::where('key', $key)->update(['value' => $value]);
            }

            return response()->json([
                'success' => true,
                'message' => 'تنظیمات با موفقیت به‌روزرسانی شد.',
            ]);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در به‌روزرسانی تنظیمات: '.$e->getMessage(),
            ], 500);
        }
    }
}
