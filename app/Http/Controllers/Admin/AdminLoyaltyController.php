<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Loyalty;
use App\Models\LoyaltyPoint;
use App\Models\Reward;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminLoyaltyController extends Controller
{
    public function index()
    {
        $totalActivePoints = LoyaltyPoint::where('type', 'earned')->sum('points');
        $totalPointUsers = LoyaltyPoint::distinct('user_id')->count('user_id');
        $averageUserPoints = $totalPointUsers > 0 ? $totalActivePoints / $totalPointUsers : 0;
        $totalRedeemedRewards = Reward::sum('used_count');

        return view('admin.loyalty.index', compact(
            'totalActivePoints',
            'totalPointUsers',
            'averageUserPoints',
            'totalRedeemedRewards'
        ));
    }

    public function create()
    {
        return view('admin.loyalty.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'points_required' => 'required|integer|min:1',
            'discount_percentage' => 'required|numeric|min:0|max:100',
            'is_active' => 'boolean',
        ]);

        Loyalty::create($validated);

        return redirect()->route('admin.loyalty.index')
            ->with('success', 'برنامه وفاداری با موفقیت ایجاد شد.');
    }

    public function show(Reward $reward)
    {
        return view('admin.loyalty.show', compact('reward'));
    }

    public function edit($reward)
    {
        try {
            $rewardItem = Reward::findOrFail($reward);

            return view('admin.loyalty.edit', [
                'reward' => $rewardItem
            ]);
        } catch (\Exception $e) {
            return redirect()->route('loyalty.index')
                ->with('error', 'خطا در بارگذاری اطلاعات پاداش: ' . $e->getMessage());
        }
    }

    public function update(Request $request, Loyalty $loyalty)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'points_required' => 'required|integer|min:1',
            'discount_percentage' => 'required|numeric|min:0|max:100',
            'is_active' => 'boolean',
        ]);

        $loyalty->update($validated);

        return redirect()->route('admin.loyalty.index')
            ->with('success', 'برنامه وفاداری با موفقیت به‌روزرسانی شد.');
    }

    public function destroy(Loyalty $loyalty)
    {
        $loyalty->delete();

        return redirect()->route('admin.loyalty.index')
            ->with('success', 'برنامه وفاداری با موفقیت حذف شد.');
    }

    public function redeemReward(Request $request, Reward $reward)
    {
        try {
            $validatedData = $request->validate([
                'user_id' => 'required|exists:users,id'
            ]);

            $loyaltyService = app()->make(\App\Services\LoyaltyService::class);
            $result = $loyaltyService->redeemReward($validatedData['user_id'], $reward);

            return redirect()->route('admin.loyalty.index')
                ->with('success', 'پاداش با موفقیت برای کاربر فعال شد.');
        } catch (\Exception $e) {
            return redirect()->route('admin.loyalty.index')
                ->with('error', 'خطا در فعال‌سازی پاداش: ' . $e->getMessage());
        }
    }

    public function getPoints()
    {
        $points = LoyaltyPoint::select(
            DB::raw('SUM(CASE WHEN type = "earned" THEN points ELSE 0 END) as earned'),
            DB::raw('SUM(CASE WHEN type = "spent" THEN points ELSE 0 END) as spent')
        )->first();

        return response()->json([
            'total' => $points->earned - $points->spent,
            'earned' => $points->earned,
            'spent' => $points->spent
        ]);
    }

    public function getRewards()
    {
        try {
            $rewards = Reward::where('is_active', true)
                ->orderBy('required_points')
                ->get();

            return response()->json($rewards);
        } catch (\Exception $e) {
            Log::error('خطا در دریافت پاداش‌ها: ' . $e->getMessage());

            return response()->json([
                'message' => 'خطا در دریافت لیست پاداش‌ها',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getHistory()
    {
        $history = LoyaltyPoint::with(['user:id,name', 'booking'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($history);
    }

    public function export(Request $request)
    {
        $type = $request->input('type', 'points');

        if ($type === 'points') {
            $data = LoyaltyPoint::with('user:id,name')
                ->orderBy('created_at', 'desc')
                ->get();
        } elseif ($type === 'rewards') {
            $data = Reward::orderBy('required_points')
                ->get();
        } else {
            $data = [];
        }

        return response()->json($data);
    }

    public function storeReward(Request $request)
    {
        try {
            Log::info('Creating new reward', $request->all());

            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'required_points' => 'required|integer|min:1',
                'discount_type' => 'required|in:fixed,percentage',
                'discount_amount' => [
                    'required',
                    'numeric',
                    'min:1',
                    function($attribute, $value, $fail) use ($request) {
                        if ($request->discount_type === 'percentage' && $value > 100) {
                            $fail('درصد تخفیف نمی‌تواند بیشتر از 100 باشد.');
                        }
                    }
                ],
                'max_uses' => 'required|integer|min:1',
                'is_active' => 'boolean'
            ]);

            $reward = Reward::create($validated);

            Log::info('Reward created successfully', ['id' => $reward->id]);

            return response()->json([
                'success' => true,
                'message' => 'پاداش با موفقیت ایجاد شد',
                'data' => $reward
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating reward', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در ایجاد پاداش: ' . $e->getMessage()
            ], 500);
        }
    }

    public function showReward(Reward $reward)
    {
        return response()->json($reward);
    }

    public function updateReward(Request $request, Reward $reward)
    {
        try {
            $validated = $request->validate([
                'title' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'required_points' => 'sometimes|integer|min:1',
                'discount_type' => 'sometimes|in:fixed,percentage',
                'discount_amount' => [
                    'sometimes',
                    'numeric',
                    'min:1',
                    function($attribute, $value, $fail) use ($request) {
                        if ($request->has('discount_type') &&
                            $request->discount_type === 'percentage' &&
                            $value > 100) {
                            $fail('درصد تخفیف نمی‌تواند بیشتر از 100 باشد.');
                        }
                    }
                ],
                'max_uses' => 'sometimes|integer|min:' . $reward->used_count,
                'is_active' => 'sometimes|boolean'
            ]);

            $reward->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'پاداش با موفقیت بروزرسانی شد',
                'data' => $reward
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در بروزرسانی پاداش: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroyReward(Reward $reward)
    {
        try {
            if ($reward->used_count > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'پاداش‌هایی که استفاده شده‌اند قابل حذف نیستند'
                ], 422);
            }

            $reward->delete();

            return response()->json([
                'success' => true,
                'message' => 'پاداش با موفقیت حذف شد'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در حذف پاداش: ' . $e->getMessage()
            ], 500);
        }
    }
}
