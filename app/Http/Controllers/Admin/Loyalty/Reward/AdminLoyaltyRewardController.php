<?php

namespace App\Http\Controllers\Admin\Loyalty\Reward;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Loyalty\Reward\RedeemRewardRequest;
use App\Http\Requests\Admin\Loyalty\Reward\StoreLoyaltyRewardRequest;
use App\Http\Requests\Admin\Loyalty\Reward\UpdateLoyaltyRewardRequest;
use App\Models\Reward;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

/**
 * مسئول CRUD پاداش‌های برنامه‌ی وفاداری (web + API).
 * از AdminLoyaltyController استخراج شد (فاز R-AdminLoyalty).
 */
class AdminLoyaltyRewardController extends Controller
{
    // ── Web ──────────────────────────────────────────────────────

    public function create()
    {
        return view('admin.loyalty.create');
    }

    public function store(StoreLoyaltyRewardRequest $request): RedirectResponse
    {
        try {
            Reward::create($request->validated());

            return redirect()->route('admin.loyalty.index')
                ->with('success', 'پاداش با موفقیت ایجاد شد.');

        } catch (Exception $e) {
            return redirect()->route('admin.loyalty.index')
                ->with('error', 'خطا در ایجاد پاداش: ' . $e->getMessage());
        }
    }

    public function show(Reward $reward)
    {
        return view('admin.loyalty.show', compact('reward'));
    }

    public function edit(Reward $reward)
    {
        try {
            return view('admin.loyalty.edit', compact('reward'));
        } catch (Exception $e) {
            return redirect()->route('admin.loyalty.index')
                ->with('error', 'خطا در بارگذاری اطلاعات پاداش: ' . $e->getMessage());
        }
    }

    public function update(UpdateLoyaltyRewardRequest $request, Reward $reward): RedirectResponse
    {
        try {
            $reward->update($request->validated());

            return redirect()->route('admin.loyalty.index')
                ->with('success', 'پاداش با موفقیت به‌روزرسانی شد.');

        } catch (Exception $e) {
            return redirect()->route('admin.loyalty.edit', $reward)
                ->with('error', 'خطا در به‌روزرسانی پاداش: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy(Reward $reward): RedirectResponse
    {
        try {
            if ($reward->used_count > 0) {
                return redirect()->route('admin.loyalty.index')
                    ->with('error', 'پاداش‌هایی که استفاده شده‌اند قابل حذف نیستند.');
            }

            $reward->delete();

            return redirect()->route('admin.loyalty.index')
                ->with('success', 'پاداش با موفقیت حذف شد.');

        } catch (Exception $e) {
            return redirect()->route('admin.loyalty.index')
                ->with('error', 'خطا در حذف پاداش: ' . $e->getMessage());
        }
    }

    public function redeemReward(RedeemRewardRequest $request, Reward $reward): RedirectResponse
    {
        try {
            $loyaltyService = app(\App\Services\LoyaltyService::class);
            $loyaltyService->redeemReward($request->validated()['user_id'], $reward);

            return redirect()->route('admin.loyalty.index')
                ->with('success', 'پاداش با موفقیت برای کاربر فعال شد.');

        } catch (Exception $e) {
            return redirect()->route('admin.loyalty.index')
                ->with('error', 'خطا در فعال‌سازی پاداش: ' . $e->getMessage());
        }
    }

    // ── API ──────────────────────────────────────────────────────

    public function getRewards(): JsonResponse
    {
        try {
            $rewards = Reward::where('is_active', true)
                ->orderBy('required_points')
                ->get();

            return response()->json($rewards);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت لیست پاداش‌ها: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function storeReward(StoreLoyaltyRewardRequest $request): JsonResponse
    {
        try {
            $reward = Reward::create($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'پاداش با موفقیت ایجاد شد.',
                'data'    => $reward,
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در ایجاد پاداش: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function showReward(Reward $reward): JsonResponse
    {
        return response()->json($reward);
    }

    public function updateReward(UpdateLoyaltyRewardRequest $request, Reward $reward): JsonResponse
    {
        try {
            $reward->update($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'پاداش با موفقیت به‌روزرسانی شد.',
                'data'    => $reward,
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در به‌روزرسانی پاداش: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroyReward(Reward $reward): JsonResponse
    {
        try {
            if ($reward->used_count > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'پاداش‌هایی که استفاده شده‌اند قابل حذف نیستند.',
                ], 422);
            }

            $reward->delete();

            return response()->json([
                'success' => true,
                'message' => 'پاداش با موفقیت حذف شد.',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در حذف پاداش: ' . $e->getMessage(),
            ], 500);
        }
    }
}
