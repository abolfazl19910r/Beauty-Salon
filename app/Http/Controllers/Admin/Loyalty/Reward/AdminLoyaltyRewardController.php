<?php

namespace App\Http\Controllers\Admin\Loyalty\Reward;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Loyalty\Reward\RedeemRewardRequest;
use App\Http\Requests\Admin\Loyalty\Reward\StoreLoyaltyRewardRequest;
use App\Http\Requests\Admin\Loyalty\Reward\UpdateLoyaltyRewardRequest;
use App\Models\Reward;
use App\Services\Admin\Loyalty\LoyaltyAdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Responsible for CRUD of loyalty program rewards (web + API).
 * Derived from AdminLoyaltyController (R-AdminLoyalty phase).
 */
class AdminLoyaltyRewardController extends Controller
{
    public function __construct(
        private readonly LoyaltyAdminService $loyaltyAdminService,
    ) {}

    public function index(): View
    {
        return view('admin.loyalty.index', $this->loyaltyAdminService->getDashboardStats());
    }

    public function create(): View
    {
        return view('admin.loyalty.create');
    }

    public function store(StoreLoyaltyRewardRequest $request): RedirectResponse
    {
        try {
            $this->loyaltyAdminService->createReward($request->validated());

            return redirect()->route('admin.loyalty.index')
                ->with('success', 'پاداش با موفقیت ایجاد شد');
        } catch (\Throwable $e) {
            return redirect()->route('admin.loyalty.index')
                ->with('error', 'خطا در ایجاد پاداش: '.$e->getMessage());
        }
    }

    public function show(Reward $reward): View
    {
        return view('admin.loyalty.show', compact('reward'));
    }

    public function edit(Reward $reward): View
    {
        return view('admin.loyalty.edit', compact('reward'));
    }

    public function update(UpdateLoyaltyRewardRequest $request, Reward $reward): RedirectResponse
    {
        try {
            $this->loyaltyAdminService->updateReward($reward, $request->validated());

            return redirect()->route('admin.loyalty.index')
                ->with('success', 'پاداش با موفقیت به‌روزرسانی شد.');
        } catch (\Throwable $e) {
            return redirect()->route('admin.loyalty.edit', $reward)
                ->with('error', 'خطا در به‌روزرسانی پاداش: '.$e->getMessage())
                ->withInput();
        }
    }

    public function destroy(Reward $reward): RedirectResponse
    {
        try {
            $this->loyaltyAdminService->deleteReward($reward);

            return redirect()->route('admin.loyalty.index')
                ->with('success', 'پاداش با موفقیت حذف شد.');
        } catch (\Throwable $e) {
            return redirect()->route('admin.loyalty.index')
                ->with('error', $e->getMessage());
        }
    }

    public function redeemReward(RedeemRewardRequest $request, Reward $reward): RedirectResponse
    {
        try {
            $this->loyaltyAdminService->redeemRewardForUser($request->validated('user_id'), $reward);

            return redirect()->route('admin.loyalty.index')
                ->with('success', 'پاداش با موفقیت برای کاربر فعال شد.');
        } catch (\Throwable $e) {
            return redirect()->route('admin.loyalty.index')
                ->with('error', 'خطا در فعال‌سازی پاداش: '.$e->getMessage());
        }
    }

    // ---- JSON version — for resources/js/admin.jsx (SPA mount point in index.blade.php) ----

    public function getRewards(): JsonResponse
    {
        try {
            return response()->json($this->loyaltyAdminService->getActiveRewards());
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'خطا در دریافت لیست پاداش‌ها',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function storeReward(StoreLoyaltyRewardRequest $request): JsonResponse
    {
        try {
            $reward = $this->loyaltyAdminService->createReward($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'پاداش با موفقیت ایجاد شد',
                'data' => $reward,
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در ایجاد پاداش: '.$e->getMessage(),
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
            $updated = $this->loyaltyAdminService->updateReward($reward, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'پاداش با موفقیت بروزرسانی شد',
                'data' => $updated,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در بروزرسانی پاداش: '.$e->getMessage(),
            ], 500);
        }
    }

    public function destroyReward(Reward $reward): JsonResponse
    {
        try {
            $this->loyaltyAdminService->deleteReward($reward);

            return response()->json([
                'success' => true,
                'message' => 'پاداش با موفقیت حذف شد',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
